<?php

namespace App\Modules\Bodegas\Application\Services;

use App\Models\Bodega;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Bodegas\Application\DTOs\CreateBodegaDTO;
use App\Modules\Bodegas\Application\DTOs\UpdateBodegaDTO;
use App\Modules\Bodegas\Application\Interfaces\BodegaRepositoryInterface;

class BodegaService
{
    public function __construct(
        protected BodegaRepositoryInterface $bodegaRepository
    ) {}

    public function paginate(array $filters = [], int $perPage = 10)
    {
        return $this->bodegaRepository->paginate($filters, $perPage);
    }

    public function findById(int $id): Bodega
    {
        $bodega = $this->bodegaRepository->findById($id);

        if (!$bodega) {
            throw new HttpException(404, 'Bodega no encontrada.');
        }

        return $bodega;
    }

    public function create(CreateBodegaDTO $dto): Bodega
    {
        if ($dto->is_principal) {
            $this->bodegaRepository->clearPrincipalExcept();
        }

        return $this->bodegaRepository->create([
            'nombre' => $dto->nombre,
            'codigo' => $dto->codigo,
            'descripcion' => $dto->descripcion,
            'direccion' => $dto->direccion,
            'telefono' => $dto->telefono,
            'responsable_id' => $dto->responsable_id,
            'is_principal' => $dto->is_principal,
            'is_active' => true,
        ]);
    }

    public function update(int $id, UpdateBodegaDTO $dto): Bodega
    {
        $bodega = $this->findById($id);

        if ($dto->is_principal) {
            $this->bodegaRepository->clearPrincipalExcept($bodega->id);
        }

        return $this->bodegaRepository->update($bodega, [
            'nombre' => $dto->nombre,
            'codigo' => $dto->codigo,
            'descripcion' => $dto->descripcion,
            'direccion' => $dto->direccion,
            'telefono' => $dto->telefono,
            'responsable_id' => $dto->responsable_id,
            'is_principal' => $dto->is_principal,
        ]);
    }

    public function changeStatus(int $id, bool $isActive): Bodega
    {
        $bodega = $this->findById($id);

        return $this->bodegaRepository->changeStatus($bodega, $isActive);
    }

    public function delete(int $id): void
    {
        $bodega = $this->findById($id);

        $this->bodegaRepository->delete($bodega);
    }
}