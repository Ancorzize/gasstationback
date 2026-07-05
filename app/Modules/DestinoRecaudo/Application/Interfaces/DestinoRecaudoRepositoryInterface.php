<?php

namespace App\Modules\DestinoRecaudo\Application\Interfaces;

use App\Models\DestinoRecaudo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DestinoRecaudoRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?DestinoRecaudo;

    public function create(array $data): DestinoRecaudo;

    public function update(DestinoRecaudo $destino, array $data): DestinoRecaudo;

    public function changeStatus(DestinoRecaudo $destino, bool $isActive): DestinoRecaudo;

    public function delete(DestinoRecaudo $destino): void;
}