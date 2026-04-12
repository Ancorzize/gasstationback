<?php

namespace App\Modules\Inventarios\Application\Interfaces;

use App\Models\Inventario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface InventarioRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findByProductoAndBodega(int $productoId, int $bodegaId): ?Inventario;

    public function findById(int $id): ?Inventario;

    public function create(array $data): Inventario;

    public function update(Inventario $inventario, array $data): Inventario;

    public function getByBodega(int $bodegaId);

    public function getResumen(array $filters = []);

    public function getByBodegaAndUser(int $bodegaId);
}