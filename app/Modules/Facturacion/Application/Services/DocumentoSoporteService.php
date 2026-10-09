<?php

namespace App\Modules\Facturacion\Application\Services;

use App\Models\Compra;
use App\Models\Gasto;
use App\Models\DocumentoElectronico;
use Illuminate\Support\Facades\DB;
use App\Modules\Facturacion\Application\DTOs\SolicitudDocumentoSoporteDTO;
use App\Modules\Facturacion\Application\DTOs\RespuestaDocumentoSoporteDTO;
use App\Modules\Facturacion\Application\DTOs\CrearDocumentoElectronicoDTO;
use App\Modules\Facturacion\Infrastructure\Managers\FacturacionManager;
use App\Modules\Facturacion\Infrastructure\Mappers\MatiasSupportDocumentMapper;
use App\Modules\ConfiguracionFacturacion\Application\Interfaces\ConfiguracionFacturacionRepositoryInterface;
use App\Modules\Compras\Application\Services\CompraService;
use App\Modules\Gastos\Application\Services\GastoService;
use App\Modules\ResolucionesFacturacion\Application\Services\ResolucionFacturacionService;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Servicio de Aplicación de Documento Soporte Electrónico (DS).
 * Orquesta la validación, prevención de duplicados, reserva atómica de consecutivo,
 * construcción del DTO y payload de MATIAS API, persistencia de DocumentoElectronico,
 * y la transmisión técnica a MATIAS API (POST /ds/document).
 */
class DocumentoSoporteService
{
    public function __construct(
        protected ConfiguracionFacturacionRepositoryInterface $configuracionRepository,
        protected FacturacionManager $facturacionManager,
        protected CompraService $compraService,
        protected GastoService $gastoService,
        protected ResolucionFacturacionService $resolucionService,
        protected DocumentoElectronicoService $documentoElectronicoService
    ) {}

