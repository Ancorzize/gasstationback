<?php

namespace App\Modules\ResolucionesFacturacion\Application\Services;

use App\Models\ResolucionFacturacion;
use App\Modules\ResolucionesFacturacion\Application\DTOs\CreateResolucionFacturacionDTO;
use App\Modules\ResolucionesFacturacion\Application\DTOs\UpdateResolucionFacturacionDTO;
use App\Modules\ResolucionesFacturacion\Application\Interfaces\ResolucionFacturacionRepositoryInterface;
use App\Modules\ConfiguracionEmpresa\Application\Interfaces\ConfiguracionEmpresaRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ResolucionFacturacionService
{
    public function __construct(
        protected ResolucionFacturacionRepositoryInterface $repository,
        protected ?ConfiguracionEmpresaRepositoryInterface $empresaRepository = null
    ) {}

    /**
     * Reservar e incrementar atómicamente el siguiente consecutivo de Documento Soporte Electrónico.
     */
    public function reservarConsecutivoDocumentoSoporte(?int $empresaId = null): array
    {
        return DB::transaction(function () use ($empresaId) {
            $resolucion = $this->repository->findAndLockByTipoDocumento('documento_soporte', $empresaId);

            if (!$resolucion) {
                throw new HttpException(422, 'No existe una resolución de facturación activa para Documento Soporte.');
            }

            $consecutivoReservado = (int) $resolucion->consecutivo_actual;

            if ($consecutivoReservado < (int) $resolucion->rango_desde) {
                $consecutivoReservado = (int) $resolucion->rango_desde;
            }

            if ($consecutivoReservado > (int) $resolucion->rango_hasta) {
                throw new HttpException(422, 'El rango numérico de la resolución para Documento Soporte se encuentra agotado.');
            }

            $this->repository->update($resolucion, [
                'consecutivo_actual' => $consecutivoReservado + 1,
            ]);

            return [
                'resolucion_id' => $resolucion->id,
                'prefijo' => $resolucion->prefijo,
                'numero_resolucion' => $resolucion->numero_resolucion,
                'consecutivo' => $consecutivoReservado,
                'folio' => (string) $consecutivoReservado,
            ];
        });
    }

    /**
     * Reservar e incrementar atómicamente el siguiente consecutivo de Nota Crédito Electrónica.
     */
    public function reservarConsecutivoNotaCredito(?int $empresaId = null): array
    {
        return DB::transaction(function () use ($empresaId) {
            $resolucion = $this->repository->findAndLockByTipoDocumento('nota_credito', $empresaId);

            if (!$resolucion) {
                $resolucion = $this->repository->findAndLockByTipoDocumento('factura', $empresaId);
                
                if (!$resolucion) {
                    $resolucion = $this->repository->findAndLockByTipoDocumento('factura_electronica', $empresaId);
                }

                if (!$resolucion) {
                    throw new HttpException(422, 'No existe una resolución de facturación activa para Nota Crédito ni Factura.');
                }

                $prefijoNC = 'NC';
                $consecutivoReservado = (int) $resolucion->consecutivo_actual;
                if ($consecutivoReservado < (int) $resolucion->rango_desde) {
                    $consecutivoReservado = (int) $resolucion->rango_desde;
                }

                return [
                    'resolucion_id' => $resolucion->id,
                    'prefijo' => $prefijoNC,
                    'numero_resolucion' => $resolucion->numero_resolucion,
                    'consecutivo' => $consecutivoReservado,
                    'folio' => (string) $consecutivoReservado,
                ];
            }

            $consecutivoReservado = (int) $resolucion->consecutivo_actual;

            if ($consecutivoReservado < (int) $resolucion->rango_desde) {
                $consecutivoReservado = (int) $resolucion->rango_desde;
            }

            if ($consecutivoReservado > (int) $resolucion->rango_hasta) {
                throw new HttpException(422, 'El rango numérico de la resolución para Nota Crédito se encuentra agotado.');
            }

            $this->repository->update($resolucion, [
                'consecutivo_actual' => $consecutivoReservado + 1,
            ]);

            return [
                'resolucion_id' => $resolucion->id,
                'prefijo' => $resolucion->prefijo ?: 'NC',
                'numero_resolucion' => $resolucion->numero_resolucion,
                'consecutivo' => $consecutivoReservado,
                'folio' => (string) $consecutivoReservado,
            ];
        });
    }

    /**
     * Reservar e incrementar atómicamente el siguiente consecutivo de Nota Débito Electrónica.
     */
    public function reservarConsecutivoNotaDebito(?int $empresaId = null): array
    {
        return DB::transaction(function () use ($empresaId) {
            $resolucion = $this->repository->findAndLockByTipoDocumento('nota_debito', $empresaId);

            if (!$resolucion) {
                $resolucion = $this->repository->findAndLockByTipoDocumento('factura', $empresaId);
                
                if (!$resolucion) {
                    $resolucion = $this->repository->findAndLockByTipoDocumento('factura_electronica', $empresaId);
                }

                if (!$resolucion) {
                    throw new HttpException(422, 'No existe una resolución de facturación activa para Nota Débito ni Factura.');
                }

                $prefijoND = 'ND';
                $consecutivoReservado = (int) $resolucion->consecutivo_actual;
                if ($consecutivoReservado < (int) $resolucion->rango_desde) {
                    $consecutivoReservado = (int) $resolucion->rango_desde;
                }

                return [
                    'resolucion_id' => $resolucion->id,
                    'prefijo' => $prefijoND,
                    'numero_resolucion' => $resolucion->numero_resolucion,
                    'consecutivo' => $consecutivoReservado,
                    'folio' => (string) $consecutivoReservado,
                ];
            }

            $consecutivoReservado = (int) $resolucion->consecutivo_actual;

            if ($consecutivoReservado < (int) $resolucion->rango_desde) {
                $consecutivoReservado = (int) $resolucion->rango_desde;
            }

            if ($consecutivoReservado > (int) $resolucion->rango_hasta) {
                throw new HttpException(422, 'El rango numérico de la resolución para Nota Débito se encuentra agotado.');
            }

            $this->repository->update($resolucion, [
                'consecutivo_actual' => $consecutivoReservado + 1,
            ]);

            return [
                'resolucion_id' => $resolucion->id,
                'prefijo' => $resolucion->prefijo ?: 'ND',
                'numero_resolucion' => $resolucion->numero_resolucion,
                'consecutivo' => $consecutivoReservado,
                'folio' => (string) $consecutivoReservado,
            ];
        });
    }

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
