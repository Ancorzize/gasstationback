<?php

namespace App\Modules\Gastos\Application\Interfaces;

use App\Models\Caja;
use App\Models\Gasto;
use App\Models\CategoriaGasto;
use App\Models\MovimientoCaja;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GastoRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?Gasto;

    public function create(array $data): Gasto;

    public function getCajaAbierta(): ?Caja;

    public function findCategoriaGastoById(int $id): ?CategoriaGasto;

    public function createMovimientoCaja(array $data): MovimientoCaja;

    public function getSaldoEfectivoCaja(int $cajaId): float;

    public function update(Gasto $gasto, array $data): Gasto;
}