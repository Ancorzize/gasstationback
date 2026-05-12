<?php

namespace App\Modules\PreciosCombustible\Application\Services;

use App\Models\PrecioCombustible;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\PreciosCombustible\Application\DTOs\CreatePrecioCombustibleDTO;
use App\Modules\PreciosCombustible\Application\Interfaces\PrecioCombustibleRepositoryInterface;

class PrecioCombustibleService
{
    public function __construct(
        protected PrecioCombustibleRepositoryInterface $precioRepository
    ) {}

    public function paginate(array $filters = [], int $perPage = 10)
    {
        return $this->precioRepository->paginate($filters, $perPage);
    }

    public function findById(int $id): PrecioCombustible
    {
        $precio = $this->precioRepository->findById($id);

        if (!$precio) {
            throw new HttpException(404, 'Precio de combustible no encontrado.');
        }

        return $precio;
    }

    public function create(CreatePrecioCombustibleDTO $dto): PrecioCombustible
    {
        return DB::transaction(function () use ($dto) {
            $this->precioRepository->cerrarPreciosActivosProducto($dto->producto_id);

            return $this->precioRepository->create([
                'producto_id' => $dto->producto_id,
                'precio' => $dto->precio,
                'fecha_inicio' => $dto->fecha_inicio ?: now(),
                'fecha_fin' => null,
                'is_active' => true,
            ]);
        });
    }

    public function changeStatus(int $id, bool $isActive): PrecioCombustible
    {
        return DB::transaction(function () use ($id, $isActive) {
            $precio = $this->findById($id);

            if ($isActive) {
                $this->precioRepository->cerrarPreciosActivosProducto($precio->producto_id);
            }

            return $this->precioRepository->changeStatus($precio, $isActive);
        });
    }
}