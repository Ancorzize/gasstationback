<?php

namespace App\Modules\Ventas\Application\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Venta;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Ventas\Application\DTOs\CreateVentaDTO;
use App\Modules\Ventas\Application\Interfaces\VentaRepositoryInterface;
use App\Modules\TurnosIslero\Application\Interfaces\TurnoIsleroRepositoryInterface;
use App\Modules\Ventas\Application\DTOs\CreateVentaCombustibleDTO;
use App\Models\TurnoIslero;
use Illuminate\Support\Collection;
use App\Models\User;
use App\Modules\Facturacion\Application\Services\FacturacionService;
use App\Modules\Facturacion\Application\Services\DocumentoElectronicoService;
use App\Modules\Facturacion\Application\Services\CatalogosFacturacionService;
use App\Modules\ConfiguracionFacturacion\Application\Interfaces\ConfiguracionFacturacionRepositoryInterface;
use App\Modules\ConfiguracionEmpresa\Application\Interfaces\ConfiguracionEmpresaRepositoryInterface;
use App\Modules\ResolucionesFacturacion\Application\Interfaces\ResolucionFacturacionRepositoryInterface;
use App\Modules\ResolucionesFacturacion\Application\Services\ResolucionFacturacionService;
use App\Models\ResolucionFacturacion;
use App\Modules\Facturacion\Application\DTOs\CrearDocumentoElectronicoDTO;
use App\Modules\Facturacion\Application\DTOs\SolicitudFacturaDTO;
use App\Modules\Facturacion\Application\DTOs\RespuestaFacturacionDTO;
use App\Models\DocumentoElectronico;
use App\Models\ConfiguracionEmpresa;
use App\Models\ConfiguracionFacturacion;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\DetalleVenta;
use App\Models\AnticipoCliente;
use App\Models\AplicacionAnticipoCliente;

class VentaService
{
    public function __construct(
        protected VentaRepositoryInterface $ventaRepository,
        protected TurnoIsleroRepositoryInterface $turnoRepository,
        protected ?FacturacionService $facturacionService = null,
        protected ?DocumentoElectronicoService $documentoElectronicoService = null,
        protected ?ConfiguracionFacturacionRepositoryInterface $configuracionFacturacionRepository = null,
        protected ?ConfiguracionEmpresaRepositoryInterface $configuracionEmpresaRepository = null,
        protected ?ResolucionFacturacionRepositoryInterface $resolucionFacturacionRepository = null,
        protected ?ResolucionFacturacionService $resolucionFacturacionService = null,
        protected ?CatalogosFacturacionService $catalogosFacturacionService = null
    ) {
        $this->facturacionService = $facturacionService ?? app(FacturacionService::class);
        $this->documentoElectronicoService = $documentoElectronicoService ?? app(DocumentoElectronicoService::class);
        $this->configuracionFacturacionRepository = $configuracionFacturacionRepository ?? app(ConfiguracionFacturacionRepositoryInterface::class);
        $this->configuracionEmpresaRepository = $configuracionEmpresaRepository ?? app(ConfiguracionEmpresaRepositoryInterface::class);
        $this->resolucionFacturacionRepository = $resolucionFacturacionRepository ?? app(ResolucionFacturacionRepositoryInterface::class);
        $this->resolucionFacturacionService = $resolucionFacturacionService ?? app(ResolucionFacturacionService::class);
        $this->catalogosFacturacionService = $catalogosFacturacionService ?? app(CatalogosFacturacionService::class);
    }

    public function paginate(array $filters = [])
    {
        return $this->ventaRepository->getAll($filters);
    }

    public function findById(int $id): Venta
    {
        $venta = $this->ventaRepository->findById(
            $id
        );
        if (!$venta) {
            throw new HttpException(404, 'Venta no encontrada.');
        }

        return $venta;
    }

    /**
     * crear venta de lubricantes
     */
    public function create(CreateVentaDTO $dto): Venta
    {
        $ventaFinal = DB::transaction(function () use ($dto) {

            if (count($dto->detalles) <= 0) {
                throw new HttpException(422, 'La venta no tiene productos.');
            }

            $user = $this->ventaRepository->findUserById($dto->user_id);

            if (!$user) {
                throw new HttpException(422, 'Usuario no encontrado.');
            }

            if (!$user->bodega_id) {
                throw new HttpException(422, 'El usuario no tiene una bodega asignada.');
            }

            $bodegaId = (int) $user->bodega_id;

            $empresaConfig = $this->configuracionEmpresaRepository?->first();
            $configFacturacion = $this->configuracionFacturacionRepository?->first();
            $facturacionActiva = (bool) ($configFacturacion && $configFacturacion->facturacion_electronica_activa);

            if ((bool) $dto->factura_electronica && !$facturacionActiva) {
                throw new HttpException(
                    422,
                    'La facturación electrónica se encuentra desactivada en la configuración del sistema.'
                );
            }

            $requiereFacturacionElectronica = $facturacionActiva && (
                (bool) $dto->factura_electronica || (bool) $configFacturacion->facturar_ventas_pos
            );

            $subtotal = 0;
            $impuesto = 0;
            $soldicom = 0;
            $sobreTasa = 0;
            $descuento = 0;
            $total = 0;
            $destinoRecaudoId = null;
            $detallesCalculados = [];

            foreach ($dto->detalles as $detalle) {

                $producto = $this->ventaRepository->findProductoById(
                    $detalle->producto_id
                );

                if (!$producto) {
                    throw new HttpException(
                        422,
                        "Producto {$detalle->producto_id} no existe."
                    );
                }

                if (
                    !$producto->categoriaProducto
                ) {
                    throw new HttpException(
                        422,
                        "El producto {$producto->nombre} no tiene categoría."
                    );
                }

                if (
                    !$producto->categoriaProducto->destino_recaudo_id
                ) {
                    throw new HttpException(
                        422,
                        "La categoría del producto {$producto->nombre} no tiene destino de recaudo."
                    );
                }

                if ($destinoRecaudoId === null) {

                    $destinoRecaudoId =
                        $producto
                        ->categoriaProducto
                        ->destino_recaudo_id;
                } elseif (
                    $destinoRecaudoId !==
                    $producto
                    ->categoriaProducto
                    ->destino_recaudo_id
                ) {

                    throw new HttpException(
                        422,
                        'No se permiten productos de diferentes destinos de recaudo en una misma venta.'
                    );
                }

                $inventario = $this->ventaRepository->findInventario(
                    $detalle->producto_id,
                    $bodegaId
                );

                if (!$inventario) {
                    throw new HttpException(
                        422,
                        "El producto {$producto->nombre} no tiene inventario."
                    );
                }

                if ((float) $inventario->cantidad < (float) $detalle->cantidad) {
                    throw new HttpException(
                        422,
                        "Stock insuficiente para {$producto->nombre}."
                    );
                }

                $calculoLinea = $this->calcularLineaTributaria(
                    producto: $producto,
                    cantidad: (float) $detalle->cantidad,
                    precioDigitado: (float) $detalle->precio_unitario,
                    descuentoDigitado: (float) $detalle->descuento,
                    empresaConfig: $empresaConfig,
                    exigirClasificacion: $requiereFacturacionElectronica,
                    soldicom: (float) $detalle->soldicom,
                    sobreTasa: (float) $detalle->sobre_tasa
                );

                $detallesCalculados[] = $calculoLinea;

                $subtotal = round($subtotal + $calculoLinea['subtotal'], 2);
                $impuesto = round($impuesto + $calculoLinea['iva_valor'], 2);
                $soldicom = round($soldicom + $calculoLinea['soldicom'], 2);
                $sobreTasa = round($sobreTasa + $calculoLinea['sobre_tasa'], 2);
                $descuento = round($descuento + $calculoLinea['descuento'], 2);
                $total = round($total + $calculoLinea['total'], 2);
            }

            $montoAnticipoAplicar = 0.0;
            if ($dto->usar_anticipo) {
                if (!$dto->cliente_id) {
                    throw new HttpException(422, 'Debe seleccionar un cliente para aplicar anticipos.');
                }

                $clienteAnticipo = Cliente::where('id', $dto->cliente_id)->lockForUpdate()->first();
                if (!$clienteAnticipo) {
                    throw new HttpException(404, 'Cliente no encontrado.');
                }

                $saldoAFavorDisponible = (float) AnticipoCliente::where('cliente_id', $clienteAnticipo->id)
                    ->where('estado', 'disponible')
                    ->where('saldo_disponible', '>', 0)
                    ->sum('saldo_disponible');

                if ($saldoAFavorDisponible <= 0) {
                    throw new HttpException(422, 'El cliente seleccionado no cuenta con anticipos o saldo a favor disponible.');
                }

                $montoDeseado = ($dto->monto_anticipo !== null && $dto->monto_anticipo > 0)
                    ? (float) $dto->monto_anticipo
                    : (float) $total;

                $montoAnticipoAplicar = min($montoDeseado, $saldoAFavorDisponible, (float) $total);

                if ($montoAnticipoAplicar <= 0) {
                    throw new HttpException(422, 'El monto de anticipo a aplicar debe ser mayor a cero.');
                }
            }

            // Si la venta es de contado sin anticipo y el frontend envió 1 solo pago cuyo monto difiere ligeramente
            // porque el frontend no conocía el IVA real calculado en el backend, ajustar el monto del pago único al total real.
            if ($dto->tipo_venta === 'contado' && !$dto->usar_anticipo && count($dto->pagos) === 1) {
                $dto->pagos[0]->monto = $total;
            }

            $totalPagadoDirecto = 0.0;

            foreach ($dto->pagos as $pago) {
                $totalPagadoDirecto += (float) $pago->monto;
            }

            $totalCubierto = round($totalPagadoDirecto + $montoAnticipoAplicar, 2);

            if ($totalCubierto > $total) {
                if ($dto->tipo_venta === 'contado' && count($dto->pagos) === 1 && $montoAnticipoAplicar > 0) {
                    $dto->pagos[0]->monto = max(0.0, round($total - $montoAnticipoAplicar, 2));
                    $totalPagadoDirecto = $dto->pagos[0]->monto;
                    $totalCubierto = round($totalPagadoDirecto + $montoAnticipoAplicar, 2);
                } else {
                    throw new HttpException(
                        422,
                        "El total pagado (\${$totalPagadoDirecto}) sumado al anticipo (\${$montoAnticipoAplicar}) supera el total de la venta (\${$total})."
                    );
                }
            }

            $saldoPendiente = round($total - $totalCubierto, 2);
            $cliente = null;

            if ($saldoPendiente > 0) {

                if (!$dto->cliente_id) {
                    throw new HttpException(
                        422,
                        'Debe seleccionar un cliente para ventas a crédito o con saldo pendiente.'
                    );
                }

                $cliente = $this->ventaRepository->findClienteById(
                    $dto->cliente_id
                );

                if (!$cliente) {
                    throw new HttpException(422, 'Cliente no encontrado.');
                }

                if (!(bool) $cliente->maneja_credito) {
                    throw new HttpException(
                        422,
                        'El cliente no tiene crédito habilitado.'
                    );
                }

                $cupoDisponible = (float) $cliente->cupo_credito - (float) $cliente->saldo_credito;

                if ($cupoDisponible < $saldoPendiente) {
                    throw new HttpException(422, 'El cliente no tiene cupo suficiente.');
                }
            }

            if ($dto->factura_electronica) {
                if (!$dto->cliente_id) {
                    throw new HttpException(422, 'Debe seleccionar un cliente para generar factura electrónica.');
                }
                $clienteReq = $this->ventaRepository->findClienteById($dto->cliente_id);
                if (!$clienteReq) {
                    throw new HttpException(422, 'El cliente seleccionado no existe.');
                }
                $nombreReq = trim(($clienteReq->nombre ?? '') . ' ' . ($clienteReq->apellidos ?? ''));
                if (empty($clienteReq->documento) || empty($nombreReq)) {
                    throw new HttpException(422, 'El cliente seleccionado no tiene documento o nombre registrado.');
                }
            }

            $turnoAbierto = $this->ventaRepository->getTurnoAbiertoByUser($dto->user_id);
            if (empty($turnoAbierto)) {
                throw new HttpException(422, 'El usuario no tiene turno abierto.');
            }

            // Validar que exista caja abierta para cada pago antes de reservar consecutivo de facturación
            $cajasPorPago = [];
            foreach ($dto->pagos as $idxPago => $pago) {
                $tipoCaja = $this->resolverTipoCaja($pago->metodo_pago);
                $caja = $this->ventaRepository->getCajaAbiertaByTipoAndDestino(
                    $tipoCaja,
                    $destinoRecaudoId
                );

                if (!$caja) {
                    throw new HttpException(
                        422,
                        'No existe caja abierta para el destino de recaudo configurado.'
                    );
                }

                $cajasPorPago[$idxPago] = $caja;
            }

            $numeracion = $this->resolverNumeracionVenta(
                requiereFacturaElectronica: $requiereFacturacionElectronica,
                empresaId: $empresaConfig?->id,
                prefijoDefault: 'POS'
            );

            $venta = $this->ventaRepository->createVenta([
                'prefijo' => $numeracion['prefijo'],
                'numero_factura' => $numeracion['numero_factura'],
                'cliente_id' => $dto->cliente_id,
                'user_id' => $dto->user_id,
                'bodega_id' => $bodegaId,
                'tipo_venta' => $dto->tipo_venta,
                'tipo_origen' => 'pos',
                'factura_electronica' => (bool) $dto->factura_electronica,
                'estado' => 'confirmada',
                'estado_pago' => $saldoPendiente <= 0
                    ? 'pagado'
                    : ($totalCubierto > 0 ? 'parcial' : 'pendiente'),
                'subtotal' => $subtotal,
                'descuento' => $descuento,
                'impuesto' => $impuesto,
                'soldicom' => $soldicom,
                'sobre_tasa' => $sobreTasa,
                'total' => $total,
                'total_pagado' => $totalCubierto,
                'saldo_pendiente' => $saldoPendiente,
                'fecha_venta' => now(),
                'fecha_vencimiento' => ($saldoPendiente > 0 && $cliente) ? now()->addDays($cliente->dias_credito) : null,
                'observacion' => $dto->observacion,
                'turno_islero_id' => $turnoAbierto?->id,
                'destino_recaudo_id' => $destinoRecaudoId,
            ]);

            if ($montoAnticipoAplicar > 0) {
                $this->aplicarAnticiposAVenta(
                    $venta,
                    $dto->cliente_id,
                    $montoAnticipoAplicar,
                    $dto->user_id
                );
            }


            foreach ($detallesCalculados as $detalleCalc) {

                $this->ventaRepository->createDetalle([
                    'venta_id' => $venta->id,
                    'producto_id' => $detalleCalc['producto_id'],
                    'manguera_id' => null,
                    'cantidad' => $detalleCalc['cantidad'],
                    'precio_unitario' => $detalleCalc['precio_unitario'],
                    'descuento' => $detalleCalc['descuento'],
                    'tipo_impuesto' => $detalleCalc['tipo_impuesto'],
                    'porcentaje_iva' => $detalleCalc['porcentaje_iva'],
                    'codigo_impuesto' => $detalleCalc['codigo_impuesto'],
                    'iva' => $detalleCalc['iva'],
                    'iva_valor' => $detalleCalc['iva_valor'],
                    'soldicom' => $detalleCalc['soldicom'],
                    'sobre_tasa' => $detalleCalc['sobre_tasa'],
                    'subtotal' => $detalleCalc['subtotal'],
                    'total' => $detalleCalc['total'],
                ]);



                $this->ventaRepository->decrementInventario(
                    $detalleCalc['producto_id'],
                    $bodegaId,
                    $detalleCalc['cantidad']
                );

                $this->ventaRepository->createMovimientoInventario([
                    'tipo_movimiento' => 'venta',
                    'producto_id' => $detalleCalc['producto_id'],
                    'bodega_origen_id' => $bodegaId,
                    'bodega_destino_id' => null,
                    'cantidad' => $detalleCalc['cantidad'],
                    'observacion' => "Venta #{$venta->id}",
                    'user_id' => $dto->user_id,
                ]);
            }

            foreach ($dto->pagos as $idxPago => $pago) {
                $caja = $cajasPorPago[$idxPago];

                $this->ventaRepository->createPago([
                    'venta_id' => $venta->id,
                    'caja_id' => $caja->id,
                    'user_id' => $dto->user_id,
                    'fecha_pago' => now(),
                    'monto' => $pago->monto,
                    'metodo_pago' => $pago->metodo_pago,
                    'observacion' => $pago->observacion,
                ]);

                if (empty($turnoAbierto)) {
                    $this->ventaRepository->createMovimientoCaja([
                        'caja_id' => $caja->id,
                        'tipo_movimiento' => 'ingreso',
                        'categoria_movimiento' => 'venta',
                        'origen_modulo' => 'ventas',
                        'origen_id' => $venta->id,
                        'medio_pago' => $pago->metodo_pago,
                        'monto' => $pago->monto,
                        'descripcion' => "Ingreso por Venta #{$venta->id}",
                        'user_id' => $dto->user_id,
                        'fecha_movimiento' => now(),
                    ]);
                }
            }


            if ($saldoPendiente > 0) {

                $cliente = $this->ventaRepository->findClienteById(
                    $dto->cliente_id
                );

                $saldoAnterior = (float) $cliente->saldo_credito;
                $nuevoSaldo = $saldoAnterior + $saldoPendiente;


                $this->ventaRepository->updateCliente($cliente, [
                    'saldo_credito' => $nuevoSaldo,
                ]);

                $this->ventaRepository->createMovimientoCartera([
                    'cliente_id' => $cliente->id,
                    'tipo_movimiento' => 'venta_credito',
                    'origen_modulo' => 'ventas',
                    'origen_id' => $venta->id,
                    'valor' => $saldoPendiente,
                    'saldo_anterior' => $saldoAnterior,
                    'saldo_nuevo' => $nuevoSaldo,
                    'medio_pago' => null,
                    'descripcion' => "Venta crédito #{$venta->id}",
                    'user_id' => $dto->user_id,
                    'fecha_movimiento' => now(),
                ]);
            }

            return $this->findById($venta->id);
        });

        $this->procesarFacturacionElectronica($ventaFinal);

        return $ventaFinal;
    }

