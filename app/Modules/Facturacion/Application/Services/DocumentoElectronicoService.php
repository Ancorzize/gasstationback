<?php

namespace App\Modules\Facturacion\Application\Services;

use App\Models\DocumentoElectronico;
use App\Modules\Facturacion\Application\DTOs\CrearDocumentoElectronicoDTO;
use App\Modules\Facturacion\Application\DTOs\ActualizarDocumentoElectronicoDTO;
use App\Modules\Facturacion\Application\DTOs\RespuestaFacturacionDTO;
use App\Modules\Facturacion\Application\Interfaces\DocumentoElectronicoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Servicio de Aplicación para la gestión y trazabilidad de Documentos Electrónicos.
 */
class DocumentoElectronicoService
{
    public function __construct(
        protected DocumentoElectronicoRepositoryInterface $repository
    ) {}

    public function registrarEmision(CrearDocumentoElectronicoDTO $dto): DocumentoElectronico
    {
        return $this->repository->create($dto);
    }

    public function actualizarDesdeRespuesta(int $documentoId, RespuestaFacturacionDTO $respuesta): DocumentoElectronico
    {
        $dto = new ActualizarDocumentoElectronicoDTO(
            estado: $respuesta->exitoso ? 'emitido' : 'rechazado',
            identificadorExterno: $respuesta->identificadorExterno,
            cufe: $respuesta->cufe,
            numeroDocumento: $respuesta->numeroFactura,
            mensaje: $respuesta->mensaje,
            errores: $respuesta->errores,
            datosTecnicos: $respuesta->datosTecnicos
        );

        return $this->repository->update($documentoId, $dto);
    }

    public function obtenerPorVenta(int $ventaId): Collection
    {
        return $this->repository->findByVentaId($ventaId);
    }

    public function obtenerPorId(int $id): ?DocumentoElectronico
    {
        return $this->repository->findById($id);
    }

    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }
}
