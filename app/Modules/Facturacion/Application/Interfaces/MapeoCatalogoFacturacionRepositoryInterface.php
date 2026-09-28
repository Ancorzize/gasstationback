<?php

namespace App\Modules\Facturacion\Application\Interfaces;

use App\Models\MapeoCatalogoFacturacion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MapeoCatalogoFacturacionRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 1000): LengthAwarePaginator;
    public function findById(int $id): ?MapeoCatalogoFacturacion;
    public function findDuplicate(string $proveedor, string $categoria, string $codigoInterno, ?int $excludeId = null): ?MapeoCatalogoFacturacion;
    public function create(array $data): MapeoCatalogoFacturacion;
    public function update(MapeoCatalogoFacturacion $mapeo, array $data): MapeoCatalogoFacturacion;
    public function delete(MapeoCatalogoFacturacion $mapeo): void;
}
