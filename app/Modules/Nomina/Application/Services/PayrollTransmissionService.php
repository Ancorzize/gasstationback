<?php

namespace App\Modules\Nomina\Application\Services;

use App\Models\PayrollSettlement;
use App\Models\DocumentoElectronico;
use App\Modules\Nomina\Infrastructure\Mappers\MatiasPayrollMapper;
use App\Modules\Facturacion\Infrastructure\Clients\MatiasClient;
use App\Modules\Facturacion\Application\Services\DocumentoElectronicoService;
use App\Modules\Facturacion\Application\DTOs\CrearDocumentoElectronicoDTO;
use App\Modules\Facturacion\Application\DTOs\ActualizarDocumentoElectronicoDTO;
use App\Modules\ResolucionesFacturacion\Application\Services\ResolucionFacturacionService;
use App\Modules\ConfiguracionFacturacion\Application\Interfaces\ConfiguracionFacturacionRepositoryInterface;
use App\Modules\Nomina\Domain\Enums\PayrollEarningTypeEnum;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de Aplicación para la transmisión de Nómina Electrónica hacia MATIAS API (POST /ep/payroll)
 * y el registro en la infraestructura de DocumentoElectronico.
 */
class PayrollTransmissionService
{
    public function __construct(
        protected PayrollSettlementService $payrollSettlementService,
        protected DocumentoElectronicoService $documentoElectronicoService,
        protected MatiasClient $matiasClient,
        protected ResolucionFacturacionService $resolucionService,
        protected ?ConfiguracionFacturacionRepositoryInterface $configuracionRepository = null
    ) {}

