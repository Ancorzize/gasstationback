<?php

namespace App\Modules\MovimientosInventario\Application\Services;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\MovimientosInventario\Application\DTOs\CreateMovimientoInventarioDTO;
use App\Modules\MovimientosInventario\Application\Interfaces\MovimientoInventarioRepositoryInterface;
use App\Modules\MovimientosInventario\Application\DTOs\CreateMovimientoInventarioMasivoDTO;
class MovimientoInventarioService
{
    public function __construct(
        protected MovimientoInventarioRepositoryInterface $repository
    ) {}

    public function getAll(array $filters = [])
    {
        return $this->repository->getAll($filters);
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

            $codigoLote = $this->repository->nextCodigoLote();

            return $this->repository->createMovimiento([
                'codigo_lote' => $codigoLote,
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

    public function trasladarMasivo(
        CreateMovimientoInventarioMasivoDTO $dto
    ): array {
        return DB::transaction(function () use ($dto) {

            if (
                $dto->bodega_origen_id ===
                $dto->bodega_destino_id
            ) {
                throw new HttpException(
                    422,
                    'La bodega origen y destino no pueden ser iguales.'
                );
            }

            if (empty($dto->items)) {
                throw new HttpException(
                    422,
                    'Debe enviar al menos un producto.'
                );
            }

            $inventariosOrigen = [];

            foreach ($dto->items as $item) {

                $inventarioOrigen =
                    $this->repository->findInventarioForUpdate(
                        $item->producto_id,
                        $dto->bodega_origen_id
                    );

                if (!$inventarioOrigen) {
                    throw new HttpException(
                        422,
                        "El producto {$item->producto_id} no existe en la bodega origen."
                    );
                }

                $stockActual = (float) $inventarioOrigen->cantidad;
                $cantidadSolicitada = (float) $item->cantidad;

                if ($stockActual < $cantidadSolicitada) {
                    throw new HttpException(
                        422,
                        "Stock insuficiente para el producto {$item->producto_id}. " .
                        "Disponible: {$stockActual}. " .
                        "Solicitado: {$cantidadSolicitada}."
                    );
                }

                $inventariosOrigen[$item->producto_id] =
                    $inventarioOrigen;
            }

            /*
            * Un solo código para todo el movimiento masivo.
            *
            * Ejemplo:
            * TRA-000206
            */
            $codigoLote = $this->repository->nextCodigoLote();

            $movimientos = [];

            foreach ($dto->items as $item) {

                /*
                * Descontar origen
                */
                $this->repository->decrementInventario(
                    $item->producto_id,
                    $dto->bodega_origen_id,
                    $item->cantidad
                );

                /*
                * Buscar inventario destino
                */
                $inventarioDestino =
                    $this->repository->findInventario(
                        $item->producto_id,
                        $dto->bodega_destino_id
                    );

                /*
                * Si no existe, crearlo
                */
                if (!$inventarioDestino) {

                    $this->repository->createInventario([
                        'producto_id' => $item->producto_id,
                        'bodega_id' => $dto->bodega_destino_id,
                        'cantidad' => 0,
                    ]);
                }

                /*
                * Incrementar destino
                */
                $this->repository->incrementInventario(
                    $item->producto_id,
                    $dto->bodega_destino_id,
                    $item->cantidad
                );

                /*
                * Registrar movimiento.
                *
                * Todos los productos del mismo movimiento
                * masivo tendrán el mismo codigo_lote.
                */
                $movimientos[] =
                    $this->repository->createMovimiento([
                        'codigo_lote' => $codigoLote,
                        'tipo_movimiento' => 'traslado',
                        'producto_id' => $item->producto_id,
                        'bodega_origen_id' => $dto->bodega_origen_id,
                        'bodega_destino_id' => $dto->bodega_destino_id,
                        'cantidad' => $item->cantidad,
                        'observacion' => $dto->observacion,
                        'user_id' => $dto->user_id,
                    ]);
            }

            return $movimientos;
        });
    }

    public function getLotes(array $filters = [])
    {
        return $this->repository->getLotes($filters);
    }

    public function getProductosByCodigoLote(string $codigoLote)
    {
        return $this->repository->getProductosByCodigoLote(
            $codigoLote
        );
    }
}