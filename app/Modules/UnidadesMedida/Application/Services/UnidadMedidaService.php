<?php

namespace App\Modules\UnidadesMedida\Application\Services;

use App\Models\UnidadMedida;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\UnidadesMedida\Application\DTOs\CreateUnidadMedidaDTO;
use App\Modules\UnidadesMedida\Application\DTOs\UpdateUnidadMedidaDTO;
use App\Modules\UnidadesMedida\Application\Interfaces\UnidadMedidaRepositoryInterface;
use Illuminate\Database\QueryException;
class UnidadMedidaService
{
    public function __construct(
        protected UnidadMedidaRepositoryInterface $unidadMedidaRepository
    ) {}

    public function paginate(array $filters = [], int $perPage = 10)
    {
        return $this->unidadMedidaRepository->paginate($filters, $perPage);
    }

    public function findById(int $id): UnidadMedida
    {
        $unidadMedida = $this->unidadMedidaRepository->findById($id);

        if (!$unidadMedida) {
            throw new HttpException(404, 'Unidad de medida no encontrada.');
        }

        return $unidadMedida;
    }

    public function create(CreateUnidadMedidaDTO $dto): UnidadMedida
    {
        return $this->unidadMedidaRepository->create([
            'nombre' => $dto->nombre,
            'abreviatura' => $dto->abreviatura,
            'descripcion' => $dto->descripcion,
            'is_active' => true,
        ]);
    }

    public function update(int $id, UpdateUnidadMedidaDTO $dto): UnidadMedida
    {
        $unidadMedida = $this->findById($id);

        return $this->unidadMedidaRepository->update($unidadMedida, [
            'nombre' => $dto->nombre,
            'abreviatura' => $dto->abreviatura,
            'descripcion' => $dto->descripcion,
        ]);
    }

    public function changeStatus(int $id, bool $isActive): UnidadMedida
    {
        $unidadMedida = $this->findById($id);

        return $this->unidadMedidaRepository->changeStatus($unidadMedida, $isActive);
    }

    public function delete(int $id): void
    {
        $unidadMedida = $this->findById($id);

        try {
            $this->unidadMedidaRepository->delete($unidadMedida);
        } catch (QueryException $e) {
            throw new HttpException(
                422,
                'No se puede eliminar la unidad de medida porque tiene registros asociados en el sistema.'
            );
        }
    }
}