<?php

namespace App\Modules\Compras\Application\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Compra;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Compras\Application\DTOs\CreateCompraDTO;
use App\Modules\Compras\Application\DTOs\UpdateCompraDTO;
use App\Modules\Compras\Application\DTOs\CreatePagoCompraDTO;
use App\Modules\Compras\Application\Interfaces\CompraRepositoryInterface;
use App\Modules\Caja\Application\Interfaces\CajaRepositoryInterface;
use App\Modules\Compras\Application\DTOs\ConfirmarCompraDTO;

class CompraService
{
    public function __construct(
        protected CompraRepositoryInterface $compraRepository,
        protected CajaRepositoryInterface $cajaRepository
    ) {}

    public function paginate(array $filters = [], int $perPage = 10)
    {
        return $this->compraRepository->paginate($filters, $perPage);
    }

    public function findById(int $id): Compra
    {
        $compra = $this->compraRepository->findById($id);

        if (!$compra) {
            throw new HttpException(404, 'Compra no encontrada.');
        }

        return $compra;
    }

    public function create(CreateCompraDTO $dto): Compra
    {
        return DB::transaction(function () use ($dto) {
            $subtotal = collect($dto->detalles)->sum(fn($item) => $item->subtotal());
            $impuesto = $dto->impuesto;
            $soldicom = $dto->soldicom;
            $sobre_tasa = $dto->sobre_tasa;
            $total = $subtotal + $impuesto + $soldicom + $sobre_tasa;

            $compra = $this->compraRepository->create([
                'proveedor_id' => $dto->proveedor_id,
                'bodega_id' => $dto->bodega_id,
                'user_id' => $dto->user_id,
                'numero_documento' => $dto->numero_documento,
                'fecha_compra' => $dto->fecha_compra,
                'fecha_vencimiento' => $dto->fecha_vencimiento,
                'tipo_pago' => $dto->tipo_pago,
                'estado' => 'borrador',
                'estado_pago' => 'pendiente',
                'subtotal' => $subtotal,
                'impuesto' => $impuesto,
                'soldicom' => $soldicom,
                'sobre_tasa' => $sobre_tasa,
                'total' => $total,
                'total_pagado' => 0,
                'saldo_pendiente' => $total,
                'observacion' => $dto->observacion,
                'numero_comprobante' => $dto->numero_comprobante
            ]);

            foreach ($dto->detalles as $detalle) {
                $this->compraRepository->createDetalle([
                    'compra_id' => $compra->id,
                    'producto_id' => $detalle->producto_id,
                    'cantidad' => $detalle->cantidad,
                    'costo_unitario' => $detalle->costo_unitario,
                    'iva' => $detalle->iva,
                    'soldicom' => $detalle->soldicom,
                    'subtotal' => $detalle->subtotal(),
                    'total' => $detalle->total,
                    'iva_valor' => $detalle->iva_valor,
                    'sobre_tasa' => $detalle->sobre_tasa,
                ]);
            }

            return $this->findById($compra->id);
        });
    }

    public function update(int $id, UpdateCompraDTO $dto): Compra
    {
        return DB::transaction(function () use ($id, $dto) {
            $compra = $this->findById($id);

            if ($compra->estado !== 'borrador') {
                throw new HttpException(422, 'Solo se pueden editar compras en borrador.');
            }

            $subtotal = collect($dto->detalles)->sum(fn($item) => $item->subtotal());
            $impuesto = $dto->impuesto;
            $soldicom = $dto->soldicom;
            $sobre_tasa = $dto->sobre_tasa;
            $total = $subtotal + $impuesto + $soldicom + $sobre_tasa;

            $this->compraRepository->update($compra, [
                'proveedor_id' => $dto->proveedor_id,
                'bodega_id' => $dto->bodega_id,
                'numero_documento' => $dto->numero_documento,
                'fecha_compra' => $dto->fecha_compra,
                'fecha_vencimiento' => $dto->fecha_vencimiento,
                'tipo_pago' => $dto->tipo_pago,
                'subtotal' => $subtotal,
                'impuesto' => $impuesto,
                'soldicom' => $soldicom,
                'sobre_tasa' => $sobre_tasa,
                'total' => $total,
                'total_pagado' => 0,
                'saldo_pendiente' => $total,
                'observacion' => $dto->observacion,
                'numero_comprobante' => $dto->numero_comprobante
            ]);

            $this->compraRepository->deleteDetallesByCompra($compra->id);

            foreach ($dto->detalles as $detalle) {
                $this->compraRepository->createDetalle([
                    'compra_id' => $compra->id,
                    'producto_id' => $detalle->producto_id,
                    'cantidad' => $detalle->cantidad,
                    'costo_unitario' => $detalle->costo_unitario,
                    'subtotal' => $detalle->subtotal(),
                    'iva' => $detalle->iva,
                    'soldicom' => $detalle->soldicom,
                    'total' => $detalle->total,
                    'iva_valor' => $detalle->iva_valor,
                    'sobre_tasa' => $detalle->sobre_tasa
                ]);
            }

            return $this->findById($compra->id);
        });
    }

