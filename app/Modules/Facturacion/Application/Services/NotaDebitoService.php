<?php

namespace App\Modules\Facturacion\Application\Services;

use App\Models\Venta;
use App\Models\DocumentoElectronico;
use Illuminate\Support\Facades\DB;
use App\Modules\Facturacion\Application\DTOs\SolicitudNotaDebitoDTO;
use App\Modules\Facturacion\Application\DTOs\RespuestaNotaDebitoDTO;
use App\Modules\Facturacion\Application\DTOs\CrearDocumentoElectronicoDTO;
use App\Modules\Facturacion\Infrastructure\Managers\FacturacionManager;
use App\Modules\Facturacion\Infrastructure\Mappers\MatiasDebitNoteMapper;
use App\Modules\ConfiguracionFacturacion\Application\Interfaces\ConfiguracionFacturacionRepositoryInterface;
use App\Modules\ConfiguracionEmpresa\Application\Interfaces\ConfiguracionEmpresaRepositoryInterface;
use App\Modules\Ventas\Application\Services\VentaService;
use App\Modules\ResolucionesFacturacion\Application\Services\ResolucionFacturacionService;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Servicio de Aplicación para la emisión de Nota Débito Electrónica.
 * Orquesta la validación de la factura de origen, reserva atómica de consecutivo,
 * construcción del DTO y payload para MATIAS API (POST /notes/debit),
 * persistencia de DocumentoElectronico y la transmisión técnica.
 */
class NotaDebitoService
{
    public function __construct(
        protected ConfiguracionFacturacionRepositoryInterface $configuracionRepository,
        protected ConfiguracionEmpresaRepositoryInterface $empresaRepository,
        protected FacturacionManager $facturacionManager,
        protected ResolucionFacturacionService $resolucionService,
        protected DocumentoElectronicoService $documentoElectronicoService,
        protected VentaService $ventaService,
        protected ?CatalogosFacturacionService $catalogosService = null
    ) {
        $this->catalogosService = $catalogosService ?? app(CatalogosFacturacionService::class);
    }

