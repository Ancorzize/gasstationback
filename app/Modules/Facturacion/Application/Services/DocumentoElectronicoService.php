<?php

namespace App\Modules\Facturacion\Application\Services;

use App\Models\DocumentoElectronico;
use App\Modules\Facturacion\Application\DTOs\CrearDocumentoElectronicoDTO;
use App\Modules\Facturacion\Application\DTOs\ActualizarDocumentoElectronicoDTO;
use App\Modules\Facturacion\Application\DTOs\RespuestaFacturacionDTO;
use App\Modules\Facturacion\Application\DTOs\RespuestaDocumentoSoporteDTO;
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

    /**
     * Actualizar estado y trazabilidad de un Documento Soporte Electrónico desde RespuestaDocumentoSoporteDTO.
     */
    public function actualizarDesdeRespuestaDS(int $documentoId, RespuestaDocumentoSoporteDTO $respuesta): DocumentoElectronico
    {
        $doc = $this->repository->findById($documentoId);
        $datosTecnicosExistentes = is_array($doc?->datos_tecnicos) ? $doc->datos_tecnicos : [];
        $datosTecnicosActualizados = array_merge($datosTecnicosExistentes, [
            'respuesta_matias' => $respuesta->datosTecnicos,
        ]);

        $dto = new ActualizarDocumentoElectronicoDTO(
            estado: $respuesta->exitoso ? ($respuesta->estado ?: 'emitido') : 'rechazado',
            identificadorExterno: $respuesta->identificadorExterno,
            cufe: $respuesta->cude,
            numeroDocumento: $respuesta->numeroDocumento,
            mensaje: $respuesta->mensaje,
            errores: $respuesta->errores,
            datosTecnicos: $datosTecnicosActualizados
        );

        return $this->repository->update($documentoId, $dto);
    }

    /**
     * Transición atómica para exclusión concurrente en transmisión.
     */
    public function marcarComoEnviando(int $documentoId): bool
    {
        return $this->repository->marcarComoEnviando($documentoId);
    }

    /**
     * Restablecer estado a pendiente en caso de fallo técnico previo a respuesta.
     */
    public function restaurarEstadoPendiente(int $documentoId): bool
    {
        return $this->repository->restaurarEstadoPendiente($documentoId);
    }

    public function obtenerPorVenta(int $ventaId): Collection
    {
        return $this->repository->findByVentaId($ventaId);
    }

    public function obtenerPorCompra(int $compraId): Collection
    {
        return $this->repository->findByCompraId($compraId);
    }

    public function obtenerPorGasto(int $gastoId): Collection
    {
        return $this->repository->findByGastoId($gastoId);
    }

    public function obtenerPorPayrollSettlement(int $settlementId): Collection
    {
        return $this->repository->findByPayrollSettlementId($settlementId);
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