    public function confirmar(int $id, ConfirmarCompraDTO $dto): Compra
    {
        return DB::transaction(function () use ($id, $dto) {
            $compra = $this->findById($id);

            if ($compra->estado !== 'borrador') {
                throw new HttpException(422, 'Solo se pueden confirmar compras en borrador.');
            }

            foreach ($compra->detalles as $detalle) {
                $inventario = $this->compraRepository->findInventario(
                    $detalle->producto_id,
                    $compra->bodega_id
                );

                if (!$inventario) {
                    $this->compraRepository->createInventario([
                        'producto_id' => $detalle->producto_id,
                        'bodega_id' => $compra->bodega_id,
                        'cantidad' => 0,
                    ]);
                }

                $this->compraRepository->incrementInventario(
                    $detalle->producto_id,
                    $compra->bodega_id,
                    (float) $detalle->cantidad
                );

                $this->compraRepository->createMovimientoInventario([
                    'tipo_movimiento' => 'entrada_compra',
                    'producto_id' => $detalle->producto_id,
                    'bodega_origen_id' => null,
                    'bodega_destino_id' => $compra->bodega_id,
                    'cantidad' => $detalle->cantidad,
                    'observacion' => 'Entrada por compra #' . $compra->id,
                    'user_id' => $compra->user_id,
                ]);

                $this->compraRepository->updateProductoPrecioCompra(
                    $detalle->producto_id,
                    (float) $detalle->costo_unitario
                );
            }

            $estadoPago = $compra->tipo_pago != 'credito' ? 'pagado' : 'pendiente';
            $totalPagado = $compra->tipo_pago != 'credito' ? (float) $compra->total : 0;
            $saldoPendiente = $compra->tipo_pago != 'credito' ? 0 : (float) $compra->total;

            $this->compraRepository->update($compra, [
                'estado' => 'confirmada',
                'estado_pago' => $estadoPago,
                'total_pagado' => $totalPagado,
                'saldo_pendiente' => $saldoPendiente,
            ]);

            if ($compra->tipo_pago != 'credito') {

                if (!$dto->caja_id) {
                    throw new HttpException(
                        422,
                        'Debe seleccionar una caja para compras de contado.'
                    );
                }
                $caja = $this->compraRepository
                    ->findCajaById(
                        $dto->caja_id
                    );

                if (!$caja) {

                    throw new HttpException(
                        422,
                        'La caja seleccionada no existe.'
                    );
                }

                if ($caja->estado != 'abierta') {

                    throw new HttpException(
                        422,
                        'La caja se encuentra cerrada.'
                    );
                }

                $tipoEsperado =
                    in_array(
                        $compra->tipo_pago,
                        ['efectivo', 'consignacion']
                    )
                    ? 'efectivo'
                    : 'digital';

                if ($caja->tipo_caja != $tipoEsperado) {

                    throw new HttpException(
                        422,
                        'La caja seleccionada no corresponde al método de pago.'
                    );
                }

                $saldoCaja =
                    $this->cajaRepository
                    ->sumMovimientosByTipo(
                        $caja->id,
                        'ingreso'
                    )
                    -
                    $this->cajaRepository
                    ->sumMovimientosByTipo(
                        $caja->id,
                        'egreso'
                    );

                if ($saldoCaja < $compra->total) {

                    throw new HttpException(
                        422,
                        'La caja no tiene saldo suficiente.'
                    );
                }

                $this->compraRepository
                    ->createMovimientoCaja([
                        'caja_id' => $caja->id,
                        'tipo_movimiento' => 'egreso',
                        'categoria_movimiento' => 'compra',
                        'origen_modulo' => 'compras',
                        'origen_id' => $compra->id,
                        'medio_pago' => $compra->tipo_pago,
                        'monto' => $compra->total,
                        'descripcion' => 'Compra #' . $compra->id,
                        'user_id' => $compra->user_id,
                        'fecha_movimiento' => now(),
                    ]);
            }


            return $this->findById($compra->id);
        });
    }