    /**
     * Orquestar la preparación y persistencia del Documento Soporte Electrónico por Origen (Compra o Gasto).
     * Nota: Esta función NO ejecuta la transmisión HTTP externa a MATIAS API (POST /ds/document).
     * El documento queda persistido en estado 'pendiente' listo para una futura transmisión.
     */
    public function prepararEmisionPorOrigen(string $origenTipo, int $origenId): RespuestaDocumentoSoporteDTO
    {
        $origenTipoNormalized = strtolower(trim($origenTipo));

        if (!in_array($origenTipoNormalized, ['compra', 'gasto'], true)) {
            throw new HttpException(422, "El tipo de origen '{$origenTipo}' no es válido. Debe ser 'compra' o 'gasto'.");
        }

        return DB::transaction(function () use ($origenTipoNormalized, $origenId) {
            $config = $this->configuracionRepository->first();

            if (!$config || !$config->facturacion_electronica_activa) {
                throw new HttpException(422, "La facturación electrónica / documento soporte se encuentra desactivada en la configuración del ERP.");
            }

            $providerName = $config->proveedor_activo ?? 'matias';
            $ambiente = $config->ambiente ?? 'sandbox';

            // 1. Obtener y validar el modelo de Origen con bloqueo pesimista (lockForUpdate)
            if ($origenTipoNormalized === 'compra') {
                $compra = Compra::where('id', $origenId)->lockForUpdate()->first();

                if (!$compra) {
                    throw new HttpException(404, "Compra no encontrada.");
                }

                if (!(bool) $compra->documento_soporte_electronico) {
                    throw new HttpException(422, "La compra #{$origenId} no está marcada para emitir Documento Soporte Electrónico.");
                }

                if (!$compra->proveedor_id || !$compra->proveedor) {
                    throw new HttpException(422, "La compra #{$origenId} no tiene un proveedor válido asociado para emitir Documento Soporte Electrónico.");
                }

                $proveedor = $compra->proveedor;
                if (empty($proveedor->nit) || empty($proveedor->nombre)) {
                    throw new HttpException(422, "El proveedor asociado a la compra #{$origenId} no cuenta con datos fiscales obligatorios (NIT y Nombre).");
                }

                // Prevención de duplicados (bajo el bloqueo pesimista del origen)
                $existentes = $this->documentoElectronicoService->obtenerPorCompra($origenId);
                foreach ($existentes as $docExistente) {
                    if (in_array($docExistente->estado, ['emitido', 'pendiente', 'enviando'], true)) {
                        throw new HttpException(422, "La compra #{$origenId} ya posee un Documento Soporte Electrónico en estado '{$docExistente->estado}'.");
                    }
                }

                $solicitudBase = $this->compraService->obtenerSolicitudDocumentoSoporte($origenId);
            } else {
                $gasto = Gasto::where('id', $origenId)->lockForUpdate()->first();

                if (!$gasto) {
                    throw new HttpException(404, "Gasto no encontrado.");
                }

                if (!(bool) $gasto->documento_soporte_electronico) {
                    throw new HttpException(422, "El gasto #{$origenId} no está marcado para emitir Documento Soporte Electrónico.");
                }

                if (!$gasto->proveedor_id || !$gasto->proveedor) {
                    throw new HttpException(422, "El gasto #{$origenId} no tiene un proveedor válido asociado para emitir Documento Soporte Electrónico.");
                }

                $proveedor = $gasto->proveedor;
                if (empty($proveedor->nit) || empty($proveedor->nombre)) {
                    throw new HttpException(422, "El proveedor asociado al gasto #{$origenId} no cuenta con datos fiscales obligatorios (NIT y Nombre).");
                }

                // Prevención de duplicados (bajo el bloqueo pesimista del origen)
                $existentes = $this->documentoElectronicoService->obtenerPorGasto($origenId);
                foreach ($existentes as $docExistente) {
                    if (in_array($docExistente->estado, ['emitido', 'pendiente', 'enviando'], true)) {
                        throw new HttpException(422, "El gasto #{$origenId} ya posee un Documento Soporte Electrónico en estado '{$docExistente->estado}'.");
                    }
                }

                $solicitudBase = $this->gastoService->obtenerSolicitudDocumentoSoporte($origenId);
            }

            // 2. Reserva atómica del consecutivo DS (dentro del contexto transaccional global)
            $empresaId = $config->configuracion_empresa_id ?? null;
            $reserva = $this->resolucionService->reservarConsecutivoDocumentoSoporte($empresaId);

            // 3. Reconstruir SolicitudDocumentoSoporteDTO asociando el consecutivo reservado
            $solicitud = new SolicitudDocumentoSoporteDTO(
                compraId: $solicitudBase->compraId,
                gastoId: $solicitudBase->gastoId,
                proveedorId: $solicitudBase->proveedorId,
                prefijo: $reserva['prefijo'],
                folio: (string) $reserva['consecutivo'],
                fechaEmision: $solicitudBase->fechaEmision,
                tipoDocumento: 'documento_soporte',
                proveedor: $solicitudBase->proveedor,
                emisor: $solicitudBase->emisor,
                items: $solicitudBase->items,
                totales: $solicitudBase->totales,
                medioPago: $solicitudBase->medioPago,
                observacion: $solicitudBase->observacion,
                metadatos: array_merge($solicitudBase->metadatos, [
                    'resolution_number' => $reserva['numero_resolucion'],
                    'prefix' => $reserva['prefijo'],
                    'resolucion_id' => $reserva['resolucion_id'],
                    'type_document_id' => 11,
                ])
            );

            // 4. Construir payload oficial de MATIAS API
            $payloadMatias = MatiasSupportDocumentMapper::toMatiasPayload($solicitud);

            // 5. Crear y persistir el registro de DocumentoElectronico
            $dtoPersistencia = new CrearDocumentoElectronicoDTO(
                ventaId: null,
                compraId: $solicitud->compraId,
                gastoId: $solicitud->gastoId,
                proveedorId: $solicitud->proveedorId,
                configuracionFacturacionId: $config->id ?? null,
                tipoDocumento: 'documento_soporte',
                estado: 'pendiente',
                proveedor: $providerName,
                ambiente: $ambiente,
                identificadorExterno: null,
                cufe: null,
                numeroDocumento: (string) $reserva['consecutivo'],
                prefijo: $reserva['prefijo'],
                trackId: null,
                mensaje: 'Documento Soporte preparado exitosamente y pendiente de transmisión.',
                errores: null,
                datosTecnicos: [
                    'payload_matias' => $payloadMatias,
                    'reserva' => $reserva,
                ]
            );

            $docElectronico = $this->documentoElectronicoService->registrarEmision($dtoPersistencia);

            // 6. Retornar RespuestaDocumentoSoporteDTO en estado pendiente (SIN transmisión HTTP)
            return RespuestaDocumentoSoporteDTO::exitoso(
                estado: 'pendiente',
                identificadorExterno: null,
                numeroDocumento: (string) $reserva['consecutivo'],
                cude: null,
                qrCode: null,
                pdfUrl: null,
                xmlUrl: null,
                mensaje: 'Documento Soporte preparado exitosamente y pendiente de transmisión.',
                datosTecnicos: [
                    'documento_electronico_id' => $docElectronico->id,
                    'reserva' => $reserva,
                    'payload_matias' => $payloadMatias,
                ]
            );
        });
    }

