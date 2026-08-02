<?php

namespace App\Modules\Mangueras\Application\Interfaces;

use App\Models\Manguera;

interface MangueraRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10);

    public function findById(int $id): ?Manguera;

    public function create(array $data): Manguera;

    public function update(Manguera $manguera, array $data): Manguera;

    public function changeStatus(Manguera $manguera, bool $isActive): Manguera;

    public function paginateLecturas(array $filters = [], int $perPage = 10);
}