    public function getPagos(int $compraId)
    {
        $compra = $this->findById($compraId);

        return $this->compraRepository->getPagosByCompra($compra->id);
    }

    public function registrarPago(CreatePagoCompraDTO $dto): Compra
    {
        return DB::transaction(function () use ($dto) {

            $compra = $this->findById($dto->compra_id);

            if ($compra->estado !== 'confirmada') {

                throw new HttpException(
                    422,
                    'Solo se pueden registrar pagos a compras confirmadas.'
                );

            }

            if ((float) $compra->saldo_pendiente <= 0) {

                throw new HttpException(
                    422,
                    'La compra ya se encuentra pagada.'
                );

            }

            if ((float) $dto->monto > (float) $compra->saldo_pendiente) {

                throw new HttpException(
                    422,
                    'El monto no puede superar el saldo pendiente.'
                );

            }

            $caja = $this->compraRepository
                ->findCajaById($dto->caja_id);

            if (!$caja) {

                throw new HttpException(
                    422,
                    'La caja seleccionada no existe.'
                );

            }

            if ($caja->estado !== 'abierta') {

                throw new HttpException(
                    422,
                    'La caja seleccionada se encuentra cerrada.'
                );

            }

            $tipoEsperado = in_array(
                $dto->metodo_pago,
                [
                    'efectivo',
                    'consignacion'
                ]
            )
                ? 'efectivo'
                : 'digital';

            if ($caja->tipo_caja !== $tipoEsperado) {

                throw new HttpException(
                    422,
                    'La caja seleccionada no corresponde al método de pago.'
                );

            }

            $saldoCaja =
                $this->cajaRepository
                    ->sumMovimientosByTipo(
                        $caja->id,
                        'ingreso'
                    )
                -
                $this->cajaRepository
                    ->sumMovimientosByTipo(
                        $caja->id,
                        'egreso'
                    );

            if ($dto->monto > $saldoCaja) {

                throw new HttpException(
                    422,
                    'No hay saldo suficiente en la caja.'
                );

            }

            $this->compraRepository->createPago([

                'compra_id' => $compra->id,

                'user_id' => $dto->user_id,

                'fecha_pago' => $dto->fecha_pago,

                'monto' => $dto->monto,

                'metodo_pago' => $dto->metodo_pago,

                'observacion' => $dto->observacion,

            ]);

            $this->compraRepository
                ->createMovimientoCaja([

                    'caja_id' => $caja->id,

                    'tipo_movimiento' => 'egreso',

                    'categoria_movimiento' => 'pago_proveedor',

                    'origen_modulo' => 'compras',

                    'origen_id' => $compra->id,

                    'medio_pago' => $dto->metodo_pago,

                    'monto' => $dto->monto,

                    'descripcion' => "Pago a proveedor por Compra #{$compra->id}",

                    'user_id' => $dto->user_id,

                    'fecha_movimiento' => now(),

                ]);

            $nuevoTotalPagado =
                (float) $compra->total_pagado +
                (float) $dto->monto;

            $nuevoSaldoPendiente =
                (float) $compra->total -
                $nuevoTotalPagado;

            $nuevoEstadoPago =
                $nuevoSaldoPendiente <= 0
                    ? 'pagado'
                    : 'pendiente';

            $this->compraRepository
                ->update($compra, [

                    'total_pagado' => $nuevoTotalPagado,

                    'saldo_pendiente' => $nuevoSaldoPendiente,

                    'estado_pago' => $nuevoEstadoPago,

                ]);

            return $this->findById($compra->id);

        });
    }
}
