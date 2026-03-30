<?php

namespace App\Modules\Clientes\Infrastructure\Repositories;

use App\Models\Cliente;
use App\Modules\Clientes\Application\Interfaces\ClienteRepositoryInterface;

class ClienteRepository implements ClienteRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10)
    {
        $query = Cliente::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                ->orWhere('apellidos', 'like', "%{$search}%")
                  ->orWhere('documento', 'like', "%{$search}%")
                  ->orWhere('telefono_uno', 'like', "%{$search}%")
                  ->orWhere('telefono_dos', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function findById(int $id): ?Cliente
    {
        return Cliente::find($id);
    }

    public function create(array $data): Cliente
    {
        return Cliente::create($data);
    }

    public function update(Cliente $cliente, array $data): Cliente
    {
        $cliente->update($data);
        return $cliente->fresh();
    }

    public function changeStatus(Cliente $cliente, bool $isActive): Cliente
    {
        $cliente->update(['is_active' => $isActive]);
        return $cliente->fresh();
    }
}