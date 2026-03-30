<?php

namespace App\Modules\Proveedores\Application\Interfaces;

use App\Models\Proveedor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProveedorRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?Proveedor;

    public function create(array $data): Proveedor;

    public function update(Proveedor $proveedor, array $data): Proveedor;

    public function changeStatus(Proveedor $proveedor, bool $isActive): Proveedor;
}