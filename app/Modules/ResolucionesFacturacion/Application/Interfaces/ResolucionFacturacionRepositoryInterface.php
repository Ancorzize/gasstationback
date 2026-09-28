<?php

namespace App\Modules\ResolucionesFacturacion\Application\Interfaces;

use App\Models\ResolucionFacturacion;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ResolucionFacturacionRepositoryInterface
{
    public function findByTipoDocumento(string $tipoDocumento, ?int $empresaId = null): ?ResolucionFacturacion;

    public function getAll(array $filters = []): Collection;

    public function paginate(array $filters = [], int $perPage = 1000): LengthAwarePaginator;

    public function findById(int $id): ?ResolucionFacturacion;

    public function create(array $data): ResolucionFacturacion;

    public function update(ResolucionFacturacion $resolucion, array $data): ResolucionFacturacion;

    public function delete(ResolucionFacturacion $resolucion): void;
}
