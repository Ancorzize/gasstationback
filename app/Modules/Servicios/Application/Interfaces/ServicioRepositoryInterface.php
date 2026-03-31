<?php

namespace App\Modules\Servicios\Application\Interfaces;

use App\Models\Servicio;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ServicioRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?Servicio;

    public function create(array $data): Servicio;

    public function update(Servicio $servicio, array $data): Servicio;

    public function changeStatus(Servicio $servicio, bool $isActive): Servicio;

    public function delete(Servicio $servicio): void;
}