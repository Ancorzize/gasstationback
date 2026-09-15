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
            $search = trim($filters['search']);
            $words = array_filter(explode(' ', $search));

            $query->where(function ($q) use ($search, $words) {
                $q->where('nombre', 'ilike', "%{$search}%")
                  ->orWhere('apellidos', 'ilike', "%{$search}%")
                  ->orWhere('documento', 'ilike', "%{$search}%")
                  ->orWhereRaw("CONCAT(COALESCE(nombre, ''), ' ', COALESCE(apellidos, '')) LIKE ?", ["%{$search}%"]);

                if (count($words) > 1) {
                    $q->orWhere(function ($subQ) use ($words) {
                        foreach ($words as $word) {
                            $subQ->where(function ($wQ) use ($word) {
                                $wQ->where('nombre', 'ilike', "%{$word}%")
                                   ->orWhere('apellidos', 'ilike', "%{$word}%")
                                   ->orWhere('documento', 'ilike', "%{$word}%");
                            });
                        }
                    });
                }
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