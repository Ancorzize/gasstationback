<?php

namespace App\Modules\Ventas\Application\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Venta;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Ventas\Application\DTOs\CreateVentaDTO;
use App\Modules\Ventas\Application\Interfaces\VentaRepositoryInterface;
use App\Modules\Ventas\Application\DTOs\CreateVentaCombustibleDTO;

class VentaService
{
    public function __construct(
        protected VentaRepositoryInterface $ventaRepository
    ) {}

    public function paginate(array $filters = [], int $perPage = 10)
    {
        return $this->ventaRepository->paginate($filters, $perPage);
    }

    public function findById(int $id): Venta
    {
        $venta = $this->ventaRepository->findById($id);

        if (!$venta) {
            throw new HttpException(404, 'Venta no encontrada.');
        }

        return $venta;
    }

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
                'observacion' => $dto->observacion,
                'turno_islero_id' => $turnoAbierto?->id,
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


                $tipoCaja = $pago->metodo_pago === 'efectivo'
                    ? 'efectivo'
                    : 'digital';

                $caja = $this->ventaRepository->getCajaAbiertaByTipo($tipoCaja);

                if (!$caja) {
                    throw new HttpException(
                        422,
                        "No hay caja {$tipoCaja} abierta."
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

                $isIslero = $turnoAbierto->usuario->hasRole('islero');
                if(!$isIslero)
                {

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

    public function anular(int $id, string $motivoAnulacion, int $userId): Venta
    {
        return DB::transaction(function () use ($id, $motivoAnulacion, $userId) {
            $venta = $this->findById($id);

            if ($venta->estado === 'anulada') {
                throw new HttpException(422, 'La venta ya se encuentra anulada.');
            }

            $bodegaId = (int) $venta->bodega_id;

            if (!$bodegaId) {
                throw new HttpException(422, 'La venta no tiene bodega asociada.');
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


            if ((float) $venta->saldo_pendiente > 0 && $venta->cliente_id) {
                $cliente = $this->ventaRepository->findClienteById($venta->cliente_id);

                if ($cliente) {
                    $saldoAnterior = (float) $cliente->saldo_credito;
                    $saldoNuevo = $saldoAnterior - (float) $venta->saldo_pendiente;

                    if ($saldoNuevo < 0) {
                        $saldoNuevo = 0;
                    }

                    $this->ventaRepository->updateCliente($cliente, [
                        'saldo_credito' => $saldoNuevo,
                    ]);

                    $this->ventaRepository->createMovimientoCartera([
                        'cliente_id' => $cliente->id,
                        'tipo_movimiento' => 'anulacion',
                        'origen_modulo' => 'ventas',
                        'origen_id' => $venta->id,
                        'valor' => $venta->saldo_pendiente,
                        'saldo_anterior' => $saldoAnterior,
                        'saldo_nuevo' => $saldoNuevo,
                        'medio_pago' => null,
                        'descripcion' => "Anulación venta crédito #{$venta->id}: {$motivoAnulacion}",
                        'user_id' => $userId,
                        'fecha_movimiento' => now(),
                    ]);
                }
            }


            $this->ventaRepository->updateVenta($venta, [
                'estado' => 'anulada',
                'motivo_anulacion' => $motivoAnulacion,
                'user_anulacion_id' => $userId,
                'fecha_anulacion' => now(),
            ]);

            return $this->findById($venta->id);
        });
    }

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
                $tipoCaja = $dto->metodo_pago === 'efectivo' ? 'efectivo' : 'digital';

                $caja = $this->ventaRepository->getCajaAbiertaByTipo($tipoCaja);

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
                'observacion' => $dto->observacion,
                'tipo_origen' => 'combustible',
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

                $isIslero = $turnoAbierto->usuario->hasRole('islero');
                if(!$isIslero)
                {
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
}