    /**
     * Transmitir un Documento Soporte Electrónico persistido en estado 'pendiente' a MATIAS API (POST /ds/document).
     *
     * @param int $documentoElectronicoId ID del registro en documentos_electronicos.
     * @param bool $ejecutarHttpReal Control de seguridad. Durante la FASE 13.2 es false para prevenir transmisiones HTTP accidentales.
     * @return RespuestaDocumentoSoporteDTO
     */
    public function transmitirDocumentoPendiente(int $documentoElectronicoId, ?bool $ejecutarHttpReal = null): RespuestaDocumentoSoporteDTO
    {
        // 1. Recuperar el DocumentoElectronico persistido
        $doc = $this->documentoElectronicoService->obtenerPorId($documentoElectronicoId);

        if (!$doc) {
            throw new HttpException(404, "El Documento Electrónico #{$documentoElectronicoId} no fue encontrado.");
        }

        // 2. Validar que sea un Documento Soporte
        if ($doc->tipo_documento !== 'documento_soporte') {
            throw new HttpException(422, "El documento electrónico #{$documentoElectronicoId} no corresponde a un Documento Soporte (tipo: '{$doc->tipo_documento}').");
        }

        // 3. Idempotencia & Exclusión Concurrente
        if ($doc->estado === 'emitido') {
            if ($doc->compra_id) {
                $this->compraService->actualizarEstadoDocumentoSoporte($doc->compra_id, 'emitido');
            } elseif ($doc->gasto_id) {
                $this->gastoService->actualizarEstadoDocumentoSoporte($doc->gasto_id, 'emitido');
            }

            return RespuestaDocumentoSoporteDTO::exitoso(
                estado: 'emitido',
                identificadorExterno: $doc->identificador_externo,
                numeroDocumento: $doc->numero_documento,
                cude: $doc->cufe,
                mensaje: 'El Documento Soporte Electrónico ya fue emitido previamente.',
                datosTecnicos: $doc->datos_tecnicos ?? []
            );
        }

        if ($doc->estado === 'enviando') {
            throw new HttpException(409, "El Documento Electrónico #{$documentoElectronicoId} ya se encuentra en proceso de transmisión por otra solicitud simultánea.");
        }

        if (!in_array($doc->estado, ['pendiente', 'rechazado'], true)) {
            throw new HttpException(422, "Solo se pueden transmitir documentos en estado 'pendiente' o 'rechazado'. El documento #{$documentoElectronicoId} se encuentra en estado '{$doc->estado}'.");
        }

        // 4. Recuperar origen (Compra o Gasto) y reconstruir DTO con consecutivo guardado
        $solicitudBase = null;
        if ($doc->compra_id) {
            $solicitudBase = $this->compraService->obtenerSolicitudDocumentoSoporte($doc->compra_id);
        } elseif ($doc->gasto_id) {
            $solicitudBase = $this->gastoService->obtenerSolicitudDocumentoSoporte($doc->gasto_id);
        } else {
            throw new HttpException(422, "El documento electrónico #{$documentoElectronicoId} no tiene un origen (Compra o Gasto) asociado.");
        }

        // Obtener el número de resolución y ID de resolución sin incrementar ni reservar un nuevo consecutivo
        $resolutionNumber = $doc->datos_tecnicos['reserva']['numero_resolucion']
            ?? $doc->datos_tecnicos['payload_matias']['resolution_number']
            ?? null;
        $resolucionId = $doc->datos_tecnicos['reserva']['resolucion_id'] ?? null;

        if (empty($resolutionNumber)) {
            $config = $this->configuracionRepository->first();
            $empresaId = $config?->configuracion_empresa_id;
            $resolucionActiva = $this->resolucionService->getResolucionByTipoDocumento('documento_soporte', $empresaId);
            if ($resolucionActiva) {
                $resolutionNumber = $resolucionActiva->numero_resolucion;
                $resolucionId = $resolucionId ?? $resolucionActiva->id;
            }
        }

        $solicitudDTO = new SolicitudDocumentoSoporteDTO(
            compraId: $solicitudBase->compraId,
            gastoId: $solicitudBase->gastoId,
            proveedorId: $solicitudBase->proveedorId,
            prefijo: $doc->prefijo,
            folio: (string) $doc->numero_documento,
            fechaEmision: $solicitudBase->fechaEmision,
            tipoDocumento: 'documento_soporte',
            proveedor: $solicitudBase->proveedor,
            emisor: $solicitudBase->emisor,
            items: $solicitudBase->items,
            totales: $solicitudBase->totales,
            medioPago: $solicitudBase->medioPago,
            observacion: $solicitudBase->observacion,
            metadatos: array_merge($solicitudBase->metadatos, [
                'resolution_number' => $resolutionNumber,
                'prefix' => $doc->prefijo,
                'resolucion_id' => $resolucionId,
                'type_document_id' => 11,
            ])
        );

        // 5. Control de Ejecución HTTP Real (por servidor/backend)
        $configFact = $this->configuracionRepository->first();
        $debeEjecutarHttpReal = $ejecutarHttpReal ?? ($configFact ? $configFact->getEffectiveMatiasDsRealTransmission() : (bool) config('services.matias.ds_real_transmission', false));

        if (!$debeEjecutarHttpReal) {
            return RespuestaDocumentoSoporteDTO::exitoso(
                estado: 'pendiente',
                identificadorExterno: $doc->identificador_externo,
                numeroDocumento: $doc->numero_documento,
                cude: $doc->cufe,
                mensaje: 'Flujo de transmisión a MATIAS API (POST /ds/document) y exclusión concurrente verificados. Transmisión HTTP inactiva según configuración del servidor.',
                datosTecnicos: [
                    'documento_electronico_id' => $doc->id,
                    'compra_id' => $doc->compra_id,
                    'gasto_id' => $doc->gasto_id,
                    'transmision_preparada' => true,
                    'exclusion_concurrente_activa' => true,
                    'ejecucion_http' => false,
                    'payload_matias' => MatiasSupportDocumentMapper::toMatiasPayload($solicitudDTO),
                ]
            );
        }

        // 6. Transición Atómica de Exclusión Concurrente (Sólo cuando $ejecutarHttpReal === true)
        $obtuvioExclusividad = $this->documentoElectronicoService->marcarComoEnviando($doc->id);
        if (!$obtuvioExclusividad) {
            throw new HttpException(409, "El Documento Electrónico #{$doc->id} ya fue reclamado para transmisión por otra solicitud simultánea.");
        }

        try {
            // 7. Transmisión Real a MATIAS API (sin transacción SQL abierta)
            $respuesta = $this->emitirDocumentoSoporte($solicitudDTO);

            // 8. Persistir respuesta y actualizar estado en documentos_electronicos ('emitido' o 'rechazado')
            $this->documentoElectronicoService->actualizarDesdeRespuestaDS($doc->id, $respuesta);

            // 9. Actualizar estado_documento_soporte en la Compra o Gasto de origen si la emisión fue exitosa
            if ($respuesta->exitoso && ($respuesta->estado === 'emitido' || $respuesta->estado === 'aprobado')) {
                if ($doc->compra_id) {
                    $this->compraService->actualizarEstadoDocumentoSoporte($doc->compra_id, 'emitido');
                } elseif ($doc->gasto_id) {
                    $this->gastoService->actualizarEstadoDocumentoSoporte($doc->gasto_id, 'emitido');
                }
            }

            return $respuesta;
        } catch (\Throwable $e) {
            // Ante cualquier falla técnica de red o excepción inesperada, restaurar estado a 'pendiente'
            $this->documentoElectronicoService->restaurarEstadoPendiente($doc->id);
            throw $e;
        }
    }

