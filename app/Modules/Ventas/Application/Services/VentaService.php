<?php

namespace App\Modules\Ventas\Application\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Venta;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Ventas\Application\DTOs\CreateVentaDTO;
use App\Modules\Ventas\Application\Interfaces\VentaRepositoryInterface;

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

                /*
                |--------------------------------------------------------------------------
                | IMPORTANTE
                |--------------------------------------------------------------------------
                | se puede cambiar la bodega según:
                | - usuario
                | - islero
                | - estación
                | - POS
                |--------------------------------------------------------------------------
                */

                $bodegaId = 1;

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

                if ((float) $cliente->cupo_disponible < (float) $saldoPendiente) {
                    throw new HttpException(
                        422,
                        'El cliente no tiene cupo suficiente.'
                    );
                }
            }


            $venta = $this->ventaRepository->createVenta([
                'prefijo' => 'POS',
                'numero_factura' => $this->ventaRepository->nextNumeroFactura(),
                'cliente_id' => $dto->cliente_id,
                'user_id' => $dto->user_id,
                'tipo_venta' => $dto->tipo_venta,
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
            ]);


            foreach ($dto->detalles as $detalle) {

                $this->ventaRepository->createDetalle([
                    'venta_id' => $venta->id,
                    'producto_id' => $detalle->producto_id,
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

                $bodegaId = 1;

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

            foreach ($venta->detalles as $detalle) {
                $bodegaId = 1;

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
}