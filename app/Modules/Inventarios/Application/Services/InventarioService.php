<?php

namespace App\Modules\Inventarios\Application\Services;

use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Inventarios\Application\Interfaces\InventarioRepositoryInterface;

class InventarioService
{
    public function __construct(
        protected InventarioRepositoryInterface $inventarioRepository
    ) {}

    public function paginate(array $filters = [], int $perPage = 10)
    {
        return $this->inventarioRepository->paginate($filters, $perPage);
    }

    public function getResumen(array $filters = [])
    {
        return $this->inventarioRepository->getResumen($filters);
    }

    public function getByBodega(int $bodegaId)
    {
        return $this->inventarioRepository->getByBodega($bodegaId);
    }

    public function getMiInventario($user)
    {
        if (!$user->bodega_id) {
            throw new HttpException(422, 'El usuario no tiene bodega asignada.');
        }

        return $this->inventarioRepository->getByBodegaAndUser($user->bodega_id);
    }
}