    private function resolverTipoCaja(
        string $metodoPago
    ): string {
        return match ($metodoPago) {

            'efectivo' => 'efectivo',

            'qr',
            'transferencia',
            'datafono',
            'consignacion',
            'digital' => 'digital',

            default => throw new HttpException(
                422,
                "Método de pago {$metodoPago} inválido."
            ),
        };
    }

    /**
     * Aplica anticipos disponibles de un cliente a una venta en orden FIFO.
     * Retorna el monto total efectivamente aplicado.
     */
    protected function aplicarAnticiposAVenta(
        Venta $venta,
        int $clienteId,
        float $montoAAplicar,
        int $userId
    ): float {
        if ($montoAAplicar <= 0) {
            return 0.0;
        }

        $cliente = Cliente::where('id', $clienteId)
            ->lockForUpdate()
            ->first();

        if (!$cliente) {
            throw new HttpException(404, 'Cliente no encontrado para aplicar anticipo.');
        }

        $anticipos = AnticipoCliente::where('cliente_id', $cliente->id)
            ->where('estado', 'disponible')
            ->where('saldo_disponible', '>', 0)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();

        $saldoAFavorDisponible = (float) $anticipos->sum('saldo_disponible');

        if ($saldoAFavorDisponible < $montoAAplicar) {
            throw new HttpException(
                422,
                "El saldo a favor disponible (\${$saldoAFavorDisponible}) es insuficiente para cubrir el monto de anticipo solicitado (\${$montoAAplicar})."
            );
        }

        $restantePorAplicar = $montoAAplicar;
        $totalAplicado = 0.0;

        foreach ($anticipos as $anticipo) {
            if ($restantePorAplicar <= 0) {
                break;
            }

            $saldoAnticipo = (float) $anticipo->saldo_disponible;
            if ($saldoAnticipo <= 0) {
                continue;
            }

            $aplicar = min($saldoAnticipo, $restantePorAplicar);
            $nuevoSaldoAnticipo = round($saldoAnticipo - $aplicar, 2);

            AplicacionAnticipoCliente::create([
                'anticipo_cliente_id' => $anticipo->id,
                'venta_id' => $venta->id,
                'monto_aplicado' => $aplicar,
                'saldo_anterior' => $saldoAnticipo,
                'saldo_nuevo' => $nuevoSaldoAnticipo,
                'estado' => 'aplicada',
                'observacion' => "Aplicación de anticipo #{$anticipo->numero} en venta #{$venta->id}",
                'user_id' => $userId,
            ]);

            $anticipo->saldo_disponible = $nuevoSaldoAnticipo;
            if ($nuevoSaldoAnticipo <= 0) {
                $anticipo->estado = 'agotado';
            }
            $anticipo->save();

            $totalAplicado = round($totalAplicado + $aplicar, 2);
            $restantePorAplicar = round($restantePorAplicar - $aplicar, 2);
        }

        $nuevoSaldoAFavorTotal = (float) AnticipoCliente::query()
            ->where('cliente_id', $cliente->id)
            ->where('estado', 'disponible')
            ->sum('saldo_disponible');

        $this->ventaRepository->updateCliente($cliente, [
            'saldo_a_favor' => round($nuevoSaldoAFavorTotal, 2),
        ]);

        return $totalAplicado;
    }

    public function anular(
        int $id,
        string $motivoAnulacion,
        int $userId
    ): Venta {
        return DB::transaction(function () use (
            $id,
            $motivoAnulacion,
            $userId
        ) {

            $venta = $this->findById($id);

            if ($venta->estado === 'anulada') {
                throw new HttpException(
                    422,
                    'La venta ya se encuentra anulada.'
                );
            }

            $facturaEmitida = DocumentoElectronico::where('venta_id', $venta->id)
                ->where('tipo_documento', 'factura_electronica')
                ->where('estado', 'emitido')
                ->exists();

            if ($facturaEmitida) {
                throw new HttpException(
                    422,
                    'Esta venta ya tiene una factura electrónica emitida y no puede anularse directamente. Debe realizarse el ajuste mediante una Nota Crédito.'
                );
            }

            $usuarioVenta = null;

            if ($venta->user_id) {
                $usuarioVenta = User::find($venta->user_id);
            }

            $turno = null;

            if ($venta->turno_islero_id) {
                $turno = TurnoIslero::find($venta->turno_islero_id);

                if (!$turno) {
                    throw new HttpException(
                        422,
                        'No se encontró el turno asociado a la venta.'
                    );
                }

                if ($turno->estado === 'cerrado') {
                    throw new HttpException(
                        422,
                        'No se puede anular la venta porque el turno donde fue realizada ya se encuentra cerrado.'
                    );
                }
            }

            $bodegaId = (int) $venta->bodega_id;

            if (!$bodegaId) {
                throw new HttpException(
                    422,
                    'La venta no tiene bodega asociada.'
                );
            }


            foreach ($venta->detalles as $detalle) {

                if ($venta->tipo_origen === 'combustible') {
                    continue;
                }

                $this->ventaRepository->incrementInventario(
                    $detalle->producto_id,
                    $bodegaId,
                    (float) $detalle->cantidad
                );

                $this->ventaRepository->createMovimientoInventario([
                    'tipo_movimiento' => 'anulacion_venta',
                    'producto_id' => $detalle->producto_id,
                    'bodega_origen_id' => null,
                    'bodega_destino_id' => $bodegaId,
                    'cantidad' => $detalle->cantidad,
                    'observacion' => "Anulación Venta #{$venta->id}",
                    'user_id' => $userId,
                ]);
            }


            if (empty($venta->turno_islero_id)) {

                foreach ($venta->pagos as $pago) {


                    if ($pago->metodo_pago === 'credito') {
                        continue;
                    }


                    if (!$pago->caja_id) {
                        continue;
                    }

                    $this->ventaRepository->createMovimientoCaja([
                        'caja_id' => $pago->caja_id,
                        'tipo_movimiento' => 'egreso',
                        'categoria_movimiento' => 'anulacion_venta',
                        'origen_modulo' => 'ventas',
                        'origen_id' => $venta->id,
                        'medio_pago' => $pago->metodo_pago,
                        'monto' => $pago->monto,
                        'descripcion' => "Anulación Venta #{$venta->id}: {$motivoAnulacion}",
                        'user_id' => $userId,
                        'fecha_movimiento' => now(),
                    ]);
                }
            }



            if (
                (float) $venta->saldo_pendiente > 0 &&
                $venta->cliente_id
            ) {

                $cliente = $this->ventaRepository->findClienteById(
                    $venta->cliente_id
                );

                if ($cliente) {

                    $saldoAnterior = (float) $cliente->saldo_credito;

                    $saldoNuevo =
                        $saldoAnterior -
                        (float) $venta->saldo_pendiente;

                    if ($saldoNuevo < 0) {
                        $saldoNuevo = 0;
                    }

                    $this->ventaRepository->updateCliente(
                        $cliente,
                        [
                            'saldo_credito' => $saldoNuevo,
                        ]
                    );

                    $this->ventaRepository->createMovimientoCartera([
                        'cliente_id' => $cliente->id,
                        'tipo_movimiento' => 'anulacion',
                        'origen_modulo' => 'ventas',
                        'origen_id' => $venta->id,
                        'valor' => $venta->saldo_pendiente,
                        'saldo_anterior' => $saldoAnterior,
                        'saldo_nuevo' => $saldoNuevo,
                        'medio_pago' => null,
                        'descripcion' =>
                            "Anulación venta crédito #{$venta->id}: {$motivoAnulacion}",
                        'user_id' => $userId,
                        'fecha_movimiento' => now(),
                    ]);
                }
            }

            // Reversión de anticipos aplicados a esta venta si existen
            $aplicaciones = AplicacionAnticipoCliente::where('venta_id', $venta->id)
                ->where('estado', 'aplicada')
                ->get();

            foreach ($aplicaciones as $aplicacion) {
                $anticipo = AnticipoCliente::lockForUpdate()->find($aplicacion->anticipo_cliente_id);
                if ($anticipo) {
                    $anticipo->saldo_disponible = round((float) $anticipo->saldo_disponible + (float) $aplicacion->monto_aplicado, 2);
                    if ($anticipo->estado === 'agotado') {
                        $anticipo->estado = 'disponible';
                    }
                    $anticipo->save();
                }

                $aplicacion->update([
                    'estado' => 'revertida',
                    'revertido_at' => now(),
                    'revertido_by' => $userId,
                ]);
            }

            if ($aplicaciones->isNotEmpty() && $venta->cliente_id) {
                $clienteAnticipo = Cliente::lockForUpdate()->find($venta->cliente_id);
                if ($clienteAnticipo) {
                    $nuevoSaldoAFavor = (float) AnticipoCliente::query()
                        ->where('cliente_id', $clienteAnticipo->id)
                        ->where('estado', 'disponible')
                        ->sum('saldo_disponible');

                    $this->ventaRepository->updateCliente($clienteAnticipo, [
                        'saldo_a_favor' => round($nuevoSaldoAFavor, 2),
                    ]);
                }
            }

            $this->ventaRepository->updateVenta(
                $venta,
                [
                    'estado' => 'anulada',
                    'estado_pago' => 'anulada',
                    'motivo_anulacion' => $motivoAnulacion,
                    'user_anulacion_id' => $userId,
                    'fecha_anulacion' => now(),
                ]
            );

            if ($turno) {
                $totalesTurno = $this->turnoRepository->recalcularTotalesTurno(
                    $turno->id
                );

                $turno->update($totalesTurno);
            }


            return $this->findById($venta->id);
        });
    }

