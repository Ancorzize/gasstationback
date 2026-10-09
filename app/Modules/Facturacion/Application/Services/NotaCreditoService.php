<?php

namespace App\Modules\Facturacion\Application\Services;

use App\Models\Venta;
use App\Models\DocumentoElectronico;
use Illuminate\Support\Facades\DB;
use App\Modules\Facturacion\Application\DTOs\SolicitudNotaCreditoDTO;
use App\Modules\Facturacion\Application\DTOs\RespuestaNotaCreditoDTO;
use App\Modules\Facturacion\Application\DTOs\CrearDocumentoElectronicoDTO;
use App\Modules\Facturacion\Infrastructure\Managers\FacturacionManager;
use App\Modules\Facturacion\Infrastructure\Mappers\MatiasCreditNoteMapper;
use App\Modules\ConfiguracionFacturacion\Application\Interfaces\ConfiguracionFacturacionRepositoryInterface;
use App\Modules\ConfiguracionEmpresa\Application\Interfaces\ConfiguracionEmpresaRepositoryInterface;
use App\Modules\Ventas\Application\Services\VentaService;
use App\Modules\ResolucionesFacturacion\Application\Services\ResolucionFacturacionService;
use App\Modules\Ventas\Application\Interfaces\VentaRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Servicio de Aplicación para la emisión de Nota Crédito Electrónica.
 * Orquesta la validación de la factura de origen, reserva atómica de consecutivo,
 * construcción del DTO y payload para MATIAS API (POST /notes/credit),
 * persistencia de DocumentoElectronico y la transmisión técnica.
 */
class NotaCreditoService
{
    public function __construct(
        protected ConfiguracionFacturacionRepositoryInterface $configuracionRepository,
        protected ConfiguracionEmpresaRepositoryInterface $empresaRepository,
        protected FacturacionManager $facturacionManager,
        protected ResolucionFacturacionService $resolucionService,
        protected DocumentoElectronicoService $documentoElectronicoService,
        protected VentaService $ventaService,
        protected ?VentaRepositoryInterface $ventaRepository = null
    ) {
        $this->ventaRepository = $ventaRepository ?? app(VentaRepositoryInterface::class);
    }

