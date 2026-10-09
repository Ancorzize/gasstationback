<?php

namespace App\Modules\Gastos\Application\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Gasto;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Gastos\Application\DTOs\CreateGastoDTO;
use App\Modules\Gastos\Application\Interfaces\GastoRepositoryInterface;
use App\Modules\Caja\Application\Interfaces\CajaRepositoryInterface;
use App\Modules\Gastos\Infrastructure\Mappers\GastoMapper;
use App\Modules\Facturacion\Application\DTOs\SolicitudDocumentoSoporteDTO;
use App\Modules\Facturacion\Application\Services\DocumentoSoporteService;

class GastoService
{
    public function __construct(
        protected GastoRepositoryInterface $gastoRepository,
        protected CajaRepositoryInterface $cajaRepository,
        protected ?DocumentoSoporteService $documentoSoporteService = null
    ) {}

    public function getAll(array $filters = [])
    {
        return $this->gastoRepository->getAll($filters);
    }

    public function findById(int $id): Gasto
    {
        $gasto = $this->gastoRepository->findById($id);

        if (!$gasto) {
            throw new HttpException(404, 'Gasto no encontrado.');
        }

        return $gasto;
    }

    public function create(CreateGastoDTO $dto): Gasto
    {
        if ($dto->documento_soporte_electronico && empty($dto->proveedor_id)) {
            throw new HttpException(422, 'El proveedor es obligatorio cuando se requiere Documento Soporte Electrónico.');
        }

        $gasto = DB::transaction(function () use ($dto) {
            $categoria = $this->gastoRepository->findCategoriaGastoById($dto->categoria_gasto_id);

            if (!$categoria) {
                throw new HttpException(422, 'La categoría de gasto no existe.');
            }

            if (!(bool) $categoria->is_active) {
                throw new HttpException(422, 'La categoría de gasto está inactiva.');
            }

            $caja = $this->cajaRepository->findById($dto->caja_id);

            if (!$caja) {
                throw new HttpException(
                    422,
                    'La caja seleccionada no esta abierta'
                );
            }

            $ingresos = $this->cajaRepository->sumMovimientosByTipo($caja->id, 'ingreso');
            $egresos = $this->cajaRepository->sumMovimientosByTipo($caja->id, 'egreso');

            $saldoActual = $ingresos - $egresos;

            if ($dto->valor > $saldoActual) {
                throw new HttpException(422, 'No hay saldo suficiente en caja');
            }

            $estadoDs = $dto->documento_soporte_electronico
                ? ($dto->estado_documento_soporte ?? 'pendiente')
                : 'no_requerido';

            $gasto = $this->gastoRepository->create([
                'fecha_gasto' => $dto->fecha_gasto,
                'proveedor_id' => $dto->proveedor_id,
                'categoria_gasto_id' => $dto->categoria_gasto_id,
                'caja_id' => $caja->id,
                'user_id' => $dto->user_id,
                'medio_pago' => $dto->tipo_caja,
                'valor' => $dto->valor,
                'descripcion' => $dto->descripcion,
                'estado' => 'registrado',
                'documento_soporte_electronico' => $dto->documento_soporte_electronico,
                'estado_documento_soporte' => $estadoDs,
            ]);

            $this->gastoRepository->createMovimientoCaja([
                'caja_id' => $caja->id,
                'tipo_movimiento' => 'egreso',
                'categoria_movimiento' => 'gasto',
                'origen_modulo' => 'gastos',
                'origen_id' => $gasto->id,
                'medio_pago' => $dto->tipo_caja,
                'monto' => $dto->valor,
                'descripcion' => 'Gasto: ' . $dto->descripcion,
                'user_id' => $dto->user_id,
                'fecha_movimiento' => now(),
            ]);

            return $this->findById($gasto->id);
        });

        // Automatización de Documento Soporte Electrónico (fuera de la transacción SQL)
        if ((bool) $gasto->documento_soporte_electronico) {
            $dsService = $this->documentoSoporteService ?? app(DocumentoSoporteService::class);
            if ($dsService) {
                $dsService->procesarEmisionAutomatica('gasto', $gasto->id);
                $gasto = $this->findById($gasto->id);
            }
        }

        return $gasto;
    }

    /**
     * Construir SolicitudDocumentoSoporteDTO a partir de un Gasto.
     */
    public function obtenerSolicitudDocumentoSoporte(int $gastoId): SolicitudDocumentoSoporteDTO
    {
        $gasto = $this->findById($gastoId);
        $gasto->load(['proveedor']);

        if (!$gasto->proveedor) {
            throw new HttpException(422, 'El gasto no posee un proveedor válido asociado para emitir Documento Soporte Electrónico.');
        }

        return GastoMapper::toSolicitudDocumentoSoporteDTO($gasto);
    }

    public function anular(int $id, string $motivoAnulacion, int $userId): Gasto
    {
        return DB::transaction(function () use ($id, $motivoAnulacion, $userId) {
            $gasto = $this->findById($id);

            if ($gasto->estado === 'anulado') {
                throw new HttpException(422, 'El gasto ya se encuentra anulado.');
            }

            $tipoCaja = $gasto->medio_pago === 'efectivo' || $gasto->medio_pago === 'consignacion' ? 'efectivo' : 'digital';

            $caja = $this->cajaRepository
                ->getCajaAbiertaByTipoAndDestino(
                    $tipoCaja,
                    $gasto->caja->destino_recaudo_id
                );

            if (!$caja) {
                throw new HttpException(
                    422,
                    'La caja donde se registró el gasto no se encuentra abierta.'
                );
            }

            $gastoActualizado = $this->gastoRepository->update($gasto, [
                'estado' => 'anulado',
                'motivo_anulacion' => $motivoAnulacion,
                'user_anulacion_id' => $userId,
                'fecha_anulacion' => now(),
            ]);

            $this->gastoRepository->createMovimientoCaja([
                'caja_id' => $caja->id,
                'tipo_movimiento' => 'ingreso',
                'categoria_movimiento' => 'anulacion_gasto',
                'origen_modulo' => 'gastos',
                'origen_id' => $gasto->id,
                'medio_pago' => $gasto->medio_pago,
                'monto' => $gasto->valor,
                'descripcion' => 'Anulación gasto #' . $gasto->id . ': ' . $motivoAnulacion,
                'user_id' => $userId,
                'fecha_movimiento' => now(),
            ]);

            return $this->findById($gastoActualizado->id);
        });
    }

    /**
     * Actualizar el estado de emisión del Documento Soporte Electrónico en el Gasto.
     */
    public function actualizarEstadoDocumentoSoporte(int $gastoId, string $estado): void
    {
        $gasto = $this->findById($gastoId);
        $this->gastoRepository->update($gasto, [
            'estado_documento_soporte' => $estado,
        ]);
    }
}