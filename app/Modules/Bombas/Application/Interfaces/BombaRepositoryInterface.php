<?php

namespace App\Modules\Bombas\Application\Interfaces;

use App\Models\Bomba;

interface BombaRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10);

    public function findById(int $id): ?Bomba;

    public function create(array $data): Bomba;

    public function update(Bomba $bomba, array $data): Bomba;

    public function changeStatus(Bomba $bomba, bool $isActive): Bomba;
}