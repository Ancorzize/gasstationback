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
class VentaService
{
    public function __construct(
        protected VentaRepositoryInterface $ventaRepository,
        protected TurnoIsleroRepositoryInterface $turnoRepository
    ) {
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
        return DB::transaction(function () use ($dto) {

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

            $subtotal = 0;
            $impuesto = 0;
            $soldicom = 0;
            $sobreTasa = 0;
            $descuento = 0;
            $total = 0;
            $destinoRecaudoId = null;

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


                if (!$producto) {
                    throw new HttpException(
                        422,
                        "Producto {$detalle->producto_id} no existe."
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

                $subtotal += $detalle->subtotal();
                $impuesto += $detalle->iva_valor;
                $soldicom += $detalle->soldicom;
                $sobreTasa += $detalle->sobre_tasa;
                $descuento += $detalle->descuento;
                $total += $detalle->total;
            }

            $totalPagado = 0;

            foreach ($dto->pagos as $pago) {
                $totalPagado += $pago->monto;
            }

            $saldoPendiente = $total - $totalPagado;

            if ($saldoPendiente > 0) {

                if (!$dto->cliente_id) {
                    throw new HttpException(
                        422,
                        'Debe seleccionar un cliente para ventas a crédito.'
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

            $turnoAbierto = $this->ventaRepository->getTurnoAbiertoByUser($dto->user_id);
            if (empty($turnoAbierto)) {
                throw new HttpException(422, 'El usuario no tiene turno abierto.');
            }
            $venta = $this->ventaRepository->createVenta([
                'prefijo' => 'POS',
                'numero_factura' => $this->ventaRepository->nextNumeroFactura(),
                'cliente_id' => $dto->cliente_id,
                'user_id' => $dto->user_id,
                'bodega_id' => $bodegaId,
                'tipo_venta' => $dto->tipo_venta,
                'tipo_origen' => 'pos',
                'estado' => 'confirmada',
                'estado_pago' => $saldoPendiente <= 0
                    ? 'pagado'
                    : ($totalPagado > 0 ? 'parcial' : 'pendiente'),
                'subtotal' => $subtotal,
                'descuento' => $descuento,
                'impuesto' => $impuesto,
                'soldicom' => $soldicom,
                'sobre_tasa' => $sobreTasa,
                'total' => $total,
                'total_pagado' => $totalPagado,
                'saldo_pendiente' => $saldoPendiente,
                'fecha_venta' => now(),
                'fecha_vencimiento' => $saldoPendiente > 0 ? now()->addDays($cliente->dias_credito) : null,
                'observacion' => $dto->observacion,
                'turno_islero_id' => $turnoAbierto?->id,
                'destino_recaudo_id' => $destinoRecaudoId,
            ]);


            foreach ($dto->detalles as $detalle) {

                $this->ventaRepository->createDetalle([
                    'venta_id' => $venta->id,
                    'producto_id' => $detalle->producto_id,
                    'manguera_id' => null,
                    'cantidad' => $detalle->cantidad,
                    'precio_unitario' => $detalle->precio_unitario,
                    'descuento' => $detalle->descuento,
                    'iva' => $detalle->iva,
                    'iva_valor' => $detalle->iva_valor,
                    'soldicom' => $detalle->soldicom,
                    'sobre_tasa' => $detalle->sobre_tasa,
                    'subtotal' => $detalle->subtotal(),
                    'total' => $detalle->total,
                ]);



                $this->ventaRepository->decrementInventario(
                    $detalle->producto_id,
                    $bodegaId,
                    $detalle->cantidad
                );

                $this->ventaRepository->createMovimientoInventario([
                    'tipo_movimiento' => 'venta',
                    'producto_id' => $detalle->producto_id,
                    'bodega_origen_id' => $bodegaId,
                    'bodega_destino_id' => null,
                    'cantidad' => $detalle->cantidad,
                    'observacion' => "Venta #{$venta->id}",
                    'user_id' => $dto->user_id,
                ]);
            }

            foreach ($dto->pagos as $pago) {


                $tipoCaja =
                    $this->resolverTipoCaja(
                        $pago->metodo_pago
                    );

                $caja =
                    $this->ventaRepository
                    ->getCajaAbiertaByTipoAndDestino(
                        $tipoCaja,
                        $destinoRecaudoId
                    );

                if (!$caja) {
                    throw new HttpException(
                        422,
                        "No existe caja abierta para el destino de recaudo configurado."
                    );
                }


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
        return DB::transaction(function () use ($dto) {

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

            $total = $dto->monto;
            $subtotal = $dto->monto;
            $impuesto = 0;
            $soldicom = 0;
            $sobreTasa = 0;
            $descuento = 0;

            $totalPagado = $dto->tipo_venta === 'credito' ? 0 : $dto->monto;
            $saldoPendiente = $dto->tipo_venta === 'credito' ? $dto->monto : 0;

            if ($dto->tipo_venta === 'credito') {
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

            if ($dto->tipo_venta !== 'credito') {
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

            $venta = $this->ventaRepository->createVenta([
                'prefijo' => 'POS',
                'numero_factura' => $this->ventaRepository->nextNumeroFactura(),
                'cliente_id' => $dto->cliente_id,
                'user_id' => $dto->user_id,
                'bodega_id' => $bodegaId,
                'turno_islero_id' => $turnoAbierto->id,
                'tipo_venta' => $dto->tipo_venta,
                'estado' => 'confirmada',
                'estado_pago' => $saldoPendiente <= 0 ? 'pagado' : 'pendiente',
                'subtotal' => $subtotal,
                'descuento' => $descuento,
                'impuesto' => $impuesto,
                'soldicom' => $soldicom,
                'sobre_tasa' => $sobreTasa,
                'total' => $total,
                'total_pagado' => $totalPagado,
                'saldo_pendiente' => $saldoPendiente,
                'fecha_venta' => now(),
                'fecha_vencimiento' => $saldoPendiente > 0 ? now()->addDays($cliente->dias_credito) : null,
                'observacion' => $dto->observacion,
                'tipo_origen' => 'combustible',
                'destino_recaudo_id' => $destinoRecaudoId,
            ]);

            $this->ventaRepository->createDetalle([
                'venta_id' => $venta->id,
                'producto_id' => $manguera->producto_id,
                'cantidad' => $cantidadGalones,
                'precio_unitario' => $precioGalon,
                'descuento' => 0,
                'iva' => 0,
                'iva_valor' => 0,
                'soldicom' => 0,
                'sobre_tasa' => 0,
                'subtotal' => $subtotal,
                'total' => $total,
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

            if ($dto->tipo_venta !== 'credito') {
                $pago = $this->ventaRepository->createPago([
                    'venta_id' => $venta->id,
                    'caja_id' => $caja->id,
                    'user_id' => $dto->user_id,
                    'fecha_pago' => now(),
                    'monto' => $dto->monto,
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
                        'monto' => $dto->monto,
                        'descripcion' => "Ingreso por venta combustible #{$venta->id}",
                        'user_id' => $dto->user_id,
                        'fecha_movimiento' => now(),
                    ]);
                }
            }

            if ($dto->tipo_venta === 'credito') {
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
    }

    public function crearVentaAjusteTurno(
        TurnoIslero $turno,
        Collection $lecturas,
        int $userId
    ): ?Venta {
        $detalles = [];

        $subtotal = 0;
        $total = 0;
        $destinoRecaudoId = null;

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

            $subtotal += $valor;
            $total += $valor;

            $detalles[] = [
                'producto_id' => $producto->id,
                'manguera_id' => $lectura->manguera_id,
                'cantidad' => $galonesAjuste,
                'precio_unitario' => (float)$lectura->precio_galon,
                'subtotal' => $valor,
                'total' => $valor,
            ];
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
            'impuesto' => 0,
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
                'descuento' => 0,
                'iva' => 0,
                'iva_valor' => 0,
                'soldicom' => 0,
                'sobre_tasa' => 0,
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

            foreach ($venta->detalles as $detalleAntiguo) {
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

                    $detSubtotal = round((float) $detData['cantidad'] * (float) $detData['precio_unitario'], 2);
                    $detDescuento = round((float) ($detData['descuento'] ?? 0), 2);
                    $detIvaValor = round((float) ($detData['iva_valor'] ?? 0), 2);
                    $detSoldicom = round((float) ($detData['soldicom'] ?? 0), 2);
                    $detSobreTasa = round((float) ($detData['sobre_tasa'] ?? 0), 2);
                    $detTotal = (float) $detData['total'];

                    $subtotal += $detSubtotal;
                    $impuesto += $detIvaValor;
                    $soldicom += $detSoldicom;
                    $sobreTasa += $detSobreTasa;
                    $descuento += $detDescuento;
                    $total += $detTotal;

                    $this->ventaRepository->createDetalle([
                        'venta_id' => $venta->id,
                        'producto_id' => $detData['producto_id'],
                        'manguera_id' => null,
                        'cantidad' => $detData['cantidad'],
                        'precio_unitario' => $detData['precio_unitario'],
                        'descuento' => $detDescuento,
                        'iva' => $detData['iva'] ?? 0,
                        'iva_valor' => $detIvaValor,
                        'soldicom' => $detSoldicom,
                        'sobre_tasa' => $detSobreTasa,
                        'subtotal' => $detSubtotal,
                        'total' => $detTotal,
                    ]);

                    $this->ventaRepository->decrementInventario($detData['producto_id'], $bodegaId, (float) $detData['cantidad']);

                    $this->ventaRepository->createMovimientoInventario([
                        'tipo_movimiento' => 'venta',
                        'producto_id' => $detData['producto_id'],
                        'bodega_origen_id' => $bodegaId,
                        'bodega_destino_id' => null,
                        'cantidad' => $detData['cantidad'],
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
}
