<?php

namespace App\Modules\PagosCompra\Application\Interfaces;

use App\Models\PagoCompra;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PagoCompraRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?PagoCompra;
}