    /**
     * Crear venta de combustible
     */
    public function createCombustible(CreateVentaCombustibleDTO $dto): Venta
    {
        $ventaFinal = DB::transaction(function () use ($dto) {

            $turnoAbierto = $this->ventaRepository->getTurnoAbiertoByUser($dto->user_id);

            if (!$turnoAbierto) {
                throw new HttpException(422, 'No tienes un turno de islero abierto.');
            }

            $user = $this->ventaRepository->findUserById($dto->user_id);

            if (!$user) {
                throw new HttpException(422, 'Usuario no encontrado.');
            }

            if (!$user->bodega_id) {
                throw new HttpException(422, 'El usuario no tiene una bodega asignada.');
            }

            $bodegaId = (int) $user->bodega_id;

            $manguera = $this->ventaRepository->findMangueraById($dto->manguera_id);

            if (!$manguera) {
                throw new HttpException(
                    422,
                    'Manguera no encontrada.'
                );
            }

            if (!$manguera->producto->categoriaProducto) {
                throw new HttpException(
                    422,
                    'El producto combustible no tiene categoría.'
                );
            }

            $destinoRecaudoId =
                $manguera->producto
                ->categoriaProducto
                ->destino_recaudo_id;

            if (!$destinoRecaudoId) {

                throw new HttpException(
                    422,
                    'La categoría del combustible no tiene destino de recaudo.'
                );
            }

            if (!$manguera) {
                throw new HttpException(422, 'Manguera no encontrada.');
            }

            if (!(bool) $manguera->is_active) {
                throw new HttpException(422, 'La manguera está inactiva.');
            }

            if ((int) $manguera->bomba?->estacion_id !== (int) $turnoAbierto->estacion_id) {
                throw new HttpException(422, 'La manguera no pertenece a la estación del turno abierto.');
            }

            $lecturaTurno = $this->ventaRepository->getLecturaAbiertaByTurnoAndManguera(
                $turnoAbierto->id,
                $manguera->id
            );

            if (!$lecturaTurno) {
                throw new HttpException(
                    422,
                    'La manguera no está asignada a tu turno abierto o ya fue cerrada.'
                );
            }

            $precioGalon = (float) $lecturaTurno->precio_galon;

            if ($precioGalon <= 0) {
                throw new HttpException(422, 'La manguera no tiene precio de galón válido para este turno.');
            }

            $cantidadGalones = round($dto->monto / $precioGalon, 3);

            if ($cantidadGalones <= 0) {
                throw new HttpException(422, 'La cantidad de galones calculada no es válida.');
            }

            $empresaConfig = $this->configuracionEmpresaRepository?->first();
            $configFacturacion = $this->configuracionFacturacionRepository?->first();
            $facturacionActiva = (bool) ($configFacturacion && $configFacturacion->facturacion_electronica_activa);

            if ((bool) $dto->factura_electronica && !$facturacionActiva) {
                throw new HttpException(
                    422,
                    'La facturación electrónica se encuentra desactivada en la configuración del sistema.'
                );
            }

            $requiereFacturacionElectronica = $facturacionActiva && (
                (bool) $dto->factura_electronica || (bool) $configFacturacion->facturar_ventas_combustible
            );

            $calculoCombustible = $this->calcularLineaCombustiblePorMonto(
                producto: $manguera->producto,
                cantidadGalones: $cantidadGalones,
                precioGalonTurno: $precioGalon,
                montoDespachado: (float) $dto->monto,
                empresaConfig: $empresaConfig,
                exigirClasificacion: $requiereFacturacionElectronica
            );

            $total = $calculoCombustible['total'];
            $subtotal = $calculoCombustible['subtotal'];
            $impuesto = $calculoCombustible['iva_valor'];
            $soldicom = $calculoCombustible['soldicom'];
            $sobreTasa = $calculoCombustible['sobre_tasa'];
            $descuento = $calculoCombustible['descuento'];

            $montoAnticipoAplicar = 0.0;
            if ($dto->usar_anticipo) {
                if (!$dto->cliente_id) {
                    throw new HttpException(422, 'Debe seleccionar un cliente para aplicar anticipos en venta de combustible.');
                }

                $clienteAnticipo = Cliente::where('id', $dto->cliente_id)->lockForUpdate()->first();
                if (!$clienteAnticipo) {
                    throw new HttpException(404, 'Cliente no encontrado.');
                }

                $saldoAFavorDisponible = (float) AnticipoCliente::where('cliente_id', $clienteAnticipo->id)
                    ->where('estado', 'disponible')
                    ->where('saldo_disponible', '>', 0)
                    ->sum('saldo_disponible');

                if ($saldoAFavorDisponible <= 0) {
                    throw new HttpException(422, 'El cliente seleccionado no cuenta con anticipos o saldo a favor disponible.');
                }

                $montoDeseado = ($dto->monto_anticipo !== null && $dto->monto_anticipo > 0)
                    ? (float) $dto->monto_anticipo
                    : (float) $total;

                $montoAnticipoAplicar = min($montoDeseado, $saldoAFavorDisponible, (float) $total);

                if ($montoAnticipoAplicar <= 0) {
                    throw new HttpException(422, 'El monto de anticipo a aplicar debe ser mayor a cero.');
                }
            }

            $montoRestante = round($total - $montoAnticipoAplicar, 2);
            $cliente = null;

            if ($dto->tipo_venta === 'credito') {
                $totalPagado = $montoAnticipoAplicar;
                $saldoPendiente = $montoRestante;
                $pagoDirecto = 0.0;

                if ($saldoPendiente > 0) {
                    if (!$dto->cliente_id) {
                        throw new HttpException(422, 'Debe seleccionar un cliente para ventas a crédito.');
                    }

                    $cliente = $this->ventaRepository->findClienteById($dto->cliente_id);

                    if (!$cliente) {
                        throw new HttpException(422, 'Cliente no encontrado.');
                    }

                    if (!(bool) $cliente->maneja_credito) {
                        throw new HttpException(422, 'El cliente no tiene crédito habilitado.');
                    }

                    $cupoDisponible = (float) $cliente->cupo_credito - (float) $cliente->saldo_credito;

                    if ($cupoDisponible < $saldoPendiente) {
                        throw new HttpException(422, 'El cliente no tiene cupo suficiente.');
                    }
                }
            } else {
                $pagoDirecto = $montoRestante;
                $totalPagado = round($montoAnticipoAplicar + $pagoDirecto, 2);
                $saldoPendiente = 0.0;
            }

            if ($dto->factura_electronica) {
                if (!$dto->cliente_id) {
                    throw new HttpException(422, 'Debe seleccionar un cliente para generar factura electrónica.');
                }
                $clienteReq = $this->ventaRepository->findClienteById($dto->cliente_id);
                if (!$clienteReq) {
                    throw new HttpException(422, 'El cliente seleccionado no existe.');
                }
                $nombreReq = trim(($clienteReq->nombre ?? '') . ' ' . ($clienteReq->apellidos ?? ''));
                if (empty($clienteReq->documento) || empty($nombreReq)) {
                    throw new HttpException(422, 'El cliente seleccionado no tiene documento o nombre registrado.');
                }
            }

            if ($dto->tipo_venta !== 'credito' && $pagoDirecto > 0) {
                $tipoCaja = $this->resolverTipoCaja(
                    $dto->metodo_pago
                );

                $caja = $this->ventaRepository->getCajaAbiertaByTipoAndDestino($tipoCaja, $destinoRecaudoId);
                if (!$caja) {
                    throw new HttpException(422, "No hay caja {$tipoCaja} abierta.");
                }
            } else {
                $caja = null;
            }

            $numeracion = $this->resolverNumeracionVenta(
                requiereFacturaElectronica: $requiereFacturacionElectronica,
                empresaId: $empresaConfig?->id,
                prefijoDefault: 'POS'
            );

            $venta = $this->ventaRepository->createVenta([
                'prefijo' => $numeracion['prefijo'],
                'numero_factura' => $numeracion['numero_factura'],
                'cliente_id' => $dto->cliente_id,
                'user_id' => $dto->user_id,
                'bodega_id' => $bodegaId,
                'turno_islero_id' => $turnoAbierto->id,
                'tipo_venta' => $dto->tipo_venta,
                'tipo_origen' => 'combustible',
                'factura_electronica' => (bool) $dto->factura_electronica,
                'estado' => 'confirmada',
                'estado_pago' => $saldoPendiente <= 0 ? 'pagado' : ($totalPagado > 0 ? 'parcial' : 'pendiente'),
                'subtotal' => $subtotal,
                'descuento' => $descuento,
                'impuesto' => $impuesto,
                'soldicom' => $soldicom,
                'sobre_tasa' => $sobreTasa,
                'total' => $total,
                'total_pagado' => $totalPagado,
                'saldo_pendiente' => $saldoPendiente,
                'fecha_venta' => now(),
                'fecha_vencimiento' => ($saldoPendiente > 0 && $cliente) ? now()->addDays($cliente->dias_credito) : null,
                'observacion' => $dto->observacion,
                'destino_recaudo_id' => $destinoRecaudoId,
            ]);

            if ($montoAnticipoAplicar > 0) {
                $this->aplicarAnticiposAVenta(
                    $venta,
                    $dto->cliente_id,
                    $montoAnticipoAplicar,
                    $dto->user_id
                );
            }

            $this->ventaRepository->createDetalle([
                'venta_id' => $venta->id,
                'producto_id' => $manguera->producto_id,
                'cantidad' => $calculoCombustible['cantidad'],
                'precio_unitario' => $calculoCombustible['precio_unitario'],
                'descuento' => $calculoCombustible['descuento'],
                'tipo_impuesto' => $calculoCombustible['tipo_impuesto'],
                'porcentaje_iva' => $calculoCombustible['porcentaje_iva'],
                'codigo_impuesto' => $calculoCombustible['codigo_impuesto'],
                'iva' => $calculoCombustible['iva'],
                'iva_valor' => $calculoCombustible['iva_valor'],
                'soldicom' => $calculoCombustible['soldicom'],
                'sobre_tasa' => $calculoCombustible['sobre_tasa'],
                'subtotal' => $calculoCombustible['subtotal'],
                'total' => $calculoCombustible['total'],
                'manguera_id' => $manguera->id,
            ]);

            $this->ventaRepository->decrementInventario(
                $manguera->producto_id,
                $bodegaId,
                $cantidadGalones
            );

            $this->ventaRepository->createMovimientoInventario([
                'tipo_movimiento' => 'venta',
                'producto_id' => $manguera->producto_id,
                'bodega_origen_id' => $bodegaId,
                'bodega_destino_id' => null,
                'cantidad' => $cantidadGalones,
                'observacion' => "Venta combustible #{$venta->id}",
                'user_id' => $dto->user_id,
            ]);

            if ($dto->tipo_venta !== 'credito' && $pagoDirecto > 0 && $caja) {
                $pago = $this->ventaRepository->createPago([
                    'venta_id' => $venta->id,
                    'caja_id' => $caja->id,
                    'user_id' => $dto->user_id,
                    'fecha_pago' => now(),
                    'monto' => $pagoDirecto,
                    'metodo_pago' => $dto->metodo_pago,
                    'observacion' => $dto->observacion,
                ]);

                if (empty($turnoAbierto)) {
                    $this->ventaRepository->createMovimientoCaja([
                        'caja_id' => $caja->id,
                        'tipo_movimiento' => 'ingreso',
                        'categoria_movimiento' => 'venta_combustible',
                        'origen_modulo' => 'ventas',
                        'origen_id' => $venta->id,
                        'medio_pago' => $dto->metodo_pago,
                        'monto' => $pagoDirecto,
                        'descripcion' => "Ingreso por venta combustible #{$venta->id}",
                        'user_id' => $dto->user_id,
                        'fecha_movimiento' => now(),
                    ]);
                }
            }

            if ($dto->tipo_venta === 'credito' && $saldoPendiente > 0) {
                $cliente = $this->ventaRepository->findClienteById($dto->cliente_id);

                $saldoAnterior = (float) $cliente->saldo_credito;
                $nuevoSaldo = $saldoAnterior + $saldoPendiente;

                $this->ventaRepository->updateCliente($cliente, [
                    'saldo_credito' => $nuevoSaldo,
                ]);

                $this->ventaRepository->createMovimientoCartera([
                    'cliente_id' => $cliente->id,
                    'tipo_movimiento' => 'venta_credito',
                    'origen_modulo' => 'ventas',
                    'origen_id' => $venta->id,
                    'valor' => $saldoPendiente,
                    'saldo_anterior' => $saldoAnterior,
                    'saldo_nuevo' => $nuevoSaldo,
                    'medio_pago' => null,
                    'descripcion' => "Venta combustible crédito #{$venta->id}",
                    'user_id' => $dto->user_id,
                    'fecha_movimiento' => now(),
                ]);
            }

            return $this->findById($venta->id);
        });

        $this->procesarFacturacionElectronica($ventaFinal);

        return $ventaFinal;
    }