    /**
     * Transmitir electrónicamente una liquidación de nómina a MATIAS API.
     *
     * @param int $settlementId ID de la liquidación de nómina.
     * @param array $options Opciones adicionales para la transmisión (force_status, etc.).
     * @return array Resultado estructurado de la transmisión.
     */
    public function transmitir(int $settlementId, array $options = []): array
    {
        // 1. Obtener y validar la liquidación de nómina
        $settlement = $this->payrollSettlementService->findById($settlementId);
        $settlement->loadMissing(['payrollPeriod', 'employee', 'employee.paymentAccounts', 'earnings', 'deductions']);

        $this->validarLiquidacionParaTransmision($settlement);

        // 2. Control de Idempotencia: Verificar si ya existe un documento transmitido exitosamente
        $documentosPrevios = $this->documentoElectronicoService->obtenerPorPayrollSettlement($settlementId);
        $docExitoso = $documentosPrevios->firstWhere(fn ($doc) => in_array($doc->estado, ['emitido', 'transmitido', 'aprobado'], true));

        if ($docExitoso) {
            return [
                'exitoso'                 => true,
                'estado'                  => $docExitoso->estado,
                'ya_transmitido'          => true,
                'documento_electronico_id' => $docExitoso->id,
                'payroll_settlement_id'   => $settlement->id,
                'numero_documento'        => $docExitoso->numero_documento,
                'prefijo'                 => $docExitoso->prefijo,
                'cune'                    => $docExitoso->cufe,
                'track_id'                => $docExitoso->track_id,
                'identificador_externo'   => $docExitoso->identificador_externo,
                'mensaje'                 => 'La liquidación de nómina ya fue transmitida exitosamente con anterioridad.',
                'errores'                 => null,
            ];
        }

        // Verificar si hay un proceso de transmisión concurrentemente activo
        $docEnviando = $documentosPrevios->firstWhere('estado', 'enviando');
        if ($docEnviando) {
            throw new HttpException(409, "La liquidación de nómina #{$settlementId} ya se encuentra en proceso de transmisión.");
        }

        // 3. Obtener token y ambiente de MATIAS
        $configFact = $this->configuracionRepository?->first();
        $token = $configFact?->getEffectiveMatiasToken() ?? config('services.matias.token');
        $ambiente = $configFact?->ambiente ?? config('services.matias.ambiente', 'sandbox');

        if (empty($token)) {
            throw new HttpException(422, "El token de acceso de MATIAS API no está configurado (MATIAS_API_TOKEN).");
        }

        // 4. Determinar o reservar consecutivos y resolución
        $empresaId = $configFact?->configuracion_empresa_id;
        $reservaResolucion = null;

        if ($this->resolucionService) {
            try {
                $resolucion = $this->resolucionService->getResolucionByTipoDocumento('nomina_electronica', $empresaId)
                    ?? $this->resolucionService->getResolucionByTipoDocumento('nomina', $empresaId);

                if ($resolucion) {
                    $reservaResolucion = [
                        'resolution_number' => $resolucion->numero_resolucion,
                        'prefix'            => $resolucion->prefijo ?: 'NE',
                        'document_number'   => (string) $resolucion->consecutivo_actual,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning("No se pudo obtener resolución de nómina desde servicio: " . $e->getMessage());
            }
        }

        $resolutionNumber = $options['resolution_number'] ?? $reservaResolucion['resolution_number'] ?? '';
        $prefix = $options['prefix'] ?? $options['prefijo'] ?? $reservaResolucion['prefix'] ?? 'NE';
        $documentNumber = $options['document_number'] ?? $reservaResolucion['document_number'] ?? (string) $settlement->id;
        $issueDate = $options['issue_date'] ?? date('Y-m-d');

        $mapperOptions = array_merge($options, [
            'resolution_number' => $resolutionNumber,
            'prefix'            => $prefix,
            'document_number'   => $documentNumber,
            'issue_date'        => $issueDate,
        ]);

        // 5. Construir payload usando MatiasPayrollMapper
        $payload = MatiasPayrollMapper::toMatiasPayload($settlement, $mapperOptions);

        // 6. Registrar / obtener el registro preliminar en DocumentoElectronico
        $docPendiente = $documentosPrevios->firstWhere(fn ($d) => in_array($d->estado, ['pendiente', 'rechazado'], true));

        if (!$docPendiente) {
            $createDto = new CrearDocumentoElectronicoDTO(
                payrollSettlementId: $settlement->id,
                configuracionFacturacionId: $configFact?->id,
                tipoDocumento: 'nomina_electronica',
                estado: 'pendiente',
                proveedor: 'matias',
                ambiente: $ambiente,
                numeroDocumento: $documentNumber,
                prefijo: $prefix,
                mensaje: 'Preparado para transmisión de nómina electrónica',
                datosTecnicos: ['payload_preparado' => $payload]
            );

            $docPendiente = $this->documentoElectronicoService->registrarEmision($createDto);
        }

        // Marcar en estado 'enviando' durante la petición HTTP
        $this->documentoElectronicoService->marcarComoEnviando($docPendiente->id);

        try {
            // 7. Transmisión HTTP real a MATIAS API (POST /ep/payroll)
            $forceStatus = $options['force_status'] ?? null;
            $respuestaMatias = $this->matiasClient->postPayroll($payload, $token, $forceStatus);

            // 8. Procesar respuesta de MATIAS
            $exito = (bool) ($respuestaMatias['exitoso'] ?? false);
            $dataResponse = $respuestaMatias['data'] ?? [];

            $cune = $dataResponse['cune'] 
                ?? $dataResponse['cufe'] 
                ?? $dataResponse['cude'] 
                ?? $dataResponse['document_key'] 
                ?? null;

            $trackId = $dataResponse['track_id'] 
                ?? $dataResponse['uuid'] 
                ?? $dataResponse['id'] 
                ?? null;

            $numDocRespuesta = $dataResponse['number'] 
                ?? $dataResponse['document_number'] 
                ?? $documentNumber;

            $identificadorExterno = $dataResponse['uuid'] 
                ?? $dataResponse['id'] 
                ?? $trackId 
                ?? $numDocRespuesta;

            $cuneStr = is_scalar($cune) ? (string) $cune : null;
            $trackIdStr = is_scalar($trackId) ? (string) $trackId : null;
            $numDocStr = is_scalar($numDocRespuesta) ? (string) $numDocRespuesta : (string) $documentNumber;
            $idExtStr = is_scalar($identificadorExterno) ? (string) $identificadorExterno : null;

            $estadoFinal = $exito ? 'emitido' : 'rechazado';
            $mensajeRespuesta = $respuestaMatias['mensaje'] ?? ($exito ? 'Nómina emitida exitosamente' : 'Rechazada por MATIAS API');
            $erroresRespuesta = is_array($respuestaMatias['errores'] ?? null) ? $respuestaMatias['errores'] : [];

            // 9. Actualizar trazabilidad en DocumentoElectronico
            $datosTecnicosActualizados = array_merge(
                is_array($docPendiente->datos_tecnicos) ? $docPendiente->datos_tecnicos : [],
                [
                    'payload_enviado'  => $payload,
                    'respuesta_matias' => $dataResponse,
                    'http_status'      => $respuestaMatias['status'] ?? 0,
                ]
            );

            $this->documentoElectronicoService->actualizarDesdeRespuestaDS(
                $docPendiente->id,
                new \App\Modules\Facturacion\Application\DTOs\RespuestaDocumentoSoporteDTO(
                    exitoso: $exito,
                    estado: $estadoFinal,
                    identificadorExterno: $idExtStr,
                    numeroDocumento: $numDocStr,
                    cude: $cuneStr,
                    mensaje: $mensajeRespuesta,
                    errores: $erroresRespuesta,
                    datosTecnicos: $datosTecnicosActualizados
                )
            );

            return [
                'exitoso'                 => $exito,
                'estado'                  => $estadoFinal,
                'ya_transmitido'          => false,
                'documento_electronico_id' => $docPendiente->id,
                'payroll_settlement_id'   => $settlement->id,
                'numero_documento'        => $numDocStr,
                'prefijo'                 => $prefix,
                'cune'                    => $cuneStr,
                'track_id'                => $trackIdStr,
                'identificador_externo'   => $idExtStr,
                'mensaje'                 => $mensajeRespuesta,
                'errores'                 => $erroresRespuesta,
            ];
        } catch (\Throwable $e) {
            // En caso de fallo inesperado, restaurar a pendiente
            $this->documentoElectronicoService->restaurarEstadoPendiente($docPendiente->id);
            throw $e;
        }
    }

    /**
     * Validar que la liquidación cumpla las condiciones indispensables para ser transmitida.
     */
    protected function validarLiquidacionParaTransmision(PayrollSettlement $settlement): void
    {
        if ($settlement->status === 'cancelled') {
            throw new HttpException(422, "La liquidación de nómina #{$settlement->id} está cancelada y no puede ser transmitida.");
        }

        if (!$settlement->payrollPeriod) {
            throw new HttpException(422, "La liquidación de nómina #{$settlement->id} no tiene un período asociado.");
        }

        if (!$settlement->employee) {
            throw new HttpException(422, "La liquidación de nómina #{$settlement->id} no tiene un empleado asociado.");
        }

        if ($settlement->earnings->isEmpty()) {
            throw new HttpException(422, "La liquidación de nómina #{$settlement->id} no posee conceptos de devengados calculados.");
        }

        $basicEarning = $settlement->earnings->firstWhere('type_code', PayrollEarningTypeEnum::BASIC_SALARY->value);
        if (!$basicEarning) {
            throw new HttpException(422, "La liquidación de nómina #{$settlement->id} requiere el concepto obligatorio 'BASIC_SALARY'.");
        }
    }

    /**
     * Reemplazar una nómina electrónica previamente transmitida a MATIAS API (POST /ep/payroll/replace).
     *
     * @param int $settlementId ID de la liquidación de nómina.
     * @param array $options Opciones adicionales para el reemplazo.
     * @return array Resultado estructurado del reemplazo.
     */
    public function reemplazar(int $settlementId, array $options = []): array
    {
        // 1. Obtener y validar la liquidación
        $settlement = $this->payrollSettlementService->findById($settlementId);
        $settlement->loadMissing(['payrollPeriod', 'employee', 'employee.paymentAccounts', 'earnings', 'deductions']);

        $this->validarLiquidacionParaTransmision($settlement);

        // 2. Buscar el documento electrónico original transmitido exitosamente
        $documentosPrevios = $this->documentoElectronicoService->obtenerPorPayrollSettlement($settlementId);
        $docOriginal = $documentosPrevios->firstWhere(fn ($d) => in_array($d->estado, ['emitido', 'transmitido', 'aprobado', 'reemplazado'], true));

        if (!$docOriginal || empty($docOriginal->cufe)) {
            throw new HttpException(422, "No existe una nómina electrónica previamente emitida y con CUNE válido para la liquidación #{$settlementId} que permita realizar un reemplazo.");
        }

        if ($docOriginal->estado === 'eliminado') {
            throw new HttpException(422, "La nómina electrónica de la liquidación #{$settlementId} se encuentra eliminada/anulada y no se puede reemplazar.");
        }

        // 3. Obtener token y ambiente de MATIAS
        $configFact = $this->configuracionRepository?->first();
        $token = $configFact?->getEffectiveMatiasToken() ?? config('services.matias.token');
        $ambiente = $configFact?->ambiente ?? config('services.matias.ambiente', 'sandbox');

        if (empty($token)) {
            throw new HttpException(422, "El token de acceso de MATIAS API no está configurado (MATIAS_API_TOKEN).");
        }

        // 4. Construir el objeto replacing_predecessor con datos del documento original
        $generationDate = $docOriginal->created_at ? $docOriginal->created_at->format('Y-m-d') : date('Y-m-d');
        $replacingPredecessor = [
            'number'          => (string) $docOriginal->numero_documento,
            'cune'            => (string) $docOriginal->cufe,
            'generation_date' => (string) ($options['generation_date'] ?? $generationDate),
        ];

        // 5. Determinar consecutivo y resolución para el reemplazo
        $prefix = $options['prefix'] ?? $options['prefijo'] ?? $docOriginal->prefijo ?? 'NE';
        $resolutionNumber = $options['resolution_number'] ?? $options['resolution'] ?? '';
        $documentNumber = $options['document_number'] ?? (string) $docOriginal->numero_documento;
        $issueDate = $options['issue_date'] ?? date('Y-m-d');

        $mapperOptions = array_merge($options, [
            'resolution_number'     => $resolutionNumber,
            'prefix'                => $prefix,
            'document_number'       => $documentNumber,
            'issue_date'            => $issueDate,
            'replacing_predecessor' => $replacingPredecessor,
        ]);

        // 6. Construir payload usando MatiasPayrollMapper (incluye replacing_predecessor)
        $payload = MatiasPayrollMapper::toMatiasPayload($settlement, $mapperOptions);

        // 7. Registrar documento de reemplazo preliminar
        $createDto = new CrearDocumentoElectronicoDTO(
            payrollSettlementId: $settlement->id,
            configuracionFacturacionId: $configFact?->id,
            tipoDocumento: 'nomina_electronica_reemplazo',
            estado: 'pendiente',
            proveedor: 'matias',
            ambiente: $ambiente,
            numeroDocumento: $documentNumber,
            prefijo: $prefix,
            mensaje: 'Preparado para reemplazo de nómina electrónica',
            datosTecnicos: [
                'documento_original_id' => $docOriginal->id,
                'replacing_predecessor' => $replacingPredecessor,
                'payload_preparado'     => $payload,
            ]
        );

        $docReemplazo = $this->documentoElectronicoService->registrarEmision($createDto);
        $this->documentoElectronicoService->marcarComoEnviando($docReemplazo->id);

        try {
            // 8. Transmisión HTTP real a MATIAS API (POST /ep/payroll/replace)
            $forceStatus = $options['force_status'] ?? null;
            $respuestaMatias = $this->matiasClient->replacePayroll($payload, $token, $forceStatus);

            $exito = (bool) ($respuestaMatias['exitoso'] ?? false);
            $dataResponse = $respuestaMatias['data'] ?? [];

            $cune = $dataResponse['cune'] 
                ?? $dataResponse['cufe'] 
                ?? $dataResponse['cude'] 
                ?? $dataResponse['document_key'] 
                ?? null;

            $trackId = $dataResponse['track_id'] 
                ?? $dataResponse['uuid'] 
                ?? $dataResponse['id'] 
                ?? null;

            $numDocRespuesta = $dataResponse['number'] 
                ?? $dataResponse['document_number'] 
                ?? $documentNumber;

            $identificadorExterno = $dataResponse['uuid'] 
                ?? $dataResponse['id'] 
                ?? $trackId 
                ?? $numDocRespuesta;

            $cuneStr = is_scalar($cune) ? (string) $cune : null;
            $trackIdStr = is_scalar($trackId) ? (string) $trackId : null;
            $numDocStr = is_scalar($numDocRespuesta) ? (string) $numDocRespuesta : (string) $documentNumber;
            $idExtStr = is_scalar($identificadorExterno) ? (string) $identificadorExterno : null;

            $estadoFinal = $exito ? 'emitido' : 'rechazado';
            $mensajeRespuesta = $respuestaMatias['mensaje'] ?? ($exito ? 'Nómina reemplazada exitosamente' : 'Reemplazo rechazado por MATIAS API');
            $erroresRespuesta = !empty($respuestaMatias['errores']) ? $respuestaMatias['errores'] : null;

            // 9. Actualizar registro del reemplazo
            $datosTecnicosActualizados = [
                'documento_original_id' => $docOriginal->id,
                'replacing_predecessor' => $replacingPredecessor,
                'payload_enviado'       => $payload,
                'respuesta_matias'      => $dataResponse,
                'http_status'           => $respuestaMatias['status'] ?? 0,
            ];

            $this->documentoElectronicoService->actualizarDesdeRespuestaDS(
                $docReemplazo->id,
                new \App\Modules\Facturacion\Application\DTOs\RespuestaDocumentoSoporteDTO(
                    exitoso: $exito,
                    estado: $estadoFinal,
                    identificadorExterno: $idExtStr,
                    numeroDocumento: $numDocStr,
                    cude: $cuneStr,
                    mensaje: $mensajeRespuesta,
                    errores: $erroresRespuesta,
                    datosTecnicos: $datosTecnicosActualizados
                )
            );

            // 10. Si el reemplazo fue exitoso, actualizar el estado del documento original a 'reemplazado'
            if ($exito) {
                $docOriginal->update(['estado' => 'reemplazado']);
            }

            return [
                'exitoso'                 => $exito,
                'estado'                  => $estadoFinal,
                'tipo_operacion'          => 'reemplazo',
                'documento_electronico_id' => $docReemplazo->id,
                'documento_original_id'   => $docOriginal->id,
                'payroll_settlement_id'   => $settlement->id,
                'numero_documento'        => $numDocStr,
                'prefijo'                 => $prefix,
                'cune'                    => $cuneStr,
                'track_id'                => $trackIdStr,
                'identificador_externo'   => $idExtStr,
                'mensaje'                 => $mensajeRespuesta,
                'errores'                 => $erroresRespuesta,
            ];
        } catch (\Throwable $e) {
            $this->documentoElectronicoService->restaurarEstadoPendiente($docReemplazo->id);
            throw $e;
        }
    }

    /**
     * Solicitar la eliminación/anulación de una nómina electrónica previamente transmitida (POST /ep/payroll/delete).
     *
     * @param int $settlementId ID de la liquidación de nómina.
     * @param array $options Opciones adicionales para la eliminación.
     * @return array Resultado estructurado de la eliminación.
     */
    public function eliminar(int $settlementId, array $options = []): array
    {
        // 1. Obtener y validar la liquidación
        $settlement = $this->payrollSettlementService->findById($settlementId);

        // 2. Buscar el documento electrónico original emitido
        $documentosPrevios = $this->documentoElectronicoService->obtenerPorPayrollSettlement($settlementId);
        $docOriginal = $documentosPrevios->firstWhere(fn ($d) => in_array($d->estado, ['emitido', 'transmitido', 'aprobado', 'reemplazado'], true));

        if (!$docOriginal || empty($docOriginal->cufe)) {
            // Verificar si ya fue eliminado previamente
            $docEliminado = $documentosPrevios->firstWhere('estado', 'eliminado');
            if ($docEliminado) {
                return [
                    'exitoso'                 => true,
                    'estado'                  => 'eliminado',
                    'ya_eliminado'            => true,
                    'documento_electronico_id' => $docEliminado->id,
                    'payroll_settlement_id'   => $settlement->id,
                    'numero_documento'        => $docEliminado->numero_documento,
                    'prefijo'                 => $docEliminado->prefijo,
                    'cune'                    => $docEliminado->cufe,
                    'mensaje'                 => 'La nómina electrónica ya fue eliminada/anulada previamente.',
                    'errores'                 => null,
                ];
            }

            throw new HttpException(422, "No existe una nómina electrónica emitida y con CUNE válido para la liquidación #{$settlementId} que permita solicitar eliminación.");
        }

        // 3. Obtener token y ambiente de MATIAS
        $configFact = $this->configuracionRepository?->first();
        $token = $configFact?->getEffectiveMatiasToken() ?? config('services.matias.token');
        $ambiente = $configFact?->ambiente ?? config('services.matias.ambiente', 'sandbox');

        if (empty($token)) {
            throw new HttpException(422, "El token de acceso de MATIAS API no está configurado (MATIAS_API_TOKEN).");
        }

        // 4. Construir deleting_predecessor
        $generationDate = $docOriginal->created_at ? $docOriginal->created_at->format('Y-m-d') : date('Y-m-d');
        $deletingPredecessor = [
            'number'          => (string) $docOriginal->numero_documento,
            'cune'            => (string) $docOriginal->cufe,
            'generation_date' => (string) ($options['generation_date'] ?? $generationDate),
        ];

        // 5. Construir payload de eliminación
        $prefix = $options['prefix'] ?? $options['prefijo'] ?? $docOriginal->prefijo ?? 'NE';
        $resolutionNumber = $options['resolution_number'] ?? $options['resolution'] ?? '';
        $documentNumber = $options['document_number'] ?? (string) $docOriginal->numero_documento;

        $payload = [
            'prefix'               => (string) $prefix,
            'resolution_number'    => (string) $resolutionNumber,
            'document_number'      => (string) $documentNumber,
            'notes'                => (string) ($options['notes'] ?? 'Eliminación / Anulación de Nómina Electrónica'),
            'deleting_predecessor' => $deletingPredecessor,
        ];

        // 6. Registrar documento de eliminación preliminar
        $createDto = new CrearDocumentoElectronicoDTO(
            payrollSettlementId: $settlement->id,
            configuracionFacturacionId: $configFact?->id,
            tipoDocumento: 'nomina_electronica_eliminacion',
            estado: 'pendiente',
            proveedor: 'matias',
            ambiente: $ambiente,
            numeroDocumento: $documentNumber,
            prefijo: $prefix,
            mensaje: 'Preparado para eliminación de nómina electrónica',
            datosTecnicos: [
                'documento_original_id' => $docOriginal->id,
                'deleting_predecessor'  => $deletingPredecessor,
                'payload_preparado'     => $payload,
            ]
        );

        $docEliminacion = $this->documentoElectronicoService->registrarEmision($createDto);
        $this->documentoElectronicoService->marcarComoEnviando($docEliminacion->id);

        try {
            // 7. Transmisión HTTP real a MATIAS API (POST /ep/payroll/delete)
            $forceStatus = $options['force_status'] ?? null;
            $respuestaMatias = $this->matiasClient->deletePayroll($payload, $token, $forceStatus);

            $exito = (bool) ($respuestaMatias['exitoso'] ?? false);
            $dataResponse = $respuestaMatias['data'] ?? [];

            $cune = $dataResponse['cune'] 
                ?? $dataResponse['cufe'] 
                ?? $dataResponse['cude'] 
                ?? $dataResponse['document_key'] 
                ?? null;

            $trackId = $dataResponse['track_id'] 
                ?? $dataResponse['uuid'] 
                ?? $dataResponse['id'] 
                ?? null;

            $numDocRespuesta = $dataResponse['number'] 
                ?? $dataResponse['document_number'] 
                ?? $documentNumber;

            $identificadorExterno = $dataResponse['uuid'] 
                ?? $dataResponse['id'] 
                ?? $trackId 
                ?? $numDocRespuesta;

            $cuneStr = is_scalar($cune) ? (string) $cune : null;
            $trackIdStr = is_scalar($trackId) ? (string) $trackId : null;
            $numDocStr = is_scalar($numDocRespuesta) ? (string) $numDocRespuesta : (string) $documentNumber;
            $idExtStr = is_scalar($identificadorExterno) ? (string) $identificadorExterno : null;

            $estadoFinal = $exito ? 'emitido' : 'rechazado';
            $mensajeRespuesta = $respuestaMatias['mensaje'] ?? ($exito ? 'Nómina eliminada/anulada exitosamente' : 'Eliminación rechazada por MATIAS API');
            $erroresRespuesta = !empty($respuestaMatias['errores']) ? $respuestaMatias['errores'] : null;

            // 8. Actualizar registro de la eliminación
            $datosTecnicosActualizados = [
                'documento_original_id' => $docOriginal->id,
                'deleting_predecessor'  => $deletingPredecessor,
                'payload_enviado'       => $payload,
                'respuesta_matias'      => $dataResponse,
                'http_status'           => $respuestaMatias['status'] ?? 0,
            ];

            $this->documentoElectronicoService->actualizarDesdeRespuestaDS(
                $docEliminacion->id,
                new \App\Modules\Facturacion\Application\DTOs\RespuestaDocumentoSoporteDTO(
                    exitoso: $exito,
                    estado: $estadoFinal,
                    identificadorExterno: $idExtStr,
                    numeroDocumento: $numDocStr,
                    cude: $cuneStr,
                    mensaje: $mensajeRespuesta,
                    errores: $erroresRespuesta,
                    datosTecnicos: $datosTecnicosActualizados
                )
            );

            // 9. Si la eliminación fue exitosa, actualizar estado del documento original a 'eliminado'
            if ($exito) {
                $docOriginal->update(['estado' => 'eliminado']);
            }

            return [
                'exitoso'                 => $exito,
                'estado'                  => $exito ? 'eliminado' : 'rechazado',
                'tipo_operacion'          => 'eliminacion',
                'documento_electronico_id' => $docEliminacion->id,
                'documento_original_id'   => $docOriginal->id,
                'payroll_settlement_id'   => $settlement->id,
                'numero_documento'        => $numDocStr,
                'prefijo'                 => $prefix,
                'cune'                    => $cuneStr,
                'track_id'                => $trackIdStr,
                'identificador_externo'   => $idExtStr,
                'mensaje'                 => $mensajeRespuesta,
                'errores'                 => $erroresRespuesta,
            ];
        } catch (\Throwable $e) {
            $this->documentoElectronicoService->restaurarEstadoPendiente($docEliminacion->id);
            throw $e;
        }
    }
}