    /**
     * Orquestar la preparación y persistencia de una Nota Débito Electrónica para una Venta.
     */
    public function prepararEmisionPorVenta(int $ventaId, array $datosNotaDebito = []): RespuestaNotaDebitoDTO
    {
        return DB::transaction(function () use ($ventaId, $datosNotaDebito) {
            $config = $this->configuracionRepository->first();

            if (!$config || !$config->facturacion_electronica_activa) {
                throw new HttpException(422, "La facturación electrónica se encuentra desactivada en la configuración del ERP.");
            }

            $empresaConfig = $this->empresaRepository->first();

            // 1. Obtener y bloquear la Venta de origen
            $venta = Venta::where('id', $ventaId)->lockForUpdate()->first();

            if (!$venta) {
                throw new HttpException(404, "Venta #{$ventaId} no encontrada.");
            }

            // 2. Verificar que exista un documento electrónico emitido de tipo 'factura_electronica' para esta Venta
            $docFactura = DocumentoElectronico::where('venta_id', $ventaId)
                ->where('tipo_documento', 'factura_electronica')
                ->where('estado', 'emitido')
                ->first();

            // 3. Prevención de duplicados en estado emitido o pendiente para la misma Venta
            $existentesND = DocumentoElectronico::where('venta_id', $ventaId)
                ->where('tipo_documento', 'nota_debito')
                ->get();

            foreach ($existentesND as $docExistente) {
                if (in_array($docExistente->estado, ['emitido', 'pendiente', 'enviando'], true)) {
                    throw new HttpException(422, "La venta #{$ventaId} ya posee una Nota Débito Electrónica en estado '{$docExistente->estado}'.");
                }
            }

            // 4. Reservar consecutivo atómicamente para Nota Débito
            $resND = $this->resolucionService->reservarConsecutivoNotaDebito();

            // 5. Extraer CUFE / UUID de la factura de origen
            $cufeFactura = $docFactura?->cufe
                ?? $docFactura?->identificador_externo
                ?? $docFactura?->datos_tecnicos['cufe']
                ?? $docFactura?->datos_tecnicos['uuid']
                ?? '';

            $numeroFacturaRef = (string) ($docFactura?->numero_documento ?? $venta->numero_factura ?? $ventaId);
            if (!empty($docFactura?->prefijo)) {
                $numeroFacturaRef = $docFactura->prefijo . $numeroFacturaRef;
            } elseif (!empty($venta->prefijo)) {
                $numeroFacturaRef = $venta->prefijo . $numeroFacturaRef;
            }

            $fechaFacturaRef = $venta->fecha_venta ? $venta->fecha_venta->format('Y-m-d') : date('Y-m-d');

            // Discrepancy response data (1 = Intereses, 2 = Gastos por cobrar, 3 = Cambio del valor, 4 = Otros)
            $responseId = (int) ($datosNotaDebito['response_id'] ?? 1);
            $motivo = (string) ($datosNotaDebito['motivo'] ?? $datosNotaDebito['description'] ?? $venta->observacion ?? 'Ajuste de débito en factura electrónica');

            $discrepancyResponse = [
                'reference_id' => $cufeFactura,
                'response_id' => $responseId,
                'description' => $motivo,
            ];

            $billingReference = [
                'number' => $numeroFacturaRef,
                'date' => $fechaFacturaRef,
                'uuid' => $cufeFactura,
            ];

            // Datos del cliente
            $cliente = $venta->cliente;
            $cliente?->loadMissing(['pais', 'ciudad']);
            $nombreCliente = $cliente ? trim(($cliente->nombre ?? '') . ' ' . ($cliente->apellidos ?? '')) : 'CONSUMIDOR FINAL';
            if (empty($nombreCliente)) {
                $nombreCliente = 'CLIENTE MOSTRADOR';
            }

            $clienteData = [
                'company_name' => $nombreCliente,
                'nombre_razon_social' => $nombreCliente,
                'dni' => (string) ($cliente?->documento ?? '222222222222'),
                'numero_documento' => (string) ($cliente?->documento ?? '222222222222'),
                'email' => (string) ($cliente?->email ?? 'factura@cliente.com'),
                'telefono' => (string) ($cliente?->telefono_uno ?? $cliente?->telefono_dos ?? '3000000000'),
                'mobile' => (string) ($cliente?->telefono_uno ?? $cliente?->telefono_dos ?? '3000000000'),
                'direccion' => (string) ($cliente?->direccion ?? ''),
                'address' => (string) ($cliente?->direccion ?? ''),
                'identity_document_id' => (string) ($cliente?->tipo_documento_id ?? '1'),
                'type_organization_id' => (int) ($cliente?->tipo_organization_id ?? $cliente?->tipo_persona ?? 2),
                'tax_regime_id' => (int) ($cliente?->tax_regime_id ?? 2),
                'tax_level_id' => (int) ($cliente?->tax_level_id ?? 5),
                'postal_code' => (string) ($cliente?->codigo_postal ?? ''),
                'country_iso' => (string) ($cliente?->pais?->codigo_iso ?? ''),
                'city_dane_code' => (string) ($cliente?->ciudad?->codigo ?? ''),
                'city_local_id' => (string) ($cliente?->ciudad_id ?? ''),
            ];

            // Datos del emisor
            $cajeroNombre = $venta->usuario?->name ?? 'Cajero ERP';
            $emisorData = [
                'nit' => (string) ($empresaConfig?->nit ?? ''),
                'dv' => (string) ($empresaConfig?->dv ?? ''),
                'nombre_empresa' => (string) ($empresaConfig?->nombre_empresa ?? ''),
                'resolution_number' => (string) ($resND['numero_resolucion'] ?? ''),
                'cajero' => $cajeroNombre,
                'vendedor' => $cajeroNombre,
            ];

            // Líneas de items
            $venta->loadMissing(['detalles.producto.unidadMedida']);
            $proveedorActivo = $config->proveedor_activo ?? 'matias';

            $itemsData = [];
            $tipoAjuste = $datosNotaDebito['tipo_ajuste'] ?? null;
            $itemsParam = $datosNotaDebito['items'] ?? [];
            $montoIncremento = (float) ($datosNotaDebito['monto_incremento'] ?? 0);

            if (($tipoAjuste === 'cantidad' || (!empty($itemsParam) && is_array($itemsParam))) && count($itemsParam) > 0) {
                // Modo Ajuste por Cantidad
                $solicitadosMap = [];
                foreach ($itemsParam as $it) {
                    $detId = (int) ($it['detalle_venta_id'] ?? 0);
                    $cantAdicional = (float) ($it['cantidad_adicional'] ?? $it['cantidad'] ?? 0);
                    if ($detId > 0 && $cantAdicional > 0) {
                        $solicitadosMap[$detId] = round($cantAdicional, 3);
                    }
                }

                $subtotalTotal = 0.0;
                $baseGravableTotal = 0.0;
                $impuestosTotal = 0.0;

                foreach ($venta->detalles as $indice => $detalle) {
                    if (!isset($solicitadosMap[$detalle->id])) {
                        continue;
                    }

                    $cantAdicional = $solicitadosMap[$detalle->id];
                    $lineaFiscal = $this->construirLineaFiscalDesdeDetalle(
                        $detalle,
                        $cantAdicional,
                        round((float) $detalle->cantidad, 3),
                        $proveedorActivo,
                        $indice + 1,
                        false
                    );

                    $subtotalTotal = round($subtotalTotal + $lineaFiscal['line_extension_amount'], 2);
                    $baseGravableTotal = round($baseGravableTotal + $lineaFiscal['base_gravable'], 2);
                    $impuestosTotal = round($impuestosTotal + $lineaFiscal['tax_amount'], 2);

                    $itemsData[] = $lineaFiscal['item'];
                }

                if (count($itemsData) === 0) {
                    throw new HttpException(422, 'Debe especificar al menos un ítem con cantidad adicional mayor a cero para preparar la Nota Débito.');
                }

                $totalPagar = round($subtotalTotal + $impuestosTotal, 2);

                $totalesData = [
                    'subtotal' => (float) $subtotalTotal,
                    'impuestos' => (float) $impuestosTotal,
                    'descuento' => 0.0,
                    'total_pagar' => (float) $totalPagar,
                    'line_extension_amount' => (float) $subtotalTotal,
                    'tax_exclusive_amount' => (float) $baseGravableTotal,
                    'tax_inclusive_amount' => (float) $totalPagar,
                    'payable_amount' => (float) $totalPagar,
                ];
            } elseif ($montoIncremento > 0) {
                // Modo Ajuste por Valor
                $subtotal = round($montoIncremento, 2);

                $itemsData[] = [
                    'code' => 'ND-INC',
                    'codigo' => 'ND-INC',
                    'description' => (string) $motivo,
                    'nombre' => (string) $motivo,
                    'invoiced_quantity' => 1.0,
                    'cantidad' => 1.0,
                    'price_amount' => $subtotal,
                    'precio_unitario' => $subtotal,
                    'line_extension_amount' => $subtotal,
                    'tipo_impuesto' => \App\Models\Producto::TIPO_IMPUESTO_EXCLUIDO,
                    'tax_amount' => 0.0,
                    'percent' => 0.0,
                    'tax_id' => null,
                    'codigo_impuesto' => null,
                    'tax_totals' => [],
                    'quantity_units_id' => '1093',
                    'um' => 'M',
                ];

                $totalesData = [
                    'subtotal' => $subtotal,
                    'impuestos' => 0.0,
                    'descuento' => 0.0,
                    'total_pagar' => $subtotal,
                    'line_extension_amount' => $subtotal,
                    'tax_exclusive_amount' => 0.0,
                    'tax_inclusive_amount' => $subtotal,
                    'payable_amount' => $subtotal,
                ];
            } else {
                // Caso por defecto: Recargo sobre la totalidad de los ítems originales
                $subtotalTotal = 0.0;
                $baseGravableTotal = 0.0;
                $impuestosTotal = 0.0;

                foreach ($venta->detalles as $indice => $detalle) {
                    $cant = round((float) $detalle->cantidad, 3);
                    $lineaFiscal = $this->construirLineaFiscalDesdeDetalle(
                        $detalle,
                        $cant,
                        $cant,
                        $proveedorActivo,
                        $indice + 1,
                        true
                    );

                    $subtotalTotal = round($subtotalTotal + $lineaFiscal['line_extension_amount'], 2);
                    $baseGravableTotal = round($baseGravableTotal + $lineaFiscal['base_gravable'], 2);
                    $impuestosTotal = round($impuestosTotal + $lineaFiscal['tax_amount'], 2);

                    $itemsData[] = $lineaFiscal['item'];
                }

                $totalPagar = round($subtotalTotal + $impuestosTotal, 2);

                $totalesData = [
                    'subtotal' => (float) $subtotalTotal,
                    'impuestos' => (float) $impuestosTotal,
                    'descuento' => 0.0,
                    'total_pagar' => (float) $totalPagar,
                    'line_extension_amount' => (float) $subtotalTotal,
                    'tax_exclusive_amount' => (float) $baseGravableTotal,
                    'tax_inclusive_amount' => (float) $totalPagar,
                    'payable_amount' => (float) $totalPagar,
                ];
            }

            $solicitud = new SolicitudNotaDebitoDTO(
                ventaId: $venta->id,
                prefijo: $resND['prefijo'],
                folio: (string) $resND['consecutivo'],
                fechaEmision: now()->format('Y-m-d H:i:s'),
                tipoDocumento: 'nota_debito',
                discrepancyResponse: $discrepancyResponse,
                billingReference: $billingReference,
                cliente: $clienteData,
                emisor: $emisorData,
                items: $itemsData,
                totales: $totalesData,
                medioPago: $venta->tipo_venta,
                observacion: $motivo,
                metadatos: [
                    'resolution_number' => $resND['numero_resolucion'],
                    'prefix' => $resND['prefijo'],
                ]
            );

            // 6. Construir el payload JSON para MATIAS API
            $payloadMatias = MatiasDebitNoteMapper::toMatiasPayload($solicitud);

            // 7. Registrar DocumentoElectronico inicial en estado 'pendiente'
            $crearDto = new CrearDocumentoElectronicoDTO(
                ventaId: $venta->id,
                configuracionFacturacionId: $config->id,
                tipoDocumento: 'nota_debito',
                estado: 'pendiente',
                proveedor: $config->proveedor_activo ?? 'matias',
                ambiente: $config->ambiente ?? 'sandbox',
                numeroDocumento: (string) $resND['consecutivo'],
                prefijo: $resND['prefijo'],
                mensaje: 'Nota Débito Electrónica preparada y lista para transmitir a MATIAS API.',
                datosTecnicos: [
                    'payload' => $payloadMatias,
                    'solicitud' => $solicitud->toArray(),
                ]
            );

            $docElectronico = $this->documentoElectronicoService->registrarEmision($crearDto);

            return RespuestaNotaDebitoDTO::exitoso(
                estado: 'pendiente',
                numeroNotaDebito: $resND['prefijo'] . $resND['consecutivo'],
                mensaje: "Nota Débito Electrónica preparada exitosamente para la Venta #{$ventaId} en estado pendiente.",
                datosTecnicos: [
                    'documento_electronico_id' => $docElectronico->id,
                    'payload' => $payloadMatias,
                ]
            );
        });
    }

