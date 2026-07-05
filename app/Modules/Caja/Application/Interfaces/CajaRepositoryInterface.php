<?php

namespace App\Modules\Caja\Application\Interfaces;

use App\Models\Caja;
use App\Models\MovimientoCaja;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CajaRepositoryInterface
{
    public function getCajasAbiertas(): Collection;

    public function findById(int $id): ?Caja;

    public function createCaja(array $data): Caja;

    public function updateCaja(Caja $caja, array $data): Caja;

    public function createMovimiento(array $data): MovimientoCaja;

    public function paginateMovimientos(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function getMovimientosByCaja(int $cajaId): Collection;

    public function sumMovimientosByTipo(int $cajaId, string $tipoMovimiento): float;

    public function paginateHistorico(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function getCajaAbiertaByTipoAndDestino(string $tipoCaja, int $destinoRecaudoId): ?Caja;

    public function existsCajaAbiertaByTipoAndDestino(string $tipoCaja, int $destinoRecaudoId): bool;
}