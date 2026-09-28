<?php

namespace App\Modules\Facturacion\Infrastructure\Repositories;

use App\Models\DocumentoElectronico;
use App\Modules\Facturacion\Application\DTOs\CrearDocumentoElectronicoDTO;
use App\Modules\Facturacion\Application\DTOs\ActualizarDocumentoElectronicoDTO;
use App\Modules\Facturacion\Application\Interfaces\DocumentoElectronicoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DocumentoElectronicoRepository implements DocumentoElectronicoRepositoryInterface
{
    public function create(CrearDocumentoElectronicoDTO $dto): DocumentoElectronico
    {
        return DocumentoElectronico::create($dto->toArray());
    }

    public function update(int $id, ActualizarDocumentoElectronicoDTO $dto): DocumentoElectronico
    {
        $doc = DocumentoElectronico::findOrFail($id);
        $doc->update($dto->toArray());
        return $doc;
    }

    public function findById(int $id): ?DocumentoElectronico
    {
        return DocumentoElectronico::find($id);
    }

    public function findByVentaId(int $ventaId): Collection
    {
        return DocumentoElectronico::where('venta_id', $ventaId)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function findByCufe(string $cufe): ?DocumentoElectronico
    {
        return DocumentoElectronico::where('cufe', $cufe)->first();
    }

    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = DocumentoElectronico::with(['venta.cliente', 'configuracionFacturacion'])
            ->orderBy('id', 'desc');

        if (!empty($filters['fecha_inicial'])) {
            $query->whereDate('created_at', '>=', $filters['fecha_inicial']);
        }

        if (!empty($filters['fecha_final'])) {
            $query->whereDate('created_at', '<=', $filters['fecha_final']);
        }

        if (!empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('numero_documento', 'like', "%{$term}%")
                  ->orWhere('prefijo', 'like', "%{$term}%")
                  ->orWhere('cufe', 'like', "%{$term}%")
                  ->orWhere('identificador_externo', 'like', "%{$term}%")
                  ->orWhereHas('venta.cliente', function ($qc) use ($term) {
                      $qc->where('nombre', 'like', "%{$term}%")
                        ->orWhere('apellidos', 'like', "%{$term}%")
                        ->orWhere('documento', 'like', "%{$term}%");
                  });
            });
        }

        return $query->paginate($perPage);
    }
}