    /**
     * Transmitir una Nota Débito Electrónica en estado 'pendiente' o 'rechazado'.
     */
    public function transmitirNotaDebitoPendiente(int $documentoElectronicoId): RespuestaNotaDebitoDTO
    {
        return DB::transaction(function () use ($documentoElectronicoId) {
            // 1. Obtener y bloquear el DocumentoElectronico
            $doc = DocumentoElectronico::where('id', $documentoElectronicoId)->lockForUpdate()->first();

            if (!$doc) {
                throw new HttpException(404, "El documento electrónico #{$documentoElectronicoId} no existe.");
            }

            if ($doc->tipo_documento !== 'nota_debito') {
                throw new HttpException(422, "El documento electrónico #{$documentoElectronicoId} no es una Nota Débito Electrónica.");
            }

            if (!in_array($doc->estado, ['pendiente', 'rechazado'], true)) {
                throw new HttpException(422, "El documento electrónico #{$documentoElectronicoId} ya se encuentra en estado '{$doc->estado}'.");
            }

            // 2. Cambiar estado a 'enviando'
            $doc->update([
                'estado' => 'enviando',
                'mensaje' => 'Transmitiendo Nota Débito Electrónica a MATIAS API...',
            ]);

            // 3. Reconstruir DTO de solicitud desde los datos técnicos persistidos
            $datosTecnicos = $doc->datos_tecnicos ?? [];
            $solicitudArray = $datosTecnicos['solicitud'] ?? [];

            if (empty($solicitudArray)) {
                $ventaId = $doc->venta_id;
                if (!$ventaId) {
                    throw new HttpException(422, "El documento electrónico #{$documentoElectronicoId} no tiene una venta asociada.");
                }
                $resPrep = $this->prepararEmisionPorVenta($ventaId);
                $datosTecnicos = $resPrep->datosTecnicos ?? [];
                $solicitudArray = $datosTecnicos['solicitud'] ?? [];
            }

            $solicitud = SolicitudNotaDebitoDTO::fromArray($solicitudArray);

            // 4. Resolver el proveedor de facturación (MatiasProvider)
            $provider = $this->facturacionManager->make($doc->proveedor ?? 'matias', $doc->ambiente ?? 'sandbox');

            // 5. Transmitir la Nota Débito a MATIAS API
            try {
                /** @var RespuestaNotaDebitoDTO $respuesta */
                $respuesta = $provider->emitirNotaDebito($solicitud);
            } catch (\Throwable $e) {
                // Falla técnica de red o excepción inesperada: restaurar estado a 'pendiente'
                $doc->update([
                    'estado' => 'pendiente',
                    'mensaje' => 'Excepción técnica durante la transmisión a MATIAS API: ' . $e->getMessage(),
                    'errores' => [$e->getMessage()],
                    'datos_tecnicos' => array_merge($datosTecnicos, ['excepcion' => $e->getMessage()]),
                ]);

                return RespuestaNotaDebitoDTO::error(
                    mensaje: 'Error técnico de comunicación al transmitir la Nota Débito: ' . $e->getMessage(),
                    errores: [$e->getMessage()],
                    estado: 'pendiente',
                    datosTecnicos: $datosTecnicos
                );
            }

            // 6. Actualizar el registro según la respuesta de MATIAS
            if ($respuesta->exitoso) {
                $doc->update([
                    'estado' => 'emitido',
                    'identificador_externo' => $respuesta->identificadorExterno,
                    'cufe' => $respuesta->cude,
                    'mensaje' => $respuesta->mensaje ?? 'Nota Débito autorizada por la DIAN / MATIAS API.',
                    'datos_tecnicos' => array_merge($datosTecnicos, $respuesta->datosTecnicos),
                ]);

                return $respuesta;
            }

            // 7. Evaluar si la respuesta no exitosa es por falla técnica o por rechazo explícito de MATIAS
            $httpStatus = $respuesta->datosTecnicos['status'] ?? 0;
            $esErrorTecnico = ($httpStatus === 0 || $httpStatus >= 500 || $respuesta->estado === 'configuracion_invalida');

            if ($esErrorTecnico) {
                // Falla técnica de comunicación o servidor: restaurar estado 'pendiente'
                $doc->update([
                    'estado' => 'pendiente',
                    'mensaje' => $respuesta->mensaje ?? 'Falla técnica o de comunicación con MATIAS API.',
                    'errores' => $respuesta->errores,
                    'datos_tecnicos' => array_merge($datosTecnicos, $respuesta->datosTecnicos),
                ]);

                return RespuestaNotaDebitoDTO::error(
                    mensaje: $respuesta->mensaje ?? 'Error técnico al transmitir la Nota Débito.',
                    errores: $respuesta->errores,
                    estado: 'pendiente',
                    datosTecnicos: array_merge($datosTecnicos, $respuesta->datosTecnicos)
                );
            }

            // Rechazo explícito de MATIAS API (e.g. validación fiscal 400/422)
            $doc->update([
                'estado' => 'rechazado',
                'mensaje' => $respuesta->mensaje ?? 'MATIAS API rechazó la Nota Débito Electrónica.',
                'errores' => $respuesta->errores,
                'datos_tecnicos' => array_merge($datosTecnicos, $respuesta->datosTecnicos),
            ]);

            return $respuesta;
        });
    }