    /**
     * Procesar la transmisión técnica de un Documento Soporte Electrónico a MATIAS API.
     */
    public function emitirDocumentoSoporte(SolicitudDocumentoSoporteDTO $solicitud): RespuestaDocumentoSoporteDTO
    {
        $config = $this->configuracionRepository->first();

        if (!$config || !$config->facturacion_electronica_activa) {
            return RespuestaDocumentoSoporteDTO::error(
                mensaje: "La facturación electrónica / documento soporte se encuentra desactivada en la configuración del ERP.",
                estado: 'desactivado'
            );
        }

        $providerName = $config->proveedor_activo ?? 'matias';
        $ambiente = $config->ambiente ?? 'sandbox';

        $provider = $this->facturacionManager->make($providerName, $ambiente);

        if (!method_exists($provider, 'emitirDocumentoSoporte')) {
            return RespuestaDocumentoSoporteDTO::error(
                mensaje: "El proveedor de facturación seleccionado ({$providerName}) no implementa la emisión de Documento Soporte.",
                estado: 'no_soportado'
            );
        }

        return $provider->emitirDocumentoSoporte($solicitud);
    }

    /**
     * Reintentar la transmisión de un Documento Soporte Electrónico en estado 'rechazado' (o 'pendiente').
     *
     * @param int $documentoElectronicoId ID del registro en documentos_electronicos.
     * @param bool|null $ejecutarHttpReal Control de seguridad opcional.
     * @return RespuestaDocumentoSoporteDTO
     */
    public function reintentarDocumentoRechazado(int $documentoElectronicoId, ?bool $ejecutarHttpReal = null): RespuestaDocumentoSoporteDTO
    {
        $doc = $this->documentoElectronicoService->obtenerPorId($documentoElectronicoId);

        if (!$doc) {
            throw new HttpException(404, "El Documento Electrónico #{$documentoElectronicoId} no fue encontrado.");
        }

        if (!in_array($doc->estado, ['rechazado', 'pendiente'], true)) {
            throw new HttpException(422, "Solo se pueden reintentar documentos en estado 'rechazado' o 'pendiente'. El documento #{$documentoElectronicoId} se encuentra en estado '{$doc->estado}'.");
        }

        return $this->transmitirDocumentoPendiente($documentoElectronicoId, $ejecutarHttpReal);
    }