    public function crearVentaAjusteTurno(
        TurnoIslero $turno,
        Collection $lecturas,
        int $userId
    ): ?Venta {
        $detalles = [];

        $subtotal = 0;
        $impuesto = 0;
        $total = 0;
        $destinoRecaudoId = null;
        $empresaConfig = $this->configuracionEmpresaRepository?->first();

        foreach ($lecturas as $lectura) {

            $galonesSistema = $this->ventaRepository
                ->sumGalonesCombustibleByTurnoAndManguera(
                    $turno->id,
                    $lectura->manguera_id
                );

            $galonesFisicos = (float)$lectura->galones_vendidos;

            $galonesAjuste = round(
                $galonesFisicos - $galonesSistema,
                3
            );

            if ($galonesAjuste <= 0) {
                continue;
            }

            $producto = $lectura->manguera->producto;

            if (!$producto->categoriaProducto) {
                throw new HttpException(
                    422,
                    "El producto {$producto->nombre} no tiene categoría."
                );
            }

            $destinoRecaudoId = $producto->categoriaProducto->destino_recaudo_id;

            $valor = round($galonesAjuste * (float)$lectura->precio_galon, 2);

            $calculoAjt = $this->calcularLineaCombustiblePorMonto(
                producto: $producto,
                cantidadGalones: $galonesAjuste,
                precioGalonTurno: (float) $lectura->precio_galon,
                montoDespachado: $valor,
                empresaConfig: $empresaConfig,
                exigirClasificacion: false
            );

            $subtotal = round($subtotal + $calculoAjt['subtotal'], 2);
            $impuesto = round($impuesto + $calculoAjt['iva_valor'], 2);
            $total = round($total + $calculoAjt['total'], 2);

            $detalles[] = array_merge($calculoAjt, [
                'manguera_id' => $lectura->manguera_id,
            ]);
        }

        if (count($detalles) == 0) {
            return null;
        }

        $venta = $this->ventaRepository->createVenta([
            'prefijo' => 'AJT',
            'numero_factura' => $this->ventaRepository->nextNumeroFactura(),
            'cliente_id' => null,
            'user_id' => $userId,
            'bodega_id' => $turno->usuario->bodega_id,
            'turno_islero_id' => $turno->id,
            'tipo_venta' => 'contado',
            'tipo_origen' => 'ajuste_turno',
            'estado' => 'confirmada',
            'estado_pago' => 'pagado',
            'subtotal' => $subtotal,
            'descuento' => 0,
            'impuesto' => $impuesto,
            'soldicom' => 0,
            'sobre_tasa' => 0,
            'total' => $total,
            'total_pagado' => $total,
            'saldo_pendiente' => 0,
            'fecha_venta' => now(),
            'observacion' => "Venta automática por cierre del turno #{$turno->id}",
            'destino_recaudo_id' => $destinoRecaudoId,

        ]);

        foreach ($detalles as $detalle) {

            $this->ventaRepository->createDetalle([
                'venta_id' => $venta->id,
                'producto_id' => $detalle['producto_id'],
                'manguera_id' => $detalle['manguera_id'],
                'cantidad' => $detalle['cantidad'],
                'precio_unitario' => $detalle['precio_unitario'],
                'descuento' => $detalle['descuento'],
                'tipo_impuesto' => $detalle['tipo_impuesto'],
                'porcentaje_iva' => $detalle['porcentaje_iva'],
                'codigo_impuesto' => $detalle['codigo_impuesto'],
                'iva' => $detalle['iva'],
                'iva_valor' => $detalle['iva_valor'],
                'soldicom' => $detalle['soldicom'],
                'sobre_tasa' => $detalle['sobre_tasa'],
                'subtotal' => $detalle['subtotal'],
                'total' => $detalle['total'],
            ]);
        }

        return $venta;
    }

    public function update(int $id, array $data, int $userId): Venta
    {
        return DB::transaction(function () use ($id, $data, $userId) {
            $venta = $this->findById($id);

            if ($venta->estado === 'anulada') {
                throw new HttpException(422, 'No se puede editar una venta anulada.');
            }

            $facturaEmitida = DocumentoElectronico::where('venta_id', $venta->id)
                ->where('tipo_documento', 'factura_electronica')
                ->where('estado', 'emitido')
                ->exists();

            if ($facturaEmitida) {
                throw new HttpException(
                    422,
                    'Esta venta ya tiene una factura electrónica emitida y no puede modificarse directamente. Debe realizarse el ajuste mediante una Nota Crédito o Nota Débito.'
                );
            }

            if ($venta->tipo_origen === 'combustible') {
                throw new HttpException(
                    422,
                    'Las ventas de combustible no se editan individualmente. Deben ajustarse mediante lecturas de manguera.'
                );
            }

            // Capturar cliente y saldo pendiente previa a cualquier modificación
            $oldClienteId = $venta->cliente_id;
            $oldSaldoPendiente = (float) $venta->saldo_pendiente;

            $turno = null;
            if ($venta->turno_islero_id) {
                $turno = TurnoIslero::find($venta->turno_islero_id);
                if ($turno && $turno->estado === 'cerrado') {
                    throw new HttpException(
                        422,
                        'No se puede editar la venta porque el turno donde fue realizada ya se encuentra cerrado.'
                    );
                }
            }

            if ($oldSaldoPendiente > 0 || $venta->tipo_venta === 'credito') {
                $tieneAbonos = DB::table('abonos_cartera_detalle')
                    ->where('venta_id', $venta->id)
                    ->exists();

                if ($tieneAbonos) {
                    throw new HttpException(
                        422,
                        'No se puede editar una venta a crédito que ya tiene abonos de cartera aplicados. Debe anular o editar primero los abonos correspondientes.'
                    );
                }
            }

            $bodegaId = (int) $venta->bodega_id;

            $detallesPreviosPorProducto = [];
            foreach ($venta->detalles as $detalleAntiguo) {
                $detallesPreviosPorProducto[(int) $detalleAntiguo->producto_id] = $detalleAntiguo;

                $this->ventaRepository->incrementInventario(
                    $detalleAntiguo->producto_id,
                    $bodegaId,
                    (float) $detalleAntiguo->cantidad
                );

                $this->ventaRepository->createMovimientoInventario([
                    'tipo_movimiento' => 'ajuste_edicion_venta',
                    'producto_id' => $detalleAntiguo->producto_id,
                    'bodega_origen_id' => null,
                    'bodega_destino_id' => $bodegaId,
                    'cantidad' => $detalleAntiguo->cantidad,
                    'observacion' => "Ajuste por edición de Venta #{$venta->id}",
                    'user_id' => $userId,
                ]);
            }

            $nuevosDetalles = $data['detalles'] ?? null;
            $total = (float) $venta->total;
            $destinoRecaudoId = null;

            if ($nuevosDetalles && count($nuevosDetalles) > 0) {
                $venta->detalles()->delete();

                $empresaConfig = $this->configuracionEmpresaRepository?->first();
                $configFacturacion = $this->configuracionFacturacionRepository?->first();
                $facturacionActiva = (bool) ($configFacturacion && $configFacturacion->facturacion_electronica_activa);
                $requiereFacturacionElectronica = $facturacionActiva && (
                    (bool) $venta->factura_electronica || (bool) $configFacturacion->facturar_ventas_pos
                );

                $subtotal = 0;
                $impuesto = 0;
                $soldicom = 0;
                $sobreTasa = 0;
                $descuento = 0;
                $total = 0;

                foreach ($nuevosDetalles as $detData) {
                    $producto = $this->ventaRepository->findProductoById($detData['producto_id']);
                    if (!$producto) {
                        throw new HttpException(422, "Producto {$detData['producto_id']} no existe.");
                    }

                    if (!$producto->categoriaProducto) {
                        throw new HttpException(
                            422,
                            "El producto {$producto->nombre} no tiene categoría."
                        );
                    }

                    if (!$producto->categoriaProducto->destino_recaudo_id) {
                        throw new HttpException(
                            422,
                            "La categoría del producto {$producto->nombre} no tiene destino de recaudo."
                        );
                    }

                    $prodDestinoRecaudoId = (int) $producto->categoriaProducto->destino_recaudo_id;

                    if ($destinoRecaudoId === null) {
                        $destinoRecaudoId = $prodDestinoRecaudoId;
                    } elseif ($destinoRecaudoId !== $prodDestinoRecaudoId) {
                        throw new HttpException(
                            422,
                            'No se permiten productos de diferentes destinos de recaudo en una misma venta.'
                        );
                    }

                    $inventario = $this->ventaRepository->findInventario($detData['producto_id'], $bodegaId);
                    if (!$inventario || (float) $inventario->cantidad < (float) $detData['cantidad']) {
                        throw new HttpException(422, "Stock insuficiente para {$producto->nombre}.");
                    }

                    $detallePrevio = $detallesPreviosPorProducto[(int) $detData['producto_id']] ?? null;

                    $calculoLinea = $this->calcularLineaTributaria(
                        producto: $producto,
                        cantidad: (float) $detData['cantidad'],
                        precioDigitado: (float) $detData['precio_unitario'],
                        descuentoDigitado: (float) ($detData['descuento'] ?? 0),
                        empresaConfig: $empresaConfig,
                        exigirClasificacion: $requiereFacturacionElectronica,
                        soldicom: (float) ($detData['soldicom'] ?? 0),
                        sobreTasa: (float) ($detData['sobre_tasa'] ?? 0),
                        snapshotPrevio: $detallePrevio
                    );

                    $subtotal = round($subtotal + $calculoLinea['subtotal'], 2);
                    $impuesto = round($impuesto + $calculoLinea['iva_valor'], 2);
                    $soldicom = round($soldicom + $calculoLinea['soldicom'], 2);
                    $sobreTasa = round($sobreTasa + $calculoLinea['sobre_tasa'], 2);
                    $descuento = round($descuento + $calculoLinea['descuento'], 2);
                    $total = round($total + $calculoLinea['total'], 2);

                    $this->ventaRepository->createDetalle([
                        'venta_id' => $venta->id,
                        'producto_id' => $calculoLinea['producto_id'],
                        'manguera_id' => null,
                        'cantidad' => $calculoLinea['cantidad'],
                        'precio_unitario' => $calculoLinea['precio_unitario'],
                        'descuento' => $calculoLinea['descuento'],
                        'tipo_impuesto' => $calculoLinea['tipo_impuesto'],
                        'porcentaje_iva' => $calculoLinea['porcentaje_iva'],
                        'codigo_impuesto' => $calculoLinea['codigo_impuesto'],
                        'iva' => $calculoLinea['iva'],
                        'iva_valor' => $calculoLinea['iva_valor'],
                        'soldicom' => $calculoLinea['soldicom'],
                        'sobre_tasa' => $calculoLinea['sobre_tasa'],
                        'subtotal' => $calculoLinea['subtotal'],
                        'total' => $calculoLinea['total'],
                    ]);

                    $this->ventaRepository->decrementInventario($calculoLinea['producto_id'], $bodegaId, $calculoLinea['cantidad']);

                    $this->ventaRepository->createMovimientoInventario([
                        'tipo_movimiento' => 'venta',
                        'producto_id' => $calculoLinea['producto_id'],
                        'bodega_origen_id' => $bodegaId,
                        'bodega_destino_id' => null,
                        'cantidad' => $calculoLinea['cantidad'],
                        'observacion' => "Venta (editada) #{$venta->id}",
                        'user_id' => $userId,
                    ]);
                }

                $venta->subtotal = $subtotal;
                $venta->descuento = $descuento;
                $venta->impuesto = $impuesto;
                $venta->soldicom = $soldicom;
                $venta->sobre_tasa = $sobreTasa;
                $venta->total = $total;
            }

            // Resolver destino de recaudo para asignación de cajas
            if (!$destinoRecaudoId && $venta->detalles()->exists()) {
                $primerDetalle = $venta->detalles()->with('producto.categoriaProducto')->first();
                $destinoRecaudoId = $primerDetalle?->producto?->categoriaProducto?->destino_recaudo_id;
            }

            // Sincronización de pagos (pagos_venta, total_pagado, saldo_pendiente, estado_pago)
            $tipoVenta = $data['tipo_venta'] ?? $venta->tipo_venta;
            $nuevosPagos = array_key_exists('pagos', $data) ? $data['pagos'] : null;

            if ($tipoVenta === 'credito') {
                $totalPagado = 0;
                if ($nuevosPagos !== null) {
                    $venta->pagos()->delete();
                }
            } else {
                if ($nuevosPagos !== null) {
                    if (count($nuevosPagos) === 1) {
                        $nuevosPagos[0]['monto'] = $total;
                    }

                    $sumaPagos = 0;
                    foreach ($nuevosPagos as $pago) {
                        $sumaPagos += (float) ($pago['monto'] ?? 0);
                    }
                    $sumaPagos = round($sumaPagos, 2);

                    if ($sumaPagos > $total) {
                        throw new HttpException(
                            422,
                            "El monto total de los pagos reportados ({$sumaPagos}) no puede ser superior al total de la venta ({$total})."
                        );
                    }

                    $venta->pagos()->delete();

                    foreach ($nuevosPagos as $pago) {
                        $metodoPago = $pago['metodo_pago'];
                        $montoPago = round((float) $pago['monto'], 2);

                        if ($montoPago <= 0) {
                            continue;
                        }

                        $tipoCaja = $this->resolverTipoCaja($metodoPago);
                        $caja = $this->ventaRepository->getCajaAbiertaByTipoAndDestino($tipoCaja, $destinoRecaudoId);
                        if (!$caja) {
                            throw new HttpException(422, "No existe caja abierta para el destino de recaudo configurado.");
                        }

                        $this->ventaRepository->createPago([
                            'venta_id' => $venta->id,
                            'caja_id' => $caja->id,
                            'user_id' => $userId,
                            'fecha_pago' => now(),
                            'monto' => $montoPago,
                            'metodo_pago' => $metodoPago,
                            'observacion' => $pago['observacion'] ?? null,
                        ]);
                    }

                    $totalPagado = $sumaPagos;
                } else {
                    $sumaPagosExistentes = round((float) $venta->pagos()->sum('monto'), 2);

                    if ($sumaPagosExistentes > $total) {
                        throw new HttpException(
                            422,
                            "El monto total de los pagos ya registrados ({$sumaPagosExistentes}) supera el nuevo total de la venta ({$total}). Debe enviar la distribución de pagos ajustada."
                        );
                    }

                    $totalPagado = $sumaPagosExistentes;
                }
            }

            // Sincronizar movimientos de caja únicamente para ventas sin turno
            if (empty($venta->turno_islero_id)) {
                $this->ventaRepository->deleteMovimientosCajaByVenta($venta->id);

                if ($tipoVenta !== 'credito') {
                    $pagosActuales = $venta->pagos()->get();
                    foreach ($pagosActuales as $pago) {
                        if ($pago->metodo_pago === 'credito' || (float) $pago->monto <= 0) {
                            continue;
                        }
                        if (!$pago->caja_id) {
                            continue;
                        }

                        $this->ventaRepository->createMovimientoCaja([
                            'caja_id' => $pago->caja_id,
                            'tipo_movimiento' => 'ingreso',
                            'categoria_movimiento' => 'venta',
                            'origen_modulo' => 'ventas',
                            'origen_id' => $venta->id,
                            'medio_pago' => $pago->metodo_pago,
                            'monto' => $pago->monto,
                            'descripcion' => "Ingreso por Venta (editada) #{$venta->id}",
                            'user_id' => $userId,
                            'fecha_movimiento' => now(),
                        ]);
                    }
                }
            }

            $saldoPendiente = round($total - $totalPagado, 2);
            if ($saldoPendiente <= 0) {
                $totalPagado = $total;
                $saldoPendiente = 0;
                $estadoPago = 'pagado';
            } else {
                $estadoPago = $totalPagado > 0 ? 'parcial' : 'pendiente';
            }

            $venta->total_pagado = $totalPagado;
            $venta->saldo_pendiente = $saldoPendiente;
            $venta->estado_pago = $estadoPago;

            $nuevoClienteId = array_key_exists('cliente_id', $data) ? $data['cliente_id'] : $oldClienteId;
            $newSaldoPendiente = (float) $venta->saldo_pendiente;

            // Manejo de saldos de cartera de clientes
            if ($nuevoClienteId !== $oldClienteId) {
                // Caso A: El cliente cambió
                if ($oldClienteId && $oldSaldoPendiente > 0) {
                    $oldCliente = $this->ventaRepository->findClienteById($oldClienteId);
                    if ($oldCliente) {
                        $saldoAnt = (float) $oldCliente->saldo_credito;
                        $saldoNuev = max(0, round($saldoAnt - $oldSaldoPendiente, 2));
                        $this->ventaRepository->updateCliente($oldCliente, ['saldo_credito' => $saldoNuev]);

                        $this->ventaRepository->createMovimientoCartera([
                            'cliente_id' => $oldCliente->id,
                            'tipo_movimiento' => 'ajuste_edicion_venta',
                            'origen_modulo' => 'ventas',
                            'origen_id' => $venta->id,
                            'valor' => $oldSaldoPendiente,
                            'saldo_anterior' => $saldoAnt,
                            'saldo_nuevo' => $saldoNuev,
                            'medio_pago' => null,
                            'descripcion' => "Ajuste por cambio de cliente en venta #{$venta->id}",
                            'user_id' => $userId,
                            'fecha_movimiento' => now(),
                        ]);
                    }
                }

                if ($nuevoClienteId && $newSaldoPendiente > 0) {
                    $newCliente = $this->ventaRepository->findClienteById($nuevoClienteId);
                    if (!$newCliente) {
                        throw new HttpException(422, 'Cliente no encontrado.');
                    }
                    if (!(bool) $newCliente->maneja_credito) {
                        throw new HttpException(422, 'El nuevo cliente no tiene crédito habilitado.');
                    }

                    $cupoDisp = (float) $newCliente->cupo_credito - (float) $newCliente->saldo_credito;
                    if ($cupoDisp < $newSaldoPendiente) {
                        throw new HttpException(422, 'El nuevo cliente no tiene cupo de crédito suficiente.');
                    }

                    $saldoAnt = (float) $newCliente->saldo_credito;
                    $saldoNuev = round($saldoAnt + $newSaldoPendiente, 2);
                    $this->ventaRepository->updateCliente($newCliente, ['saldo_credito' => $saldoNuev]);

                    $this->ventaRepository->createMovimientoCartera([
                        'cliente_id' => $newCliente->id,
                        'tipo_movimiento' => 'venta_credito',
                        'origen_modulo' => 'ventas',
                        'origen_id' => $venta->id,
                        'valor' => $newSaldoPendiente,
                        'saldo_anterior' => $saldoAnt,
                        'saldo_nuevo' => $saldoNuev,
                        'medio_pago' => null,
                        'descripcion' => "Venta crédito (reasignada) #{$venta->id}",
                        'user_id' => $userId,
                        'fecha_movimiento' => now(),
                    ]);
                }
            } else {
                // Caso B: Es el mismo cliente, se ajusta la diferencia de saldo pendiente
                $diferencia = round($newSaldoPendiente - $oldSaldoPendiente, 2);

                if ($oldClienteId && $diferencia != 0) {
                    $cliente = $this->ventaRepository->findClienteById($oldClienteId);
                    if ($cliente) {
                        if ($diferencia > 0) {
                            if (!(bool) $cliente->maneja_credito) {
                                throw new HttpException(422, 'El cliente no tiene crédito habilitado.');
                            }

                            $cupoDisp = (float) $cliente->cupo_credito - (float) $cliente->saldo_credito;
                            if ($cupoDisp < $diferencia) {
                                throw new HttpException(422, 'El cliente no tiene cupo de crédito suficiente para el incremento.');
                            }
                        }

                        $saldoAnt = (float) $cliente->saldo_credito;
                        $saldoNuev = max(0, round($saldoAnt + $diferencia, 2));
                        $this->ventaRepository->updateCliente($cliente, ['saldo_credito' => $saldoNuev]);

                        $this->ventaRepository->createMovimientoCartera([
                            'cliente_id' => $cliente->id,
                            'tipo_movimiento' => $diferencia > 0 ? 'venta_credito' : 'ajuste_edicion_venta',
                            'origen_modulo' => 'ventas',
                            'origen_id' => $venta->id,
                            'valor' => abs($diferencia),
                            'saldo_anterior' => $saldoAnt,
                            'saldo_nuevo' => $saldoNuev,
                            'medio_pago' => null,
                            'descripcion' => "Ajuste por edición de venta crédito #{$venta->id}",
                            'user_id' => $userId,
                            'fecha_movimiento' => now(),
                        ]);
                    }
                }
            }

            if (array_key_exists('cliente_id', $data)) {
                $venta->cliente_id = $data['cliente_id'];
            }
            if (isset($data['tipo_venta'])) {
                $venta->tipo_venta = $data['tipo_venta'];
            }
            if (isset($data['observacion'])) {
                $venta->observacion = $data['observacion'];
            }

            $venta->save();

            if ($turno) {
                $totalesTurno = $this->turnoRepository->recalcularTotalesTurno($turno->id);
                $turno->update($totalesTurno);
            }

            return $this->findById($venta->id);
        });
    }