    /**
     * Reintentar la transmisión de una Nota Débito Electrónica rechazada.
     */
    public function reintentarNotaDebitoRechazada(int $documentoElectronicoId): RespuestaNotaDebitoDTO
    {
        return $this->transmitirNotaDebitoPendiente($documentoElectronicoId);
    }

    /**
     * Construye una línea fiscal para Nota Débito respetando el snapshot histórico de DetalleVenta.
     */
    protected function construirLineaFiscalDesdeDetalle(
        \App\Models\DetalleVenta $detalle,
        float $cantidadDebitar,
        float $cantidadOriginal,
        string $proveedorActivo,
        int $numeroLinea,
        bool $usarSubtotalOriginal = false
    ): array {
        $soldicom = round((float) ($detalle->soldicom ?? 0), 2);
        $sobreTasa = round((float) ($detalle->sobre_tasa ?? 0), 2);
        if ($soldicom > 0 || $sobreTasa > 0) {
            throw new HttpException(
                422,
                "No se puede preparar la línea {$numeroLinea} (producto {$detalle->producto_id}): contiene Sordicom o sobretasa sin homologación MATÍAS confirmada."
            );
        }

        $precio = round((float) $detalle->precio_unitario, 2);
        $esTotalLinea = $usarSubtotalOriginal && abs($cantidadDebitar - $cantidadOriginal) < 0.0001 && round((float) $detalle->subtotal, 2) > 0;
        $subtotalLine = $esTotalLinea
            ? round((float) $detalle->subtotal, 2)
            : round($cantidadDebitar * $precio, 2);

        $ivaPorcentaje = round((float) ($detalle->porcentaje_iva ?? $detalle->iva ?? 0), 2);
        $tipoImpuesto = ($detalle->tipo_impuesto !== null && trim((string) $detalle->tipo_impuesto) !== '')
            ? strtolower(trim((string) $detalle->tipo_impuesto))
            : null;

        if ($tipoImpuesto === null && $ivaPorcentaje > 0) {
            $tipoImpuesto = \App\Models\Producto::TIPO_IMPUESTO_GRAVADO;
        }

        if ($tipoImpuesto === null) {
            throw new HttpException(
                422,
                "No se puede preparar la Nota Débito en la línea {$numeroLinea} (producto {$detalle->producto_id}): el detalle de venta no tiene clasificación tributaria histórica (gravado, exento o excluido)."
            );
        }

        $codigoImpuesto = trim((string) ($detalle->codigo_impuesto ?? ''));
        $taxTotals = [];
        $baseGravable = 0.0;
        $ivaValorLine = 0.0;
        $taxId = null;

        if ($tipoImpuesto === \App\Models\Producto::TIPO_IMPUESTO_GRAVADO) {
            if ($ivaPorcentaje <= 0) {
                throw new HttpException(
                    422,
                    "La línea {$numeroLinea} (producto {$detalle->producto_id}) figura como gravada pero su tarifa de IVA ({$ivaPorcentaje}%) no es válida."
                );
            }

            $ivaOriginal = round((float) ($detalle->iva_valor ?? 0), 2);
            $ivaValorLine = $esTotalLinea
                ? $ivaOriginal
                : round($subtotalLine * ($ivaPorcentaje / 100.0), 2);

            $taxId = $codigoImpuesto !== ''
                ? $codigoImpuesto
                : ($this->catalogosService?->resolverCodigoImpuestoProducto(\App\Models\Producto::TIPO_IMPUESTO_GRAVADO, null, $proveedorActivo) ?? '1');

            $taxTotals[] = [
                'tax_id' => $taxId,
                'tax_amount' => number_format($ivaValorLine, 2, '.', ''),
                'taxable_amount' => number_format($subtotalLine, 2, '.', ''),
                'percent' => $ivaPorcentaje,
            ];
            $baseGravable = $subtotalLine;
        } elseif ($tipoImpuesto === \App\Models\Producto::TIPO_IMPUESTO_EXENTO) {
            $ivaPorcentaje = 0.0;
            $ivaValorLine = 0.0;
            $taxId = $codigoImpuesto !== ''
                ? $codigoImpuesto
                : ($this->catalogosService?->resolverCodigoImpuestoProducto(\App\Models\Producto::TIPO_IMPUESTO_EXENTO, null, $proveedorActivo) ?? '1');

            $taxTotals[] = [
                'tax_id' => $taxId,
                'tax_amount' => '0.00',
                'taxable_amount' => number_format($subtotalLine, 2, '.', ''),
                'percent' => 0.0,
            ];
            $baseGravable = $subtotalLine;
        } elseif ($tipoImpuesto === \App\Models\Producto::TIPO_IMPUESTO_EXCLUIDO) {
            $ivaPorcentaje = 0.0;
            $ivaValorLine = 0.0;
            $taxId = null;
            $taxTotals = [];
            $baseGravable = 0.0;
        } else {
            throw new HttpException(
                422,
                "Clasificación tributaria histórica '{$tipoImpuesto}' no reconocida en la línea {$numeroLinea}."
            );
        }

        $unidadMedida = $detalle->producto?->unidadMedida;
        if ($unidadMedida && $this->catalogosService) {
            try {
                $unidadHomologada = $this->catalogosService->resolverUnidadMedidaProducto($unidadMedida, $proveedorActivo);
            } catch (\InvalidArgumentException $e) {
                throw new HttpException(422, $e->getMessage());
            }
        } else {
            $unidadHomologada = ['quantity_units_id' => '1093', 'um' => 'M'];
        }

        $nombreProd = $detalle->producto?->nombre ?? 'Producto';
        $codigoProd = $detalle->producto?->codigo ?? 'PROD-' . $detalle->producto_id;

        return [
            'line_extension_amount' => $subtotalLine,
            'base_gravable' => $baseGravable,
            'tax_amount' => $ivaValorLine,
            'item' => [
                'code' => (string) $codigoProd,
                'codigo' => (string) $codigoProd,
                'description' => (string) $nombreProd,
                'nombre' => (string) $nombreProd,
                'invoiced_quantity' => $cantidadDebitar,
                'cantidad' => $cantidadDebitar,
                'price_amount' => $precio,
                'precio_unitario' => $precio,
                'line_extension_amount' => $subtotalLine,
                'tipo_impuesto' => $tipoImpuesto,
                'tax_amount' => $ivaValorLine,
                'percent' => $ivaPorcentaje,
                'tax_id' => $taxId,
                'codigo_impuesto' => $taxId,
                'tax_totals' => $taxTotals,
                'quantity_units_id' => $unidadHomologada['quantity_units_id'],
                'um' => $unidadHomologada['um'],
            ],
        ];
    }
}
