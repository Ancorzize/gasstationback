<?php

namespace App\Modules\PreciosCombustible\Application\Interfaces;

use App\Models\PrecioCombustible;

interface PrecioCombustibleRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10);

    public function findById(int $id): ?PrecioCombustible;

    public function create(array $data): PrecioCombustible;

    public function cerrarPreciosActivosProducto(int $productoId): void;

    public function changeStatus(PrecioCombustible $precio, bool $isActive): PrecioCombustible;
}