<?php

namespace App\Modules\UnidadesMedida\Application\Interfaces;

use App\Models\UnidadMedida;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UnidadMedidaRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?UnidadMedida;

    public function create(array $data): UnidadMedida;

    public function update(UnidadMedida $unidadMedida, array $data): UnidadMedida;

    public function changeStatus(UnidadMedida $unidadMedida, bool $isActive): UnidadMedida;

    public function delete(UnidadMedida $unidadMedida): void;
}