    /**
     * Resolver la numeración de una venta sincronizando con la resolución activa de factura electrónica
     * cuando la venta requiere emisión electrónica, o usando el consecutivo comercial en caso contrario.
     */
    protected function resolverNumeracionVenta(
        bool $requiereFacturaElectronica,
        ?int $empresaId = null,
        string $prefijoDefault = 'POS'
    ): array {
        if ($requiereFacturaElectronica && $this->resolucionFacturacionService) {
            $reserva = $this->resolucionFacturacionService->reservarConsecutivoFactura($empresaId);
            return [
                'prefijo' => $reserva['prefijo'] ?: $prefijoDefault,
                'numero_factura' => (string) $reserva['consecutivo'],
                'resolucion_id' => $reserva['resolucion_id'] ?? null,
            ];
        }

        return [
            'prefijo' => $prefijoDefault,
            'numero_factura' => $this->ventaRepository->nextNumeroFactura(),
            'resolucion_id' => null,
        ];
    }

    /**
     * Cálculo tributario centralizado para productos generales y lubricantes (POS y edición).
     * Consulta la clasificación real del producto (o snapshot previo en edición) y aplica
     * responsable_iva y maneja_iva_incluido de ConfiguracionEmpresa.
     */
    public function calcularLineaTributaria(
        Producto $producto,
        float $cantidad,
        float $precioDigitado,
        float $descuentoDigitado = 0.0,
        ?ConfiguracionEmpresa $empresaConfig = null,
        bool $exigirClasificacion = false,
        float $soldicom = 0.0,
        float $sobreTasa = 0.0,
        ?DetalleVenta $snapshotPrevio = null
    ): array {
        $cant = round($cantidad, 3);
        $precioInput = round($precioDigitado, 2);
        $descuentoInput = max(0.0, round($descuentoDigitado, 2));
        $soldicomVal = max(0.0, round($soldicom, 2));
        $sobreTasaVal = max(0.0, round($sobreTasa, 2));

        // 1. Determinar clasificación y tarifa efectiva (respetando snapshot histórico si se edita la misma línea,
        // incluyendo ventas anteriores a las nuevas migraciones que tenían tarifa histórica en 'iva').
        $tipoImpuesto = null;
        $porcentajeIva = null;
        $codigoImpuesto = null;

        if ($snapshotPrevio) {
            $tipoPrevio = $snapshotPrevio->tipo_impuesto !== null && trim((string) $snapshotPrevio->tipo_impuesto) !== ''
                ? strtolower(trim((string) $snapshotPrevio->tipo_impuesto))
                : null;
            $tarifaPreviaRaw = $snapshotPrevio->porcentaje_iva ?? $snapshotPrevio->iva ?? null;
            $tarifaPrevia = ($tarifaPreviaRaw !== null && is_numeric($tarifaPreviaRaw))
                ? round((float) $tarifaPreviaRaw, 2)
                : null;

            if ($tipoPrevio !== null) {
                $tipoImpuesto = $tipoPrevio;
                $porcentajeIva = $tarifaPrevia ?? 0.0;
                $codigoImpuesto = $snapshotPrevio->codigo_impuesto
                    ?: ($tipoImpuesto !== Producto::TIPO_IMPUESTO_EXCLUIDO
                        ? ($this->catalogosFacturacionService?->resolverCodigoImpuestoProducto($tipoImpuesto) ?? '1')
                        : null);
            } elseif ($tarifaPrevia !== null && $tarifaPrevia > 0) {
                // Preservar tarifa histórica de ventas previas a las nuevas migraciones
                $tipoImpuesto = Producto::TIPO_IMPUESTO_GRAVADO;
                $porcentajeIva = $tarifaPrevia;
                $codigoImpuesto = $snapshotPrevio->codigo_impuesto
                    ?: ($this->catalogosFacturacionService?->resolverCodigoImpuestoProducto(Producto::TIPO_IMPUESTO_GRAVADO) ?? '1');
            }
        }

        if ($tipoImpuesto === null) {
            if ($producto->tieneClasificacionTributariaValida()) {
                $tipoImpuesto = strtolower(trim((string) $producto->tipo_impuesto));
                $porcentajeIva = round((float) $producto->porcentaje_iva, 2);
                $codigoImpuesto = $producto->codigo_impuesto
                    ?: $this->catalogosFacturacionService?->resolverCodigoImpuestoProducto($tipoImpuesto);
            } elseif ($exigirClasificacion) {
                throw new HttpException(
                    422,
                    "El producto '{$producto->nombre}' (ID {$producto->id}) está pendiente de clasificación tributaria (gravado, exento o excluido). Configure su tratamiento tributario antes de emitir factura electrónica."
                );
            }
        }

        // 2. Resolver escenario responsable_iva === false sin guardar combinaciones inconsistentes ('gravado' con 0%)
        $responsableIva = $empresaConfig ? (bool) $empresaConfig->responsable_iva : true;
        $manejaIvaIncluido = $empresaConfig ? (bool) $empresaConfig->maneja_iva_incluido : false;

        if (!$responsableIva) {
            $porcentajeEfectivo = 0.0;
            if ($tipoImpuesto === Producto::TIPO_IMPUESTO_GRAVADO) {
                $tipoImpuesto = Producto::TIPO_IMPUESTO_EXCLUIDO;
                $codigoImpuesto = null;
            }
        } else {
            $porcentajeEfectivo = ($tipoImpuesto === Producto::TIPO_IMPUESTO_GRAVADO && $porcentajeIva !== null && $porcentajeIva > 0)
                ? $porcentajeIva
                : 0.0;
        }

        // 3. Si es edición de una línea existente ($snapshotPrevio), verificar si el precio unitario recibido
        // ya corresponde al precio base sin IVA guardado previamente en detalle_ventas para no descontar el IVA dos veces.
        $precioPrevioGuardado = $snapshotPrevio !== null ? round((float) $snapshotPrevio->precio_unitario, 2) : null;
        $esPrecioBaseExistente = $snapshotPrevio !== null
            && $precioPrevioGuardado !== null
            && abs($precioInput - $precioPrevioGuardado) < 0.01;

        if ($esPrecioBaseExistente) {
            $cantPrevia = round((float) $snapshotPrevio->cantidad, 3);
            $descPrevio = round((float) ($snapshotPrevio->descuento ?? 0.0), 2);

            // Si la línea existente no cambió ni en cantidad, ni en precio, ni en descuento, preservar su cálculo exacto
            if (
                abs($cant - $cantPrevia) < 0.0001
                && abs($descuentoInput - $descPrevio) < 0.01
                && round((float) $snapshotPrevio->subtotal, 2) > 0
            ) {
                $baseImponible = round((float) $snapshotPrevio->subtotal, 2);
                $ivaValor = $porcentajeEfectivo > 0
                    ? round((float) ($snapshotPrevio->iva_valor ?? round($baseImponible * ($porcentajeEfectivo / 100.0), 2)), 2)
                    : 0.0;
                $totalLinea = round($baseImponible + $ivaValor + $soldicomVal + $sobreTasaVal, 2);

                return [
                    'producto_id' => $producto->id,
                    'cantidad' => $cant,
                    'precio_unitario' => $precioPrevioGuardado,
                    'descuento' => $descPrevio,
                    'tipo_impuesto' => $tipoImpuesto,
                    'porcentaje_iva' => $tipoImpuesto !== null ? $porcentajeEfectivo : null,
                    'codigo_impuesto' => $codigoImpuesto,
                    'iva' => $porcentajeEfectivo,
                    'iva_valor' => $ivaValor,
                    'soldicom' => $soldicomVal,
                    'sobre_tasa' => $sobreTasaVal,
                    'subtotal' => $baseImponible,
                    'total' => $totalLinea,
                ];
            }
        }

        // 4. Calcular base, descuento, IVA y total cuando maneja_iva_incluido = true sobre precio con IVA incluido
        if ($porcentajeEfectivo > 0 && $manejaIvaIncluido && !$esPrecioBaseExistente) {
            $factor = 1.0 + ($porcentajeEfectivo / 100.0);
            $brutoConIva = round($cant * $precioInput, 2);
            $descuentoConIva = min($descuentoInput, $brutoConIva);
            $netoConIva = max(0.0, round($brutoConIva - $descuentoConIva, 2));

            // Calcular la base imponible directamente desde el neto con IVA para evitar error de redondeo acumulado
            $baseImponible = round($netoConIva / $factor, 2);
            $ivaValor = max(0.0, round($netoConIva - $baseImponible, 2));

            if ($descuentoConIva > 0) {
                $precioUnitarioBase = round($precioInput / $factor, 2);
                $valorAntesDescuentoBase = round($cant * $precioUnitarioBase, 2);
                $descuentoBase = max(0.0, round($valorAntesDescuentoBase - $baseImponible, 2));
                $baseImponible = round($valorAntesDescuentoBase - $descuentoBase, 2);
                $ivaValor = max(0.0, round($netoConIva - $baseImponible, 2));
            } else {
                $descuentoBase = 0.0;
                $precioUnitarioBase = $cant > 0
                    ? round($baseImponible / $cant, 2)
                    : round($precioInput / $factor, 2);
            }

            $totalLinea = round($baseImponible + $ivaValor + $soldicomVal + $sobreTasaVal, 2);

            return [
                'producto_id' => $producto->id,
                'cantidad' => $cant,
                'precio_unitario' => $precioUnitarioBase,
                'descuento' => $descuentoBase,
                'tipo_impuesto' => $tipoImpuesto,
                'porcentaje_iva' => $tipoImpuesto !== null ? $porcentajeEfectivo : null,
                'codigo_impuesto' => $codigoImpuesto,
                'iva' => $porcentajeEfectivo,
                'iva_valor' => $ivaValor,
                'soldicom' => $soldicomVal,
                'sobre_tasa' => $sobreTasaVal,
                'subtotal' => $baseImponible,
                'total' => $totalLinea,
            ];
        }

        // 5. Caso precios base sin IVA incluido (o línea existente ya en base, o tarifa 0% / exento / excluido / pendiente)
        $valorAntesDescuento = round($cant * $precioInput, 2);
        $descuentoBase = min($descuentoInput, $valorAntesDescuento);
        $baseImponible = round($valorAntesDescuento - $descuentoBase, 2);
        $ivaValor = $porcentajeEfectivo > 0
            ? round($baseImponible * ($porcentajeEfectivo / 100.0), 2)
            : 0.0;
        $totalLinea = round($baseImponible + $ivaValor + $soldicomVal + $sobreTasaVal, 2);

        return [
            'producto_id' => $producto->id,
            'cantidad' => $cant,
            'precio_unitario' => $precioInput,
            'descuento' => $descuentoBase,
            'tipo_impuesto' => $tipoImpuesto,
            'porcentaje_iva' => $tipoImpuesto !== null ? $porcentajeEfectivo : null,
            'codigo_impuesto' => $codigoImpuesto,
            'iva' => $porcentajeEfectivo,
            'iva_valor' => $ivaValor,
            'soldicom' => $soldicomVal,
            'sobre_tasa' => $sobreTasaVal,
            'subtotal' => $baseImponible,
            'total' => $totalLinea,
        ];
    }

