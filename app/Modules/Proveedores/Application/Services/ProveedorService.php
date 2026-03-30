<?php

namespace App\Modules\Proveedores\Application\Services;

use App\Models\Proveedor;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Proveedores\Application\DTOs\CreateProveedorDTO;
use App\Modules\Proveedores\Application\DTOs\UpdateProveedorDTO;
use App\Modules\Proveedores\Application\Interfaces\ProveedorRepositoryInterface;
use GuzzleHttp\Handler\Proxy;

class ProveedorService
{
    public function __construct(
        protected ProveedorRepositoryInterface $repository
    ) {}

    public function paginate(array $filters, int $perPage)
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function create(CreateProveedorDTO $dto): Proveedor
    {
        return $this->repository->create([
            'nombre' => $dto->nombre,
            'nit' => $dto->nit,
            'telefono' => $dto->telefono,
            'direccion' => $dto->direccion,
            'email' => $dto->email,
            'is_active' => true,
        ]);
    }

    public function update(int $id, UpdateProveedorDTO $dto): Proveedor
    {
        $cliente = $this->findById($id);

        return $this->repository->update($cliente, [
            'nombre' => $dto->nombre,
            'nit' => $dto->nit,
            'telefono' => $dto->telefono,
            'direccion' => $dto->direccion,
            'email' => $dto->email,
            'is_active' => true,
        ]);
    }

    public function changeStatus(int $id, bool $isActive): Proveedor
    {
        $cliente = $this->findById($id);

        return $this->repository->changeStatus($cliente, $isActive);
    }


    public function findById(int $id): Proveedor
    {
        $cliente = $this->repository->findById($id);

        if (!$cliente) {
            throw new HttpException(404, 'Proveedor no encontrado.');
        }

        return $cliente;
    }
}