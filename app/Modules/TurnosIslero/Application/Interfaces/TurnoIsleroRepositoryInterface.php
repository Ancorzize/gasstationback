<?php

namespace App\Modules\TurnosIslero\Application\Interfaces;

use App\Models\TurnoIslero;
use App\Models\Caja;
use App\Models\MovimientoCaja;
use App\Models\LecturaManguera;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TurnoIsleroRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?TurnoIslero;

    public function getTurnoAbiertoByUser(int $userId): ?TurnoIslero;

    public function existsTurnoAbiertoByUser(int $userId): bool;

    public function createTurno(array $data): TurnoIslero;

    public function updateTurno(TurnoIslero $turno, array $data): TurnoIslero;

    public function getManguerasActivasByEstacion(int $estacionId): Collection;

    public function getUltimaLecturaCerradaByManguera(int $mangueraId): ?LecturaManguera;

    public function createLectura(array $data): LecturaManguera;

    public function findLecturaByTurnoAndManguera(int $turnoId, int $mangueraId): ?LecturaManguera;

    public function updateLectura(LecturaManguera $lectura, array $data): LecturaManguera;

    public function sumVentasCreditoByTurno(int $turnoId): float;

    public function sumAbonosByTurno(int $turnoId): float;

    public function getPrecioVigenteProducto(int $productoId): ?float;

    public function getManguerasDisponiblesByEstacion(int $estacionId): Collection;

    public function getManguerasByIds(array $ids): Collection;

    public function asignarMangueras(TurnoIslero $turno, array $mangueraIds): void;

    public function getManguerasOcupadasEnTurnosAbiertos(int $estacionId): Collection;

    public function sumVentasCombustibleByTurno(int $turnoId): float;

    public function sumVentasLubricantesByTurno(int $turnoId): float;

    public function sumPagosVentasByTurnoAndMetodo(int $turnoId, string $metodoPago): float;

    public function sumAbonosByTurnoAndMetodo(int $turnoId, string $metodoPago): float;

    public function sumGalonesCombustibleByTurnoAndManguera(int $turnoId, int $mangueraId): float;

    public function sumTotalCombustibleByTurnoAndManguera(int $turnoId, int $mangueraId): float;

    public function getVentasLubricantesDetalleByTurno(int $turnoId): Collection;

    public function getAbonosDetalleByTurno(int $turnoId): Collection;

    public function getResumenPagosPorDestino(int $turnoId): Collection;

    public function getDestinosRecaudoConVentas(int $turnoId): Collection;
        
    public function getVentasDelTurnoConDestino(int $turnoId): Collection;

    public function getCajaAbiertaByTipoAndDestino( string $tipoCaja, int $destinoRecaudoId): ?Caja;

    public function createMovimientoCaja(array $data): MovimientoCaja;

    public function getResumenDestinosTurno( int $turnoId): Collection;

    public function getDestinosConCajaAbierta();
}