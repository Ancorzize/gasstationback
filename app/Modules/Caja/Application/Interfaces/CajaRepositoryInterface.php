<?php

namespace App\Modules\Caja\Application\Interfaces;

use App\Models\Caja;
use App\Models\MovimientoCaja;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CajaRepositoryInterface
{
    public function getCajaAbierta(): ?Caja;

    public function findById(int $id): ?Caja;

    public function createCaja(array $data): Caja;

    public function updateCaja(Caja $caja, array $data): Caja;

    public function createMovimiento(array $data): MovimientoCaja;

    public function paginateMovimientos(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function getMovimientosByCaja(int $cajaId): Collection;

    public function sumMovimientosByTipoAndMedio(int $cajaId, string $tipoMovimiento, string $medioPago): float;
}