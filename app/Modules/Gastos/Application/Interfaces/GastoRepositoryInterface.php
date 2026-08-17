<?php

namespace App\Modules\Gastos\Application\Interfaces;

use App\Models\Caja;
use App\Models\Gasto;
use App\Models\CategoriaGasto;
use App\Models\MovimientoCaja;
use Illuminate\Support\Collection;

interface GastoRepositoryInterface
{
    public function getAll(array $filters = []): Collection;

    public function findById(int $id): ?Gasto;

    public function create(array $data): Gasto;

    public function findCategoriaGastoById(int $id): ?CategoriaGasto;

    public function createMovimientoCaja(array $data): MovimientoCaja;

    public function getSaldoEfectivoCaja(int $cajaId): float;

    public function update(Gasto $gasto, array $data): Gasto;
}