<?php

namespace App\Modules\Cartera\Application\Interfaces;

use App\Models\Caja;
use App\Models\Cliente;
use App\Models\AbonoCartera;
use App\Models\MovimientoCaja;
use App\Models\MovimientoCartera;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\TurnoIslero;
interface CarteraRepositoryInterface
{
    public function findClienteById(int $id): ?Cliente;

    public function updateCliente(Cliente $cliente, array $data): Cliente;

    public function createAbono(array $data): AbonoCartera;

    public function createMovimientoCartera(array $data): MovimientoCartera;

    public function paginateMovimientos(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function getMovimientosByCliente(int $clienteId): Collection;

    public function getCajaAbiertaByTipo(string $tipoCaja): ?Caja;

    public function createMovimientoCaja(array $data): MovimientoCaja;

    public function resumen(): array;

    public function getTurnoAbiertoByUser(int $userId): ?TurnoIslero;
}