    /**
     * Cálculo tributario centralizado para ventas de combustible en isla (monto fijo despachado en surtidor).
     * Preserva el total despachado ($dto->monto) para cuadre de lecturas de manguera y turno,
     * desglosando base e IVA según la clasificación explícita del producto combustible.
     */
    public function calcularLineaCombustiblePorMonto(
        Producto $producto,
        float $cantidadGalones,
        float $precioGalonTurno,
        float $montoDespachado,
        ?ConfiguracionEmpresa $empresaConfig = null,
        bool $exigirClasificacion = false
    ): array {
        $cant = round($cantidadGalones, 3);
        $precioGalon = round($precioGalonTurno, 2);
        // Calcular el total exacto como galones (3 decimales) × precio vigente del galón
        // para preservar intacto el precio unitario del galón en ventas y facturas DIAN.
        $montoTotal = round($cant * $precioGalon, 2);

        $tipoImpuesto = null;
        $porcentajeIva = null;
        $codigoImpuesto = null;

        if ($producto->tieneClasificacionTributariaValida()) {
            $tipoImpuesto = strtolower(trim((string) $producto->tipo_impuesto));
            $porcentajeIva = round((float) $producto->porcentaje_iva, 2);
            $codigoImpuesto = $producto->codigo_impuesto
                ?: $this->catalogosFacturacionService?->resolverCodigoImpuestoProducto($tipoImpuesto);
        } elseif ($exigirClasificacion) {
            throw new HttpException(
                422,
                "El combustible '{$producto->nombre}' (ID {$producto->id}) no tiene clasificación tributaria configurada (gravado, exento o excluido). Configure su tratamiento tributario antes de emitir factura electrónica."
            );
        }

        $responsableIva = $empresaConfig ? (bool) $empresaConfig->responsable_iva : true;

        if (!$responsableIva) {
            $porcentajeEfectivo = 0.0;
            if ($tipoImpuesto === Producto::TIPO_IMPUESTO_GRAVADO) {
                $tipoImpuesto = Producto::TIPO_IMPUESTO_EXCLUIDO;
                $codigoImpuesto = null;
            }
        } else {
            $porcentajeEfectivo = ($tipoImpuesto === Producto::TIPO_IMPUESTO_GRAVADO && $porcentajeIva !== null && $porcentajeIva > 0)
                ? $porcentajeIva
                : 0.0;
        }

        if ($porcentajeEfectivo > 0) {
            $factor = 1.0 + ($porcentajeEfectivo / 100.0);
            $baseImponible = round($montoTotal / $factor, 2);
            $ivaValor = max(0.0, round($montoTotal - $baseImponible, 2));
            $precioUnitarioBase = $cant > 0 ? round($baseImponible / $cant, 2) : round($precioGalonTurno / $factor, 2);
        } else {
            $baseImponible = $montoTotal;
            $ivaValor = 0.0;
            $precioUnitarioBase = round($precioGalonTurno, 2);
        }

        return [
            'producto_id' => $producto->id,
            'cantidad' => $cant,
            'precio_unitario' => $precioUnitarioBase,
            'descuento' => 0.0,
            'tipo_impuesto' => $tipoImpuesto,
            'porcentaje_iva' => $tipoImpuesto !== null ? $porcentajeEfectivo : null,
            'codigo_impuesto' => $codigoImpuesto,
            'iva' => $porcentajeEfectivo,
            'iva_valor' => $ivaValor,
            'soldicom' => 0.0,
            'sobre_tasa' => 0.0,
            'subtotal' => $baseImponible,
            'total' => $montoTotal,
        ];
    }