    /**
     * Reintentar masivamente la transmisión de todos los Documentos Soporte Electrónicos en estado 'rechazado'.
     *
     * @return array
     */
    public function reintentarMasivoDocumentosRechazados(): array
    {
        $rechazados = DocumentoElectronico::where('tipo_documento', 'documento_soporte')
            ->where('estado', 'rechazado')
            ->orderBy('id', 'asc')
            ->get();

        $totalEncontrados = $rechazados->count();

        if ($totalEncontrados === 0) {
            return [
                'total_encontrados' => 0,
                'procesados' => 0,
                'emitidos' => 0,
                'rechazados' => 0,
                'pendientes' => 0,
                'detalles' => [],
            ];
        }

        $procesados = 0;
        $emitidos = 0;
        $rechazadosCount = 0;
        $pendientesCount = 0;
        $detalles = [];

        foreach ($rechazados as $doc) {
            $estadoAnterior = $doc->estado;

            try {
                $respuesta = $this->reintentarDocumentoRechazado($doc->id);
                $procesados++;

                $estadoFinal = $respuesta->estado ?? 'desconocido';

                if ($respuesta->exitoso && in_array($estadoFinal, ['emitido', 'aprobado'], true)) {
                    $emitidos++;
                } elseif ($estadoFinal === 'pendiente') {
                    $pendientesCount++;
                } else {
                    $rechazadosCount++;
                }

                $detalles[] = [
                    'documento_electronico_id' => $doc->id,
                    'estado_anterior' => $estadoAnterior,
                    'estado_final' => $estadoFinal,
                    'exitoso' => (bool) $respuesta->exitoso,
                    'mensaje' => $respuesta->mensaje,
                ];
            } catch (\Throwable $e) {
                $procesados++;

                $docActual = $this->documentoElectronicoService->obtenerPorId($doc->id);
                $estadoFinal = $docActual ? $docActual->estado : 'rechazado';

                if ($estadoFinal === 'pendiente') {
                    $pendientesCount++;
                } elseif ($estadoFinal === 'emitido' || $estadoFinal === 'aprobado') {
                    $emitidos++;
                } else {
                    $rechazadosCount++;
                }

                $detalles[] = [
                    'documento_electronico_id' => $doc->id,
                    'estado_anterior' => $estadoAnterior,
                    'estado_final' => $estadoFinal,
                    'exitoso' => false,
                    'mensaje' => $e->getMessage(),
                ];
            }
        }

        return [
            'total_encontrados' => $totalEncontrados,
            'procesados' => $procesados,
            'emitidos' => $emitidos,
            'rechazados' => $rechazadosCount,
            'pendientes' => $pendientesCount,
            'detalles' => $detalles,
        ];
    }

