<?php

namespace App\Modules\MovimientosInventario\Application\Services;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\MovimientosInventario\Application\DTOs\CreateMovimientoInventarioDTO;
use App\Modules\MovimientosInventario\Application\Interfaces\MovimientoInventarioRepositoryInterface;

class MovimientoInventarioService
{
    public function __construct(
        protected MovimientoInventarioRepositoryInterface $repository
    ) {}

    public function paginate(array $filters = [], int $perPage = 10)
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function trasladar(CreateMovimientoInventarioDTO $dto)
    {
        return DB::transaction(function () use ($dto) {
            if ($dto->bodega_origen_id === $dto->bodega_destino_id) {
                throw new HttpException(422, 'La bodega origen y destino no pueden ser iguales.');
            }

            $inventarioOrigen = $this->repository->findInventario(
                $dto->producto_id,
                $dto->bodega_origen_id
            );

            if (!$inventarioOrigen) {
                throw new HttpException(422, 'El producto no existe en la bodega origen.');
            }

            if ((float) $inventarioOrigen->cantidad < (float) $dto->cantidad) {
                throw new HttpException(422, 'Stock insuficiente en la bodega origen.');
            }

            $this->repository->decrementInventario(
                $dto->producto_id,
                $dto->bodega_origen_id,
                $dto->cantidad
            );

            $inventarioDestino = $this->repository->findInventario(
                $dto->producto_id,
                $dto->bodega_destino_id
            );

            if (!$inventarioDestino) {
                $this->repository->createInventario([
                    'producto_id' => $dto->producto_id,
                    'bodega_id' => $dto->bodega_destino_id,
                    'cantidad' => 0,
                ]);
            }

            $this->repository->incrementInventario(
                $dto->producto_id,
                $dto->bodega_destino_id,
                $dto->cantidad
            );

            return $this->repository->createMovimiento([
                'tipo_movimiento' => 'traslado',
                'producto_id' => $dto->producto_id,
                'bodega_origen_id' => $dto->bodega_origen_id,
                'bodega_destino_id' => $dto->bodega_destino_id,
                'cantidad' => $dto->cantidad,
                'observacion' => $dto->observacion,
                'user_id' => $dto->user_id,
            ]);
        });
    }
}