<?php

namespace App\Modules\Estaciones\Application\Interfaces;

use App\Models\Estacion;

interface EstacionRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10);

    public function findById(int $id): ?Estacion;

    public function create(array $data): Estacion;

    public function update(Estacion $estacion, array $data): Estacion;

    public function changeStatus(Estacion $estacion, bool $isActive): Estacion;
}