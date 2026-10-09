<?php

namespace App\Modules\Facturacion\Application\Interfaces;

use App\Models\DocumentoElectronico;
use App\Modules\Facturacion\Application\DTOs\CrearDocumentoElectronicoDTO;
use App\Modules\Facturacion\Application\DTOs\ActualizarDocumentoElectronicoDTO;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DocumentoElectronicoRepositoryInterface
{
    public function create(CrearDocumentoElectronicoDTO $dto): DocumentoElectronico;

    public function update(int $id, ActualizarDocumentoElectronicoDTO $dto): DocumentoElectronico;

    public function findById(int $id): ?DocumentoElectronico;

    public function findByVentaId(int $ventaId): Collection;

    public function findByCompraId(int $compraId): Collection;

    public function findByGastoId(int $gastoId): Collection;

    public function findByPayrollSettlementId(int $settlementId): Collection;

    public function findByCufe(string $cufe): ?DocumentoElectronico;

    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function marcarComoEnviando(int $documentoId): bool;

    public function restaurarEstadoPendiente(int $documentoId): bool;
}
