<?php

namespace App\Modules\Clientes\Application\Services;

use App\Models\Cliente;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Clientes\Application\DTOs\CreateClienteDTO;
use App\Modules\Clientes\Application\DTOs\UpdateClienteDTO;
use App\Modules\Clientes\Application\Interfaces\ClienteRepositoryInterface;

class ClienteService
{
    public function __construct(
        protected ClienteRepositoryInterface $clienteRepository
    ) {}

    public function paginate(array $filters = [], int $perPage = 1000)
    {
        return $this->clienteRepository->paginate($filters, $perPage);
    }

    public function findById(int $id): Cliente
    {
        $cliente = $this->clienteRepository->findById($id);

        if (!$cliente) {
            throw new HttpException(404, 'Cliente no encontrado.');
        }

        return $cliente;
    }

    public function create(CreateClienteDTO $dto): Cliente
    {
        return $this->clienteRepository->create([
            'nombre' => $dto->nombre,
            'apellidos' => $dto->apellidos,
            'documento' => $dto->documento,
            'telefono_uno' => $dto->telefono_uno,
            'telefono_dos' => $dto->telefono_dos,
            'direccion' => $dto->direccion,
            'email' => $dto->email,
            'is_active' => true,
        ]);
    }

    public function update(int $id, UpdateClienteDTO $dto): Cliente
    {
        $cliente = $this->findById($id);

        return $this->clienteRepository->update($cliente, [
            'nombre' => $dto->nombre,
            'apellidos' => $dto->apellidos,
            'documento' => $dto->documento,
            'telefono_uno' => $dto->telefono_uno,
            'telefono_dos' => $dto->telefono_dos,
            'direccion' => $dto->direccion,
            'email' => $dto->email
        ]);
    }

    public function changeStatus(int $id, bool $isActive): Cliente
    {
        $cliente = $this->findById($id);

        return $this->clienteRepository->changeStatus($cliente, $isActive);
    }
}