    /**
     * Orquestar la preparación y persistencia de una Nota Crédito Electrónica para una Venta.
     */
    public function prepararEmisionPorVenta(int $ventaId, array $datosNotaCredito = []): RespuestaNotaCreditoDTO
    {
        return DB::transaction(function () use ($ventaId, $datosNotaCredito) {
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
            $existentesNC = DocumentoElectronico::where('venta_id', $ventaId)
                ->where('tipo_documento', 'nota_credito')
                ->get();

            foreach ($existentesNC as $docExistente) {
                if (in_array($docExistente->estado, ['emitido', 'pendiente', 'enviando'], true)) {
                    throw new HttpException(422, "La venta #{$ventaId} ya posee una Nota Crédito Electrónica en estado '{$docExistente->estado}'.");
                }
            }

            // 4. Reservar consecutivo atómicamente para Nota Crédito
            $resNC = $this->resolucionService->reservarConsecutivoNotaCredito();

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

            // Discrepancy response data
            $responseId = (int) ($datosNotaCredito['response_id'] ?? 2); // 2 = Anulación de factura electrónica
            $motivo = (string) ($datosNotaCredito['motivo'] ?? $datosNotaCredito['description'] ?? $venta->observacion ?? 'Anulación de factura electrónica');

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
                'direccion' => (string) ($cliente?->direccion ?? 'Dirección Conocida'),
                'address' => (string) ($cliente?->direccion ?? 'Dirección Conocida'),
                'identity_document_id' => (string) ($cliente?->tipo_documento_id ?? '1'),
                'type_organization_id' => (int) ($cliente?->tipo_organization_id ?? $cliente?->tipo_persona ?? 2),
                'tax_regime_id' => (int) ($cliente?->tax_regime_id ?? 2),
                'tax_level_id' => (int) ($cliente?->tax_level_id ?? 5),
                'postal_code' => (string) ($cliente?->codigo_postal ?? '661002'),
                'country_id' => (string) ($cliente?->pais_id ?? '45'),
                'city_id' => (string) ($cliente?->ciudad_id ?? '836'),
            ];

            // Datos del emisor
            $cajeroNombre = $venta->usuario?->name ?? 'Cajero ERP';
            $emisorData = [
                'nit' => (string) ($empresaConfig?->nit ?? ''),
                'dv' => (string) ($empresaConfig?->dv ?? ''),
                'nombre_empresa' => (string) ($empresaConfig?->nombre_empresa ?? ''),
                'resolution_number' => (string) ($resNC['numero_resolucion'] ?? ''),
                'cajero' => $cajeroNombre,
                'vendedor' => $cajeroNombre,
            ];

            // Líneas de items
            $itemsData = [];
            $subtotalTotal = 0.0;
            $impuestosTotal = 0.0;

            $tipoAjuste = $datosNotaCredito['tipo_ajuste'] ?? null;
            $montoDescuento = (float) ($datosNotaCredito['monto_descuento'] ?? $datosNotaCredito['monto_ajuste'] ?? 0);
            $itemsParam = $datosNotaCredito['items'] ?? [];
            $devolucionInventario = (bool) ($datosNotaCredito['devolucion_inventario'] ?? false);

            if ($tipoAjuste === 'valor' || ($montoDescuento > 0 && empty($itemsParam))) {
                // 1. MODO AJUSTE POR VALOR MONETARIO
                if ($montoDescuento <= 0) {
                    throw new HttpException(422, 'El monto del ajuste por valor debe ser mayor a cero.');
                }
                if ($montoDescuento > (float) $venta->total) {
                    throw new HttpException(
                        422,
                        "El monto del ajuste por valor ($" . number_format($montoDescuento, 2) . ") no puede ser superior al total de la factura original ($" . number_format($venta->total, 2) . ")."
                    );
                }

                $subtotal = round($montoDescuento, 2);

                $itemsData[] = [
                    'code' => 'NC-VAL',
                    'codigo' => 'NC-VAL',
                    'description' => (string) $motivo,
                    'nombre' => (string) $motivo,
                    'invoiced_quantity' => 1.0,
                    'cantidad' => 1.0,
                    'price_amount' => $subtotal,
                    'precio_unitario' => $subtotal,
                    'line_extension_amount' => $subtotal,
                    'tax_amount' => 0.0,
                    'percent' => 0.0,
                    'quantity_units_id' => '1093',
                    'um' => 'M',
                ];

                $totalesData = [
                    'subtotal' => $subtotal,
                    'impuestos' => 0.0,
                    'descuento' => 0.0,
                    'total_pagar' => $subtotal,
                    'line_extension_amount' => $subtotal,
                    'tax_exclusive_amount' => $subtotal,
                    'tax_inclusive_amount' => $subtotal,
                    'payable_amount' => $subtotal,
                ];

                // CASO A: Ajuste por valor NO mueve inventario.
            } elseif (is_array($itemsParam) && count($itemsParam) > 0) {
                // 2. MODO AJUSTE POR CANTIDAD (Devolución parcial o de ítems específicos)
                $solicitadosMap = [];
                foreach ($itemsParam as $it) {
                    if (isset($it['detalle_venta_id'])) {
                        $solicitadosMap[(int) $it['detalle_venta_id']] = (float) ($it['cantidad'] ?? 0);
                    }
                }

                foreach ($venta->detalles as $detalle) {
                    if (!isset($solicitadosMap[$detalle->id])) {
                        continue;
                    }

                    $cantSolicitada = $solicitadosMap[$detalle->id];
                    if ($cantSolicitada <= 0) {
                        continue;
                    }

                    $cantOriginal = (float) $detalle->cantidad;
                    $nombreProd = $detalle->producto?->nombre ?? 'Producto';

                    if ($cantSolicitada > $cantOriginal) {
                        throw new HttpException(
                            422,
                            "La cantidad a acreditar ({$cantSolicitada}) supera la cantidad facturada ({$cantOriginal}) para el producto '{$nombreProd}'."
                        );
                    }

                    $precio = (float) $detalle->precio_unitario;
                    $subtotalLine = round($cantSolicitada * $precio, 2);
                    $factor = $cantOriginal > 0 ? ($cantSolicitada / $cantOriginal) : 1.0;
                    $ivaValorLine = round(((float) ($detalle->iva_valor ?? 0)) * $factor, 2);
                    $ivaPorcentaje = (float) ($detalle->iva ?? 0);
                    $codigoProd = $detalle->producto?->codigo ?? 'PROD-' . $detalle->producto_id;

                    $subtotalTotal += $subtotalLine;
                    $impuestosTotal += $ivaValorLine;

                    $itemsData[] = [
                        'code' => (string) $codigoProd,
                        'codigo' => (string) $codigoProd,
                        'description' => (string) $nombreProd,
                        'nombre' => (string) $nombreProd,
                        'invoiced_quantity' => $cantSolicitada,
                        'cantidad' => $cantSolicitada,
                        'price_amount' => $precio,
                        'precio_unitario' => $precio,
                        'line_extension_amount' => $subtotalLine,
                        'tax_amount' => $ivaValorLine,
                        'percent' => $ivaPorcentaje,
                        'quantity_units_id' => '1093',
                        'um' => 'M',
                    ];

                    // CASO B: Si existe devolución física confirmada (devolucion_inventario === true) y la venta posee bodega_id
                    if ($devolucionInventario && $venta->bodega_id && $detalle->producto_id) {
                        $this->ventaRepository->incrementInventario(
                            (int) $detalle->producto_id,
                            (int) $venta->bodega_id,
                            (float) $cantSolicitada
                        );

                        $this->ventaRepository->createMovimientoInventario([
                            'codigo_lote' => 'NC-' . $resNC['consecutivo'],
                            'tipo_movimiento' => 'entrada',
                            'producto_id' => $detalle->producto_id,
                            'bodega_origen_id' => null,
                            'bodega_destino_id' => $venta->bodega_id,
                            'cantidad' => $cantSolicitada,
                            'observacion' => "Reintegro por devolución física en Nota Crédito NC-{$resNC['consecutivo']} de Venta #{$venta->id}",
                            'user_id' => auth()->id() ?? $venta->usuario_id ?? 1,
                        ]);
                    }
                }

                if (count($itemsData) === 0) {
                    throw new HttpException(422, 'Debe especificar al menos un ítem válido con cantidad mayor a cero para preparar la Nota Crédito.');
                }

                $totalPagar = round($subtotalTotal + $impuestosTotal, 2);

                $totalesData = [
                    'subtotal' => (float) $subtotalTotal,
                    'impuestos' => (float) $impuestosTotal,
                    'descuento' => 0.00,
                    'total_pagar' => (float) $totalPagar,
                    'line_extension_amount' => (float) $subtotalTotal,
                    'tax_exclusive_amount' => (float) $subtotalTotal,
                    'tax_inclusive_amount' => (float) $totalPagar,
                    'payable_amount' => (float) $totalPagar,
                ];
            } else {
                // 3. MODO POR DEFECTO: Anulación total (100% de los detalles)
                foreach ($venta->detalles as $detalle) {
                    $cant = (float) $detalle->cantidad;
                    $precio = (float) $detalle->precio_unitario;
                    $subtotal = (float) $detalle->subtotal;
                    $ivaValor = (float) ($detalle->iva_valor ?? 0);
                    $ivaPorcentaje = (float) ($detalle->iva ?? 0);
                    $nombreProd = $detalle->producto?->nombre ?? 'Producto';
                    $codigoProd = $detalle->producto?->codigo ?? 'PROD-' . $detalle->producto_id;

                    $subtotalTotal += $subtotal;
                    $impuestosTotal += $ivaValor;

                    $itemsData[] = [
                        'code' => (string) $codigoProd,
                        'codigo' => (string) $codigoProd,
                        'description' => (string) $nombreProd,
                        'nombre' => (string) $nombreProd,
                        'invoiced_quantity' => $cant,
                        'cantidad' => $cant,
                        'price_amount' => $precio,
                        'precio_unitario' => $precio,
                        'line_extension_amount' => $subtotal,
                        'tax_amount' => $ivaValor,
                        'percent' => $ivaPorcentaje,
                        'quantity_units_id' => '1093',
                        'um' => 'M',
                    ];

                    // Reintegro a inventario si devolucion_inventario === true
                    if ($devolucionInventario && $venta->bodega_id && $detalle->producto_id) {
                        $this->ventaRepository->incrementInventario(
                            (int) $detalle->producto_id,
                            (int) $venta->bodega_id,
                            (float) $cant
                        );

                        $this->ventaRepository->createMovimientoInventario([
                            'codigo_lote' => 'NC-' . $resNC['consecutivo'],
                            'tipo_movimiento' => 'entrada',
                            'producto_id' => $detalle->producto_id,
                            'bodega_origen_id' => null,
                            'bodega_destino_id' => $venta->bodega_id,
                            'cantidad' => $cant,
                            'observacion' => "Reintegro por anulación en Nota Crédito NC-{$resNC['consecutivo']} de Venta #{$venta->id}",
                            'user_id' => auth()->id() ?? $venta->usuario_id ?? 1,
                        ]);
                    }
                }

                $totalPagar = round($subtotalTotal + $impuestosTotal, 2);

                $totalesData = [
                    'subtotal' => (float) $subtotalTotal,
                    'impuestos' => (float) $impuestosTotal,
                    'descuento' => 0.00,
                    'total_pagar' => (float) $totalPagar,
                    'line_extension_amount' => (float) $subtotalTotal,
                    'tax_exclusive_amount' => (float) $subtotalTotal,
                    'tax_inclusive_amount' => (float) $totalPagar,
                    'payable_amount' => (float) $totalPagar,
                ];
            }

            $solicitud = new SolicitudNotaCreditoDTO(
                ventaId: $venta->id,
                prefijo: $resNC['prefijo'],
                folio: (string) $resNC['consecutivo'],
                fechaEmision: now()->format('Y-m-d H:i:s'),
                tipoDocumento: 'nota_credito',
                discrepancyResponse: $discrepancyResponse,
                billingReference: $billingReference,
                cliente: $clienteData,
                emisor: $emisorData,
                items: $itemsData,
                totales: $totalesData,
                medioPago: $venta->tipo_venta,
                observacion: $motivo,
                metadatos: [
                    'resolution_number' => $resNC['numero_resolucion'],
                    'prefix' => $resNC['prefijo'],
                ]
            );

            // 6. Construir el payload JSON para MATIAS API
            $payloadMatias = MatiasCreditNoteMapper::toMatiasPayload($solicitud);

            // 7. Registrar DocumentoElectronico inicial en estado 'pendiente'
            $crearDto = new CrearDocumentoElectronicoDTO(
                ventaId: $venta->id,
                configuracionFacturacionId: $config->id,
                tipoDocumento: 'nota_credito',
                estado: 'pendiente',
                proveedor: $config->proveedor_activo ?? 'matias',
                ambiente: $config->ambiente ?? 'sandbox',
                numeroDocumento: (string) $resNC['consecutivo'],
                prefijo: $resNC['prefijo'],
                mensaje: 'Nota Crédito Electrónica preparada y lista para transmitir a MATIAS API.',
                datosTecnicos: [
                    'payload' => $payloadMatias,
                    'solicitud' => $solicitud->toArray(),
                ]
            );

            $docElectronico = $this->documentoElectronicoService->registrarEmision($crearDto);

            return RespuestaNotaCreditoDTO::exitoso(
                estado: 'pendiente',
                numeroNotaCredito: $resNC['prefijo'] . $resNC['consecutivo'],
                mensaje: "Nota Crédito Electrónica preparada exitosamente para la Venta #{$ventaId} en estado pendiente.",
                datosTecnicos: [
                    'documento_electronico_id' => $docElectronico->id,
                    'payload' => $payloadMatias,
                ]
            );
        });
    }

    /**
     * Transmitir una Nota Crédito Electrónica en estado 'pendiente' o 'rechazado'.
     */
    public function transmitirNotaCreditoPendiente(int $documentoElectronicoId): RespuestaNotaCreditoDTO
    {
        return DB::transaction(function () use ($documentoElectronicoId) {
            // 1. Obtener y bloquear el DocumentoElectronico
            $doc = DocumentoElectronico::where('id', $documentoElectronicoId)->lockForUpdate()->first();

            if (!$doc) {
                throw new HttpException(404, "El documento electrónico #{$documentoElectronicoId} no existe.");
            }

            if ($doc->tipo_documento !== 'nota_credito') {
                throw new HttpException(422, "El documento electrónico #{$documentoElectronicoId} no es una Nota Crédito Electrónica.");
            }

            if (!in_array($doc->estado, ['pendiente', 'rechazado'], true)) {
                throw new HttpException(422, "El documento electrónico #{$documentoElectronicoId} ya se encuentra en estado '{$doc->estado}'.");
            }

            // 2. Cambiar estado a 'enviando'
            $doc->update([
                'estado' => 'enviando',
                'mensaje' => 'Transmitiendo Nota Crédito Electrónica a MATIAS API...',
            ]);

            // 3. Reconstruir DTO de solicitud desde los datos técnicos persistidos
            $datosTecnicos = $doc->datos_tecnicos ?? [];
            $solicitudArray = $datosTecnicos['solicitud'] ?? [];

            if (empty($solicitudArray)) {
                // Si por alguna razón no existiera solicitud guardada, reconstruirla desde la Venta
                $ventaId = $doc->venta_id;
                if (!$ventaId) {
                    throw new HttpException(422, "El documento electrónico #{$documentoElectronicoId} no tiene una venta asociada.");
                }
                $resPrep = $this->prepararEmisionPorVenta($ventaId);
                $datosTecnicos = $resPrep->datosTecnicos ?? [];
                $solicitudArray = $datosTecnicos['solicitud'] ?? [];
            }

            $solicitud = SolicitudNotaCreditoDTO::fromArray($solicitudArray);

            // 4. Resolver el proveedor de facturación (MatiasProvider)
            $provider = $this->facturacionManager->make($doc->proveedor ?? 'matias', $doc->ambiente ?? 'sandbox');

            // 5. Transmitir la Nota Crédito a MATIAS API
            try {
                /** @var RespuestaNotaCreditoDTO $respuesta */
                $respuesta = $provider->emitirNotaCredito($solicitud);
            } catch (\Throwable $e) {
                $doc->update([
                    'estado' => 'rechazado',
                    'mensaje' => 'Excepción durante la transmisión HTTP a MATIAS API: ' . $e->getMessage(),
                    'errores' => [$e->getMessage()],
                    'datos_tecnicos' => array_merge($datosTecnicos, ['excepcion' => $e->getMessage()]),
                ]);

                return RespuestaNotaCreditoDTO::error(
                    mensaje: 'Error de comunicación al transmitir la Nota Crédito: ' . $e->getMessage(),
                    errores: [$e->getMessage()],
                    estado: 'rechazado',
                    datosTecnicos: $datosTecnicos
                );
            }

            // 6. Actualizar el registro según la respuesta de MATIAS
            if ($respuesta->exitoso) {
                $doc->update([
                    'estado' => 'emitido',
                    'identificador_externo' => $respuesta->identificadorExterno,
                    'cufe' => $respuesta->cude,
                    'mensaje' => $respuesta->mensaje ?? 'Nota Crédito autorizada por la DIAN / MATIAS API.',
                    'datos_tecnicos' => array_merge($datosTecnicos, $respuesta->datosTecnicos),
                ]);

                return $respuesta;
            }

            // Si MATIAS rechazó la Nota Crédito
            $doc->update([
                'estado' => 'rechazado',
                'mensaje' => $respuesta->mensaje ?? 'MATIAS API rechazó la Nota Crédito Electrónica.',
                'errores' => $respuesta->errores,
                'datos_tecnicos' => array_merge($datosTecnicos, $respuesta->datosTecnicos),
            ]);

            return $respuesta;
        });
    }

    /**
     * Reintentar la transmisión de una Nota Crédito Electrónica rechazada.
     */
    public function reintentarNotaCreditoRechazada(int $documentoElectronicoId): RespuestaNotaCreditoDTO
    {
        return $this->transmitirNotaCreditoPendiente($documentoElectronicoId);
    }
}