    /**
     * Procesar automáticamente la preparación y transmisión de un Documento Soporte Electrónico.
     * Diseñado para ejecutarse tras guardar o confirmar una Compra o Gasto.
     * Es idempotente y no afecta ni revierte la transacción del origen ante fallas técnicas.
     *
     * @param string $origenTipo 'compra' o 'gasto'
     * @param int $origenId ID de la Compra o Gasto
     * @return RespuestaDocumentoSoporteDTO|null
     */
    public function procesarEmisionAutomatica(string $origenTipo, int $origenId): ?RespuestaDocumentoSoporteDTO
    {
        try {
            $origenTipoNormalized = strtolower(trim($origenTipo));
            $existentes = ($origenTipoNormalized === 'compra')
                ? $this->documentoElectronicoService->obtenerPorCompra($origenId)
                : $this->documentoElectronicoService->obtenerPorGasto($origenId);

            // 1. Si ya existe un documento emitido previamente, no hacer nada (idempotencia)
            foreach ($existentes as $docExistente) {
                if ($docExistente->estado === 'emitido') {
                    return null;
                }
            }

            // 2. Si ya existe un documento en estado 'pendiente', reusar su ID
            $docPendiente = null;
            foreach ($existentes as $docExistente) {
                if ($docExistente->estado === 'pendiente') {
                    $docPendiente = $docExistente;
                    break;
                }
            }

            $docId = null;

            if ($docPendiente) {
                $docId = $docPendiente->id;
            } else {
                // Preparar nuevo DocumentoElectronico (reserva consecutivo y construye payload)
                $respuestaPrep = $this->prepararEmisionPorOrigen($origenTipoNormalized, $origenId);
                $docId = $respuestaPrep->datosTecnicos['documento_electronico_id'] ?? null;
            }

            if (!$docId) {
                return null;
            }

            // 3. Transmitir a MATIAS API (respetando la configuración real o de prueba activa)
            return $this->transmitirDocumentoPendiente($docId);

        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }
}
