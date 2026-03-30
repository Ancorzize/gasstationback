<?php

namespace App\Modules\Clientes\Application\Interfaces;

use App\Models\Cliente;

interface ClienteRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10);
    public function findById(int $id): ?Cliente;
    public function create(array $data): Cliente;
    public function update(Cliente $cliente, array $data): Cliente;
    public function changeStatus(Cliente $cliente, bool $isActive): Cliente;
}