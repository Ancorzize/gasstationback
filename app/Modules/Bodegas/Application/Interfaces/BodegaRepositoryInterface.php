<?php

namespace App\Modules\Bodegas\Application\Interfaces;

use App\Models\Bodega;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BodegaRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?Bodega;

    public function create(array $data): Bodega;

    public function update(Bodega $bodega, array $data): Bodega;

    public function changeStatus(Bodega $bodega, bool $isActive): Bodega;

    public function delete(Bodega $bodega): void;

    public function clearPrincipalExcept(?int $exceptId = null): void;
}