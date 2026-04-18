<?php

namespace App\Modules\Compras\Application\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Compra;
use App\Models\PagoCompra;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Compras\Application\DTOs\CreateCompraDTO;
use App\Modules\Compras\Application\DTOs\UpdateCompraDTO;
use App\Modules\Compras\Application\DTOs\CreatePagoCompraDTO;
use App\Modules\Compras\Application\Interfaces\CompraRepositoryInterface;

class CompraService
{
    public function __construct(
        protected CompraRepositoryInterface $compraRepository
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
            $subtotal = collect($dto->detalles)->sum(fn ($item) => $item->subtotal());
            $impuesto = $dto->impuesto;
            $total = $subtotal + $impuesto;

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
                'total' => $total,
                'total_pagado' => 0,
                'saldo_pendiente' => $total,
                'observacion' => $dto->observacion,
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

            $subtotal = collect($dto->detalles)->sum(fn ($item) => $item->subtotal());
            $impuesto = $dto->impuesto;
            $total = $subtotal + $impuesto;

            $this->compraRepository->update($compra, [
                'proveedor_id' => $dto->proveedor_id,
                'bodega_id' => $dto->bodega_id,
                'numero_documento' => $dto->numero_documento,
                'fecha_compra' => $dto->fecha_compra,
                'fecha_vencimiento' => $dto->fecha_vencimiento,
                'tipo_pago' => $dto->tipo_pago,
                'subtotal' => $subtotal,
                'impuesto' => $impuesto,
                'total' => $total,
                'total_pagado' => 0,
                'saldo_pendiente' => $total,
                'observacion' => $dto->observacion,
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
                ]);
            }

            return $this->findById($compra->id);
        });
    }

    public function confirmar(int $id): Compra
    {
        return DB::transaction(function () use ($id) {
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

            $estadoPago = $compra->tipo_pago === 'contado' ? 'pagado' : 'pendiente';
            $totalPagado = $compra->tipo_pago === 'contado' ? (float) $compra->total : 0;
            $saldoPendiente = $compra->tipo_pago === 'contado' ? 0 : (float) $compra->total;

            $this->compraRepository->update($compra, [
                'estado' => 'confirmada',
                'estado_pago' => $estadoPago,
                'total_pagado' => $totalPagado,
                'saldo_pendiente' => $saldoPendiente,
            ]);

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
                throw new HttpException(422, 'Solo se pueden registrar pagos a compras confirmadas.');
            }

            if ((float) $compra->saldo_pendiente <= 0) {
                throw new HttpException(422, 'La compra ya se encuentra pagada.');
            }

            if ((float) $dto->monto > (float) $compra->saldo_pendiente) {
                throw new HttpException(422, 'El monto no puede superar el saldo pendiente.');
            }

            $this->compraRepository->createPago([
                'compra_id' => $compra->id,
                'user_id' => $dto->user_id,
                'fecha_pago' => $dto->fecha_pago,
                'monto' => $dto->monto,
                'metodo_pago' => $dto->metodo_pago,
                'observacion' => $dto->observacion,
            ]);

            $cajaAbierta = $this->compraRepository->getCajaAbierta();
        
            if (!$cajaAbierta) {
                throw new HttpException(422, 'No se puede registrar el pago porque no hay una caja abierta.');
            }

            $this->compraRepository->createMovimientoCaja([
                'caja_id' => $cajaAbierta->id,
                'tipo_movimiento' => 'egreso',
                'categoria_movimiento' => 'pago_proveedor',
                'origen_modulo' => 'compras',
                'origen_id' => $compra->id,
                'medio_pago' => $dto->metodo_pago, // Importante para el cuadre
                'monto' => $dto->monto,
                'descripcion' => "Pago a proveedor por Compra #{$compra->id}",
                'user_id' => $dto->user_id,
                'fecha_movimiento' => now(),
            ]);

            $nuevoTotalPagado = (float) $compra->total_pagado + (float) $dto->monto;
            $nuevoSaldoPendiente = (float) $compra->total - $nuevoTotalPagado;

            $nuevoEstadoPago = $nuevoSaldoPendiente <= 0 ? 'pagado' : 'pendiente';

            $this->compraRepository->update($compra, [
                'total_pagado' => $nuevoTotalPagado,
                'saldo_pendiente' => $nuevoSaldoPendiente,
                'estado_pago' => $nuevoEstadoPago,
            ]);

            return $this->findById($compra->id);
        });
    }
}