    /**
     * Procesar la facturación electrónica de forma desacoplada de la venta comercial.
     * Protege contra emisiones duplicadas o concurrentes y no revierte la venta comercial.
     */
    public function procesarFacturacionElectronica(Venta $venta): ?DocumentoElectronico
    {
        $documento = null;
        $adquirioCandadoEnvio = false;

        try {
            if (!$this->configuracionFacturacionRepository) {
                return null;
            }

            $configFacturacion = $this->configuracionFacturacionRepository->first();

            if (!$configFacturacion || !$configFacturacion->facturacion_electronica_activa) {
                return null;
            }

            // Verificar si la venta requiere facturación electrónica (explícita o por regla de origen)
            if (!$venta->factura_electronica) {
                if ($venta->tipo_origen === 'pos' && !$configFacturacion->facturar_ventas_pos) {
                    return null;
                }

                if ($venta->tipo_origen === 'combustible' && !$configFacturacion->facturar_ventas_combustible) {
                    return null;
                }

                if (!in_array($venta->tipo_origen, ['pos', 'combustible'], true)) {
                    return null;
                }
            }

            // Cargar relaciones necesarias si no están cargadas
            $venta->loadMissing([
                'cliente.pais',
                'cliente.ciudad',
                'detalles.producto.unidadMedida',
                'pagos',
                'usuario',
            ]);

            $empresaConfig = $this->configuracionEmpresaRepository?->first();
            $resolucionConfig = $this->resolucionFacturacionRepository?->findByTipoDocumento('factura', $empresaConfig?->id)
                ?? $this->resolucionFacturacionRepository?->findByTipoDocumento('factura_electronica', $empresaConfig?->id);

            // Validar que exista una resolución activa y con número de resolución no vacío
            if (!$resolucionConfig || !$resolucionConfig->is_active || empty($resolucionConfig->numero_resolucion)) {
                \Illuminate\Support\Facades\Log::warning("No existe resolución de facturación electrónica activa o válida para el tipo 'factura' en venta #{$venta->id}.");
                return null;
            }

            // Protección contra duplicados y concurrencia bajo bloqueo pesimista de la venta y sus documentos
            $preparacion = DB::transaction(function () use ($venta, $empresaConfig, $configFacturacion, $resolucionConfig) {
                $ventaLock = Venta::where('id', $venta->id)->lockForUpdate()->first();
                if (!$ventaLock) {
                    return ['accion' => 'abortar', 'documento' => null];
                }

                $existentes = DocumentoElectronico::where('venta_id', $ventaLock->id)
                    ->where('tipo_documento', 'factura_electronica')
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->get();

                // 1. Si ya existe una factura emitida/aceptada con CUFE, retornar sin retransmitir
                foreach ($existentes as $docExistente) {
                    if (
                        in_array(strtolower((string) $docExistente->estado), ['emitido', 'aceptado', 'aprobado'], true)
                        && !empty($docExistente->cufe)
                    ) {
                        return ['accion' => 'ya_emitido', 'documento' => $docExistente];
                    }
                }

                // 2. Si otra petición simultánea está enviando o ya fue enviado y aguarda confirmación DIAN, no duplicar
                foreach ($existentes as $docExistente) {
                    if (in_array(strtolower((string) $docExistente->estado), ['enviando', 'enviado'], true)) {
                        return ['accion' => 'en_proceso', 'documento' => $docExistente];
                    }
                }

                // 3. Si existe un documento en estado 'pendiente' que ya tiene respuesta previa de MATÍAS (ej. StatusCode 98),
                // no reenviar automáticamente para no duplicar folio.
                foreach ($existentes as $docExistente) {
                    if (strtolower((string) $docExistente->estado) === 'pendiente' && !empty($docExistente->datos_tecnicos['respuesta_matias'])) {
                        return ['accion' => 'en_proceso', 'documento' => $docExistente];
                    }
                }

                // 4. Reutilizar registro pendiente/rechazado/error existente o crear uno nuevo
                $docReutilizable = $existentes->first(fn ($d) => in_array(
                    strtolower((string) $d->estado),
                    ['pendiente', 'rechazado', 'configuracion_invalida', 'error_tecnico'],
                    true
                ));

                $prefijoDoc = trim((string) ($docReutilizable?->prefijo ?? ''));
                $prefijoRes = trim((string) ($resolucionConfig->prefijo ?? ''));
                $prefijoVenta = trim((string) ($ventaLock->prefijo ?? ''));
                $prefijoEmp = trim((string) ($empresaConfig?->prefijo_factura ?? ''));

                $prefijoEfectivo = $prefijoDoc !== ''
                    ? $prefijoDoc
                    : ($prefijoRes !== ''
                        ? $prefijoRes
                        : ($prefijoVenta !== '' && !in_array($prefijoVenta, ['POS', 'AJT'], true)
                            ? $prefijoVenta
                            : ($prefijoEmp !== '' ? $prefijoEmp : 'SETP')));

                $folioDoc = trim((string) ($docReutilizable?->numero_documento ?? ''));
                $folioEfectivo = $folioDoc !== '' ? $folioDoc : (string) $ventaLock->numero_factura;

                if (!$docReutilizable) {
                    $crearDto = new CrearDocumentoElectronicoDTO(
                        ventaId: $ventaLock->id,
                        configuracionFacturacionId: $configFacturacion->id,
                        tipoDocumento: 'factura_electronica',
                        estado: 'enviando',
                        proveedor: $configFacturacion->proveedor_activo ?? 'matias',
                        ambiente: $configFacturacion->ambiente ?? 'sandbox',
                        numeroDocumento: (string) $folioEfectivo,
                        prefijo: (string) $prefijoEfectivo,
                        mensaje: 'Preparando factura electrónica para transmisión a MATÍAS API.'
                    );

                    $docReutilizable = $this->documentoElectronicoService?->registrarEmision($crearDto);
                } else {
                    $docReutilizable->update([
                        'estado' => 'enviando',
                        'prefijo' => (string) $prefijoEfectivo,
                        'numero_documento' => (string) $folioEfectivo,
                        'mensaje' => 'Reintentando preparación y transmisión a MATÍAS API.',
                    ]);
                }

                return [
                    'accion' => 'transmitir',
                    'documento' => $docReutilizable,
                ];
            });

            $documento = $preparacion['documento'] ?? null;
            if (($preparacion['accion'] ?? '') !== 'transmitir' || !$documento) {
                return $documento;
            }

            // Este proceso adquirió atómicamente el bloqueo de transmisión ('enviando') dentro de la transacción
            $adquirioCandadoEnvio = true;

            if (!$this->facturacionService) {
                $this->documentoElectronicoService?->restaurarEstadoPendiente($documento->id);
                return $documento;
            }

            // Construir SolicitudFacturaDTO unificando prefijo y número de factura con el documento reservado
            $solicitud = $this->construirSolicitudFacturaDTO(
                $venta,
                $empresaConfig,
                $configFacturacion,
                $resolucionConfig,
                $documento
            );

            // Persistir solicitud y payload preparado en datos_tecnicos para trazabilidad
            $payloadPreparado = \App\Modules\Facturacion\Infrastructure\Mappers\MatiasInvoiceMapper::toMatiasPayload($solicitud);
            $documento->update([
                'datos_tecnicos' => array_merge(
                    is_array($documento->datos_tecnicos) ? $documento->datos_tecnicos : [],
                    [
                        'solicitud' => $solicitud->toArray(),
                        'payload' => $payloadPreparado,
                    ]
                ),
            ]);

            // Enviar solicitud de facturación electrónica a MATÍAS API
            $respuesta = $this->facturacionService->enviarFactura($solicitud);

            // Actualizar documento con la respuesta obtenida
            if ($this->documentoElectronicoService) {
                return $this->documentoElectronicoService->actualizarDesdeRespuesta($documento->id, $respuesta);
            }

            return $documento;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error al procesar facturación electrónica para venta #' . $venta->id, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            try {
                // Solo actualizar el estado del documento si este proceso fue el que adquirió el bloqueo de envío
                if ($adquirioCandadoEnvio && isset($documento) && $documento && $this->documentoElectronicoService) {
                    $respuestaError = RespuestaFacturacionDTO::error(
                        mensaje: 'Error durante la emisión: ' . $e->getMessage(),
                        errores: ['exception' => $e->getMessage()],
                        estado: 'rechazado'
                    );
                    return $this->documentoElectronicoService->actualizarDesdeRespuesta($documento->id, $respuestaError);
                }
            } catch (\Throwable $ex) {
                \Illuminate\Support\Facades\Log::error('Error al actualizar trazabilidad de documento electrónico #' . $venta->id, [
                    'error' => $ex->getMessage(),
                ]);
            }

            return null;
        }
    }

    /**
     * Reintentar manualmente la transmisión de una Factura Electrónica rechazada o con error.
     * Reutiliza el mismo DocumentoElectronico, prefijo y número consecutivo sin consumir un nuevo folio.
     */
    public function reintentarFacturaRechazada(int $documentoId): DocumentoElectronico
    {
        $documento = DocumentoElectronico::find($documentoId);

        if (!$documento) {
            throw new HttpException(404, "El documento electrónico #{$documentoId} no fue encontrado.");
        }

        if (!in_array(strtolower((string) $documento->tipo_documento), ['factura_electronica', 'factura'], true)) {
            throw new HttpException(
                422,
                "El documento electrónico #{$documentoId} no corresponde a una factura electrónica."
            );
        }

        $estadoActual = strtolower((string) $documento->estado);
        if (!in_array($estadoActual, ['rechazado', 'configuracion_invalida', 'error_tecnico', 'error'], true)) {
            throw new HttpException(
                422,
                "Solo se pueden reintentar facturas electrónicas en estado rechazado o error (estado actual: '{$documento->estado}')."
            );
        }

        if (empty($documento->venta_id)) {
            throw new HttpException(
                422,
                "El documento electrónico #{$documentoId} no tiene una venta asociada para reconstruir la factura."
            );
        }

        $venta = Venta::find($documento->venta_id);
        if (!$venta) {
            throw new HttpException(
                404,
                "La venta #{$documento->venta_id} asociada al documento electrónico #{$documentoId} no fue encontrada."
            );
        }

        $resultado = $this->procesarFacturacionElectronica($venta);

        return ($resultado ?? $documento->fresh())->loadMissing(['venta.cliente']);
    }

    /**
     * Reintentar masivamente todas las Facturas Electrónicas en estado rechazado o error.
     */
    public function reintentarMasivoFacturasRechazadas(): array
    {
        $rechazadas = DocumentoElectronico::whereIn('tipo_documento', ['factura_electronica', 'factura'])
            ->whereIn('estado', ['rechazado', 'configuracion_invalida', 'error_tecnico', 'error'])
            ->orderBy('id', 'asc')
            ->get();

        $totalEncontrados = $rechazadas->count();

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

        foreach ($rechazadas as $doc) {
            $estadoAnterior = $doc->estado;

            try {
                $docActualizado = $this->reintentarFacturaRechazada($doc->id);
                $procesados++;

                $estadoFinal = strtolower((string) ($docActualizado->estado ?? 'desconocido'));

                if (in_array($estadoFinal, ['emitido', 'aceptado', 'aprobado'], true)) {
                    $emitidos++;
                } elseif (in_array($estadoFinal, ['pendiente', 'enviado'], true)) {
                    $pendientesCount++;
                } else {
                    $rechazadosCount++;
                }

                $detalles[] = [
                    'documento_electronico_id' => $doc->id,
                    'tipo_documento' => $doc->tipo_documento,
                    'numero_documento' => trim(($doc->prefijo ? $doc->prefijo . '-' : '') . ($doc->numero_documento ?? '')),
                    'estado_anterior' => $estadoAnterior,
                    'estado_final' => $docActualizado->estado,
                    'mensaje' => $docActualizado->mensaje ?? 'Reintento procesado.',
                ];
            } catch (\Throwable $e) {
                $procesados++;
                $rechazadosCount++;

                $detalles[] = [
                    'documento_electronico_id' => $doc->id,
                    'tipo_documento' => $doc->tipo_documento,
                    'numero_documento' => trim(($doc->prefijo ? $doc->prefijo . '-' : '') . ($doc->numero_documento ?? '')),
                    'estado_anterior' => $estadoAnterior,
                    'estado_final' => 'rechazado',
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
     * Construir SolicitudFacturaDTO a partir de la Venta y Configuración.
     * Utiliza el snapshot tributario histórico guardado en detalle_ventas, las unidades DIAN homologadas
     * y los medios de pago reales registrados en la venta.
     */
    protected function construirSolicitudFacturaDTO(
        Venta $venta,
        ?ConfiguracionEmpresa $empresaConfig,
        ?ConfiguracionFacturacion $configFacturacion,
        ?ResolucionFacturacion $resolucionConfig = null,
        ?DocumentoElectronico $documento = null
    ): SolicitudFacturaDTO {
        $venta->loadMissing([
            'cliente.pais',
            'cliente.ciudad',
            'detalles.producto.unidadMedida',
            'pagos',
            'usuario',
        ]);

        $cliente = $venta->cliente;
        $cliente?->loadMissing(['pais', 'ciudad']);
        $empresaConfig?->loadMissing(['pais', 'ciudad']);

        if ($venta->factura_electronica) {
            if (!$cliente) {
                throw new HttpException(422, 'Se requiere un cliente registrado para emitir una factura electrónica a nombre de un adquirente.');
            }

            $nombreCliente = trim(($cliente->nombre ?? '') . ' ' . ($cliente->apellidos ?? ''));
            if (empty($nombreCliente)) {
                throw new HttpException(422, 'El cliente seleccionado no tiene un nombre o razón social válido.');
            }
            if (empty($cliente->documento)) {
                throw new HttpException(422, 'El cliente seleccionado no tiene un número de documento válido.');
            }

            $clienteData = [
                'company_name' => $nombreCliente,
                'nombre_razon_social' => $nombreCliente,
                'dni' => (string) $cliente->documento,
                'numero_documento' => (string) $cliente->documento,
                'email' => (string) ($cliente->email ?? ''),
                'telefono' => (string) ($cliente->telefono_uno ?? $cliente->telefono_dos ?? ''),
                'mobile' => (string) ($cliente->telefono_uno ?? $cliente->telefono_dos ?? ''),
                'direccion' => (string) ($cliente->direccion ?? ''),
                'address' => (string) ($cliente->direccion ?? ''),
                'identity_document_id' => (string) ($cliente->tipo_documento_id ?? '1'),
                'type_organization_id' => (int) ($cliente->tipo_organization_id ?? $cliente->tipo_persona ?? 2),
                'tax_regime_id' => (int) ($cliente->tax_regime_id ?? 2),
                'tax_level_id' => (int) ($cliente->tax_level_id ?? 5),
                'postal_code' => (string) ($cliente->codigo_postal ?? ''),
                'country_iso' => (string) ($cliente->pais?->codigo_iso ?? 'CO'),
                'city_dane_code' => (string) ($cliente->ciudad?->codigo ?? $empresaConfig?->ciudad?->codigo ?? '41396'),
                'city_local_id' => (string) ($cliente->ciudad_id ?? $empresaConfig?->ciudad_id ?? ''),
            ];
        } else {
            if ($cliente) {
                $nombreCliente = trim(($cliente->nombre ?? '') . ' ' . ($cliente->apellidos ?? ''));
                if (empty($nombreCliente)) {
                    $nombreCliente = 'CLIENTE MOSTRADOR';
                }
                $clienteData = [
                    'company_name' => $nombreCliente,
                    'nombre_razon_social' => $nombreCliente,
                    'dni' => (string) ($cliente->documento ?? '222222222222'),
                    'numero_documento' => (string) ($cliente->documento ?? '222222222222'),
                    'email' => (string) ($cliente->email ?? 'factura@cliente.com'),
                    'telefono' => (string) ($cliente->telefono_uno ?? $cliente->telefono_dos ?? '3000000000'),
                    'mobile' => (string) ($cliente->telefono_uno ?? $cliente->telefono_dos ?? '3000000000'),
                    'direccion' => (string) ($cliente->direccion ?? ''),
                    'address' => (string) ($cliente->direccion ?? ''),
                    'identity_document_id' => (string) ($cliente->tipo_documento_id ?? '1'),
                    'type_organization_id' => (int) ($cliente->tipo_organization_id ?? $cliente->tipo_persona ?? 2),
                    'tax_regime_id' => (int) ($cliente->tax_regime_id ?? 2),
                    'tax_level_id' => (int) ($cliente->tax_level_id ?? 5),
                    'postal_code' => (string) ($cliente->codigo_postal ?? ''),
                    'country_iso' => (string) ($cliente->pais?->codigo_iso ?? 'CO'),
                    'city_dane_code' => (string) ($cliente->ciudad?->codigo ?? $empresaConfig?->ciudad?->codigo ?? '41396'),
                    'city_local_id' => (string) ($cliente->ciudad_id ?? $empresaConfig?->ciudad_id ?? ''),
                ];
            } else {
                $clienteData = [
                    'company_name' => 'CONSUMIDOR FINAL',
                    'nombre_razon_social' => 'CONSUMIDOR FINAL',
                    'dni' => '222222222222',
                    'numero_documento' => '222222222222',
                    'email' => 'factura@cliente.com',
                    'telefono' => '3000000000',
                    'direccion' => (string) ($empresaConfig?->direccion ?? ''),
                    'address' => (string) ($empresaConfig?->direccion ?? ''),
                    'identity_document_id' => '1',
                    'type_organization_id' => 2,
                    'tax_regime_id' => 2,
                    'tax_level_id' => 5,
                    'postal_code' => (string) ($empresaConfig?->codigo_postal ?? ''),
                    'country_iso' => (string) ($empresaConfig?->pais?->codigo_iso ?? 'CO'),
                    'city_dane_code' => (string) ($empresaConfig?->ciudad?->codigo ?? '41396'),
                    'city_local_id' => (string) ($empresaConfig?->ciudad_id ?? ''),
                ];
            }
        }

        $prefijoDoc = trim((string) ($documento?->prefijo ?? ''));
        $prefijoRes = trim((string) ($resolucionConfig?->prefijo ?? ''));
        $prefijoVenta = trim((string) ($venta->prefijo ?? ''));
        $prefijoEmp = trim((string) ($empresaConfig?->prefijo_factura ?? ''));

        $prefijo = $prefijoDoc !== ''
            ? $prefijoDoc
            : ($prefijoRes !== ''
                ? $prefijoRes
                : ($prefijoVenta !== '' && !in_array($prefijoVenta, ['POS', 'AJT'], true)
                    ? $prefijoVenta
                    : ($prefijoEmp !== '' ? $prefijoEmp : 'SETP')));

        $folioDoc = trim((string) ($documento?->numero_documento ?? ''));
        $folioEfectivo = $folioDoc !== '' ? $folioDoc : (string) $venta->numero_factura;

        $resNumCfg = trim((string) ($resolucionConfig?->numero_resolucion ?? ''));
        $resNumEmp = trim((string) ($empresaConfig?->numero_resolucion ?? ''));
        $numeroResolucion = $resNumCfg !== '' ? $resNumCfg : $resNumEmp;
        $proveedorActivo = $configFacturacion?->proveedor_activo ?? 'matias';

        $cajeroNombre = $venta->usuario?->name ?? 'Cajero ERP';
        $emisorData = [
            'nit' => (string) ($empresaConfig?->nit ?? ''),
            'dv' => (string) ($empresaConfig?->dv ?? ''),
            'nombre_empresa' => (string) ($empresaConfig?->nombre_empresa ?? ''),
            'resolution_number' => $numeroResolucion,
            'cajero' => $cajeroNombre,
            'vendedor' => $cajeroNombre,
        ];

        $itemsData = [];
        $subtotalLineas = 0.0;
        $baseGravableLineas = 0.0;
        $impuestosLineas = 0.0;

        foreach ($venta->detalles as $indice => $detalle) {
            $cant = (float) $detalle->cantidad;
            $precio = (float) $detalle->precio_unitario;
            $subtotal = (float) $detalle->subtotal;
            $ivaValor = round((float) ($detalle->iva_valor ?? 0), 2);
            $ivaPorcentaje = round((float) ($detalle->porcentaje_iva ?? $detalle->iva ?? 0), 2);
            $descuento = round((float) ($detalle->descuento ?? 0), 2);
            $soldicom = round((float) ($detalle->soldicom ?? 0), 2);
            $sobreTasa = round((float) ($detalle->sobre_tasa ?? 0), 2);

            if ($soldicom > 0 || $sobreTasa > 0) {
                throw new \InvalidArgumentException(sprintf(
                    'No se puede transmitir la línea %d (producto %d): tiene Sordicom o sobretasa, pero el detalle no contiene una clasificación MATÍAS confirmada para esos tributos.',
                    $indice + 1,
                    $detalle->producto_id
                ));
            }

            // Resolver clasificación exclusivamente desde el snapshot histórico guardado en detalle_ventas
            $tipoImpuesto = $detalle->tipo_impuesto !== null && trim((string) $detalle->tipo_impuesto) !== ''
                ? strtolower(trim((string) $detalle->tipo_impuesto))
                : null;

            // Compatibilidad con detalles históricos que tenían IVA > 0 antes de existir tipo_impuesto
            if ($tipoImpuesto === null && $ivaPorcentaje > 0 && $ivaValor >= 0) {
                $tipoImpuesto = Producto::TIPO_IMPUESTO_GRAVADO;
            }

            if ($tipoImpuesto === null) {
                throw new \InvalidArgumentException(sprintf(
                    'No se puede transmitir la línea %d (producto %d): el detalle de venta no tiene clasificación tributaria registrada (gravado, exento o excluido).',
                    $indice + 1,
                    $detalle->producto_id
                ));
            }

            $baseImponible = round($subtotal, 2);
            $codigoImpuesto = trim((string) ($detalle->codigo_impuesto ?? ''));

            if ($tipoImpuesto === Producto::TIPO_IMPUESTO_GRAVADO) {
                if ($ivaPorcentaje <= 0 || $ivaValor < 0) {
                    throw new \InvalidArgumentException(sprintf(
                        'No se puede transmitir la línea %d (producto %d): está clasificada como gravada pero su tarifa de IVA (%.2f%%) no es válida.',
                        $indice + 1,
                        $detalle->producto_id,
                        $ivaPorcentaje
                    ));
                }

                $impuestoEsperado = round($baseImponible * $ivaPorcentaje / 100, 2);
                if (abs($impuestoEsperado - $ivaValor) > 0.05) {
                    throw new \InvalidArgumentException(sprintf(
                        'No se puede transmitir la línea %d (producto %d): iva_valor (%.2f) no coincide con la base del detalle (%.2f) por la tarifa registrada (%.2f%%).',
                        $indice + 1,
                        $detalle->producto_id,
                        $ivaValor,
                        $baseImponible,
                        $ivaPorcentaje
                    ));
                }

                $taxId = $codigoImpuesto !== ''
                    ? $codigoImpuesto
                    : ($this->catalogosFacturacionService?->resolverCodigoImpuestoProducto(Producto::TIPO_IMPUESTO_GRAVADO, null, $proveedorActivo) ?? '1');

                $ivaPorLinea = [[
                    'tax_id' => $taxId,
                    'tax_amount' => number_format($ivaValor, 2, '.', ''),
                    'taxable_amount' => number_format($baseImponible, 2, '.', ''),
                    'percent' => $ivaPorcentaje,
                ]];

                $baseGravableLineas += $baseImponible;
            } elseif ($tipoImpuesto === Producto::TIPO_IMPUESTO_EXENTO) {
                if ($ivaPorcentaje != 0.0 || $ivaValor != 0.0) {
                    throw new \InvalidArgumentException(sprintf(
                        'No se puede transmitir la línea %d (producto %d): está clasificada como exenta pero registra tarifa (%.2f%%) o valor de IVA (%.2f) distinto de cero.',
                        $indice + 1,
                        $detalle->producto_id,
                        $ivaPorcentaje,
                        $ivaValor
                    ));
                }

                $taxId = $codigoImpuesto !== ''
                    ? $codigoImpuesto
                    : ($this->catalogosFacturacionService?->resolverCodigoImpuestoProducto(Producto::TIPO_IMPUESTO_EXENTO, null, $proveedorActivo) ?? '1');

                $ivaPorLinea = [[
                    'tax_id' => $taxId,
                    'tax_amount' => '0.00',
                    'taxable_amount' => number_format($baseImponible, 2, '.', ''),
                    'percent' => 0.0,
                ]];

                $baseGravableLineas += $baseImponible;
            } elseif ($tipoImpuesto === Producto::TIPO_IMPUESTO_EXCLUIDO) {
                if ($ivaPorcentaje != 0.0 || $ivaValor != 0.0) {
                    throw new \InvalidArgumentException(sprintf(
                        'No se puede transmitir la línea %d (producto %d): está clasificada como excluida pero registra tarifa (%.2f%%) o valor de IVA (%.2f) distinto de cero.',
                        $indice + 1,
                        $detalle->producto_id,
                        $ivaPorcentaje,
                        $ivaValor
                    ));
                }

                $taxId = null;
                $ivaPorLinea = [];
            } else {
                throw new \InvalidArgumentException(sprintf(
                    'No se puede transmitir la línea %d (producto %d): clasificación tributaria "%s" desconocida.',
                    $indice + 1,
                    $detalle->producto_id,
                    $tipoImpuesto
                ));
            }

            $subtotalLineas += $baseImponible;
            $impuestosLineas += $ivaValor;
            $nombreProd = $detalle->producto?->nombre ?? 'Producto';
            $codigoProd = $detalle->producto?->codigo ?? 'PROD-' . $detalle->producto_id;

            // Para ventas de combustible donde cantidad tiene 3 decimales y precio_unitario fue redondeado a 2 decimales,
            // ajustar el precio unitario de presentación en la línea electrónica si no hay descuento para que cant * precio = base
            $precioParaLinea = $precio;
            if ($descuento == 0.0 && $cant > 0 && round($cant * $precio, 2) !== $baseImponible) {
                $precioParaLinea = round($baseImponible / $cant, 2);
            }

            $allowanceCharges = [];
            if ($descuento > 0) {
                $valorAntesDescuento = round($cant * $precio, 2);
                if (round($valorAntesDescuento - $descuento, 2) !== $baseImponible) {
                    throw new \InvalidArgumentException(sprintf(
                        'No se puede transmitir la línea %d (producto %d): subtotal, precio, cantidad y descuento no coinciden.',
                        $indice + 1,
                        $detalle->producto_id
                    ));
                }
                $allowanceCharges[] = [
                    'amount' => number_format($descuento, 2, '.', ''),
                    'base_amount' => number_format($valorAntesDescuento, 2, '.', ''),
                    'charge_indicator' => false,
                    'allowance_charge_reason' => 'Descuento comercial',
                ];
            }

            if (!$this->catalogosFacturacionService) {
                throw new \InvalidArgumentException(
                    'El servicio de homologación de catálogos de facturación no está disponible.'
                );
            }

            $unidadHomologada = $this->catalogosFacturacionService->resolverUnidadMedidaProducto(
                $detalle->producto?->unidadMedida,
                $proveedorActivo
            );

            $itemsData[] = [
                'code' => (string) $codigoProd,
                'codigo' => (string) $codigoProd,
                'description' => (string) $nombreProd,
                'nombre' => (string) $nombreProd,
                'invoiced_quantity' => $cant,
                'cantidad' => $cant,
                'price_amount' => $precioParaLinea,
                'precio_unitario' => $precioParaLinea,
                'line_extension_amount' => $baseImponible,
                'tipo_impuesto' => $tipoImpuesto,
                'tax_amount' => $ivaValor,
                'percent' => $ivaPorcentaje,
                'tax_id' => $taxId,
                'codigo_impuesto' => $taxId,
                'tax_totals' => $ivaPorLinea,
                'allowance_charges' => $allowanceCharges,
                'quantity_units_id' => $unidadHomologada['quantity_units_id'],
                'um' => $unidadHomologada['um'],
            ];
        }

        if ($venta->detalles->count() > 0) {
            $subtotalLineas = round($subtotalLineas, 2);
            $baseGravableLineas = round($baseGravableLineas, 2);
            $impuestosLineas = round($impuestosLineas, 2);

            if ($subtotalLineas !== round((float) $venta->subtotal, 2)) {
                throw new \InvalidArgumentException(sprintf(
                    'No se puede transmitir la factura: la suma de bases de sus detalles (%.2f) no coincide con el subtotal de la venta (%.2f).',
                    $subtotalLineas,
                    (float) $venta->subtotal
                ));
            }
            if ($impuestosLineas !== round((float) $venta->impuesto, 2)) {
                throw new \InvalidArgumentException(sprintf(
                    'No se puede transmitir la factura: la suma del IVA guardado en los detalles (%.2f) no coincide con el impuesto de la venta (%.2f).',
                    $impuestosLineas,
                    (float) $venta->impuesto
                ));
            }
            $totalCalculado = round($subtotalLineas + $impuestosLineas, 2);
            if ($totalCalculado !== round((float) $venta->total, 2)) {
                throw new \InvalidArgumentException(sprintf(
                    'No se puede transmitir la factura: bases más IVA (%.2f) no coinciden con el total cobrado (%.2f); puede haber otros tributos sin clasificación MATÍAS.',
                    $totalCalculado,
                    (float) $venta->total
                ));
            }
        } else {
            $subtotalLineas = round((float) $venta->subtotal, 2);
            $baseGravableLineas = 0.0;
            $impuestosLineas = round((float) $venta->impuesto, 2);
        }

        $totalesData = [
            'subtotal' => $subtotalLineas,
            'impuestos' => $impuestosLineas,
            'descuento' => (float) $venta->descuento,
            'total_pagar' => (float) $venta->total,
            'line_extension_amount' => $subtotalLineas,
            'tax_exclusive_amount' => $baseGravableLineas,
            'tax_inclusive_amount' => (float) $venta->total,
            'payable_amount' => (float) $venta->total,
        ];

        $paymentsPayload = $this->construirPagosMatias($venta, $proveedorActivo);

        return new SolicitudFacturaDTO(
            ventaId: $venta->id,
            prefijo: $prefijo,
            folio: $folioEfectivo,
            fechaEmision: $venta->fecha_venta ? $venta->fecha_venta->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
            tipoDocumento: 'factura_venta',
            cliente: $clienteData,
            emisor: $emisorData,
            items: $itemsData,
            totales: $totalesData,
            medioPago: $venta->tipo_venta,
            observacion: $venta->observacion,
            metadatos: [
                'resolution_number' => $numeroResolucion,
                'prefix' => $prefijo,
                'payments' => $paymentsPayload,
            ]
        );
    }

    /**
     * Construir el arreglo de pagos homologados para MATÍAS a partir de los pagos reales de la venta.
     * Carga la relación pagos si aún no estaba cargada y exige homologación válida en CatalogosFacturacionService.
     */
    protected function construirPagosMatias(Venta $venta, string $proveedor = 'matias'): array
    {
        if (!$this->catalogosFacturacionService) {
            throw new \InvalidArgumentException(
                'El servicio de homologación de catálogos de facturación no está disponible para resolver medios de pago.'
            );
        }

        $venta->loadMissing('pagos');
        $totalVenta = round((float) $venta->total, 2);

        if (strtolower((string) $venta->tipo_venta) === 'credito') {
            $mapeo = $this->catalogosFacturacionService->obtenerMapeoMedioPago($proveedor, 'credito');

            return [[
                'payment_method_id' => (int) $mapeo['payment_method_id'],
                'means_payment_id' => (int) $mapeo['means_payment_id'],
                'value_paid' => number_format($totalVenta, 2, '.', ''),
            ]];
        }

        if ($venta->pagos && $venta->pagos->isNotEmpty()) {
            $payments = [];
            $sumaPagos = 0.0;

            foreach ($venta->pagos as $pago) {
                $monto = round((float) $pago->monto, 2);
                if ($monto <= 0) {
                    continue;
                }
                $metodo = trim((string) ($pago->metodo_pago ?? ''));
                if ($metodo === '') {
                    throw new \InvalidArgumentException(
                        "El pago #{$pago->id} de la venta #{$venta->id} no tiene un método de pago registrado."
                    );
                }
                $mapeo = $this->catalogosFacturacionService->obtenerMapeoMedioPago($proveedor, $metodo);

                $payments[] = [
                    'payment_method_id' => (int) $mapeo['payment_method_id'],
                    'means_payment_id' => (int) $mapeo['means_payment_id'],
                    'value_paid' => number_format($monto, 2, '.', ''),
                ];
                $sumaPagos = round($sumaPagos + $monto, 2);
            }

            // Si además se aplicó anticipo o saldo pendiente parcial, completar hasta el total de la factura
            if ($sumaPagos < $totalVenta) {
                $diferencia = round($totalVenta - $sumaPagos, 2);
                $metodoRestante = (float) $venta->saldo_pendiente > 0
                    ? 'credito'
                    : ($payments !== [] ? (string) $venta->pagos->first()->metodo_pago : 'efectivo');
                $mapeoRest = $this->catalogosFacturacionService->obtenerMapeoMedioPago($proveedor, $metodoRestante);

                $payments[] = [
                    'payment_method_id' => (int) $mapeoRest['payment_method_id'],
                    'means_payment_id' => (int) $mapeoRest['means_payment_id'],
                    'value_paid' => number_format($diferencia, 2, '.', ''),
                ];
            }

            if (!empty($payments)) {
                return $payments;
            }
        }

        $mapeoDefault = $this->catalogosFacturacionService->obtenerMapeoMedioPago($proveedor, 'efectivo');

        return [[
            'payment_method_id' => (int) $mapeoDefault['payment_method_id'],
            'means_payment_id' => (int) $mapeoDefault['means_payment_id'],
            'value_paid' => number_format($totalVenta, 2, '.', ''),
        ]];
    }
}
