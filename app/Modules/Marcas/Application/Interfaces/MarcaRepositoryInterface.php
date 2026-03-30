<?php

namespace App\Modules\Marcas\Application\Interfaces;

use App\Models\Marca;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MarcaRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?Marca;

    public function create(array $data): Marca;

    public function update(Marca $marca, array $data): Marca;

    public function changeStatus(Marca $marca, bool $isActive): Marca;

    public function delete(Marca $marca): void;
}