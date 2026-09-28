<?php

namespace App\Modules\ResolucionesFacturacion\Application\Services;

use App\Models\ResolucionFacturacion;
use App\Modules\ResolucionesFacturacion\Application\DTOs\CreateResolucionFacturacionDTO;
use App\Modules\ResolucionesFacturacion\Application\DTOs\UpdateResolucionFacturacionDTO;
use App\Modules\ResolucionesFacturacion\Application\Interfaces\ResolucionFacturacionRepositoryInterface;
use App\Modules\ConfiguracionEmpresa\Application\Interfaces\ConfiguracionEmpresaRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ResolucionFacturacionService
{
    public function __construct(
        protected ResolucionFacturacionRepositoryInterface $repository,
        protected ?ConfiguracionEmpresaRepositoryInterface $empresaRepository = null
    ) {}

    public function getResolucionByTipoDocumento(string $tipoDocumento, ?int $empresaId = null): ?ResolucionFacturacion
    {
        $resolucion = $this->repository->findByTipoDocumento($tipoDocumento, $empresaId);

        // Fallback de compatibilidad: Si se consulta factura y no existe registro aún en resoluciones_facturacion,
        // construir uno dinámico/migrado desde configuracion_empresa.
        if (!$resolucion && ($tipoDocumento === 'factura' || $tipoDocumento === 'factura_electronica')) {
            $empresa = $this->empresaRepository?->first();
            if ($empresa) {
                $numResolucion = $empresa->numero_resolucion ?: '18764074347312';
                $prefijo = $empresa->prefijo_factura ?: 'SETP';

                $resolucion = $this->repository->create([
                    'configuracion_empresa_id' => $empresa->id,
                    'tipo_documento' => 'factura',
                    'prefijo' => $prefijo,
                    'numero_resolucion' => $numResolucion,
                    'fecha_resolucion' => $empresa->fecha_resolucion,
                    'rango_desde' => $empresa->rango_desde ?? 1,
                    'rango_hasta' => $empresa->rango_hasta ?? 5000,
                    'consecutivo_actual' => 1,
                    'fecha_vencimiento' => $empresa->fecha_vencimiento,
                    'proveedor' => 'matias',
                    'ambiente' => 'sandbox',
                    'is_active' => true,
                ]);
            }
        }

        return $resolucion;
    }

    public function paginate(array $filters = [], int $perPage = 1000)
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function findById(int $id): ResolucionFacturacion
    {
        $resolucion = $this->repository->findById($id);

        if (!$resolucion) {
            throw new HttpException(404, 'Resolución de facturación no encontrada.');
        }

        return $resolucion;
    }

    public function create(CreateResolucionFacturacionDTO $dto): ResolucionFacturacion
    {
        return $this->repository->create([
            'configuracion_empresa_id' => $dto->configuracionEmpresaId,
            'tipo_documento' => strtolower(trim($dto->tipoDocumento)),
            'prefijo' => $dto->prefijo ? trim($dto->prefijo) : null,
            'numero_resolucion' => trim($dto->numeroResolucion),
            'fecha_resolucion' => $dto->fechaResolucion,
            'rango_desde' => $dto->rangoDesde,
            'rango_hasta' => $dto->rangoHasta,
            'consecutivo_actual' => $dto->consecutivoActual,
            'fecha_vencimiento' => $dto->fechaVencimiento,
            'clave_tecnica' => $dto->claveTecnica ? trim($dto->claveTecnica) : null,
            'proveedor' => $dto->proveedor ? strtolower(trim($dto->proveedor)) : 'matias',
            'ambiente' => $dto->ambiente ? strtolower(trim($dto->ambiente)) : 'sandbox',
            'is_active' => $dto->isActive,
        ]);
    }

    public function update(int $id, UpdateResolucionFacturacionDTO $dto): ResolucionFacturacion
    {
        $resolucion = $this->findById($id);

        return $this->repository->update($resolucion, [
            'configuracion_empresa_id' => $dto->configuracionEmpresaId,
            'tipo_documento' => strtolower(trim($dto->tipoDocumento)),
            'prefijo' => $dto->prefijo ? trim($dto->prefijo) : null,
            'numero_resolucion' => trim($dto->numeroResolucion),
            'fecha_resolucion' => $dto->fechaResolucion,
            'rango_desde' => $dto->rangoDesde,
            'rango_hasta' => $dto->rangoHasta,
            'consecutivo_actual' => $dto->consecutivoActual,
            'fecha_vencimiento' => $dto->fechaVencimiento,
            'clave_tecnica' => $dto->claveTecnica ? trim($dto->claveTecnica) : null,
            'proveedor' => $dto->proveedor ? strtolower(trim($dto->proveedor)) : 'matias',
            'ambiente' => $dto->ambiente ? strtolower(trim($dto->ambiente)) : 'sandbox',
            'is_active' => $dto->isActive,
        ]);
    }

    public function delete(int $id): void
    {
        $resolucion = $this->findById($id);
        $this->repository->delete($resolucion);
    }
}
