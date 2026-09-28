<?php

namespace App\Modules\Facturacion\Application\Services;

use App\Models\MapeoCatalogoFacturacion;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Facturacion\Application\DTOs\CreateMapeoCatalogoFacturacionDTO;
use App\Modules\Facturacion\Application\DTOs\UpdateMapeoCatalogoFacturacionDTO;
use App\Modules\Facturacion\Application\Interfaces\MapeoCatalogoFacturacionRepositoryInterface;

class MapeoCatalogoFacturacionService
{
    public function __construct(
        protected MapeoCatalogoFacturacionRepositoryInterface $mapeoRepository
    ) {}

    public function paginate(array $filters = [], int $perPage = 1000)
    {
        return $this->mapeoRepository->paginate($filters, $perPage);
    }

    public function findById(int $id): MapeoCatalogoFacturacion
    {
        $mapeo = $this->mapeoRepository->findById($id);

        if (!$mapeo) {
            throw new HttpException(404, 'Mapeo de catálogo no encontrado.');
        }

        return $mapeo;
    }

    public function create(CreateMapeoCatalogoFacturacionDTO $dto): MapeoCatalogoFacturacion
    {
        $duplicate = $this->mapeoRepository->findDuplicate(
            $dto->proveedor,
            $dto->categoria,
            $dto->codigo_interno
        );

        if ($duplicate) {
            throw new HttpException(
                422,
                "Ya existe un mapeo para el proveedor '{$dto->proveedor}', categoría '{$dto->categoria}' y código interno '{$dto->codigo_interno}'."
            );
        }

        return $this->mapeoRepository->create([
            'proveedor' => strtolower(trim($dto->proveedor)),
            'categoria' => strtolower(trim($dto->categoria)),
            'codigo_interno' => trim($dto->codigo_interno),
            'codigo_externo' => trim($dto->codigo_externo),
            'codigo_externo_secundario' => $dto->codigo_externo_secundario ? trim($dto->codigo_externo_secundario) : null,
            'descripcion' => $dto->descripcion ? trim($dto->descripcion) : null,
            'is_active' => $dto->is_active,
        ]);
    }

    public function update(int $id, UpdateMapeoCatalogoFacturacionDTO $dto): MapeoCatalogoFacturacion
    {
        $mapeo = $this->findById($id);

        $duplicate = $this->mapeoRepository->findDuplicate(
            $dto->proveedor,
            $dto->categoria,
            $dto->codigo_interno,
            $id
        );

        if ($duplicate) {
            throw new HttpException(
                422,
                "Ya existe otro mapeo registrado para el proveedor '{$dto->proveedor}', categoría '{$dto->categoria}' y código interno '{$dto->codigo_interno}'."
            );
        }

        return $this->mapeoRepository->update($mapeo, [
            'proveedor' => strtolower(trim($dto->proveedor)),
            'categoria' => strtolower(trim($dto->categoria)),
            'codigo_interno' => trim($dto->codigo_interno),
            'codigo_externo' => trim($dto->codigo_externo),
            'codigo_externo_secundario' => $dto->codigo_externo_secundario ? trim($dto->codigo_externo_secundario) : null,
            'descripcion' => $dto->descripcion ? trim($dto->descripcion) : null,
            'is_active' => $dto->is_active,
        ]);
    }

    public function delete(int $id): void
    {
        $mapeo = $this->findById($id);
        $this->mapeoRepository->delete($mapeo);
    }
}
