<?php

namespace App\Modules\Gastos\Application\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Gasto;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Gastos\Application\DTOs\CreateGastoDTO;
use App\Modules\Gastos\Application\Interfaces\GastoRepositoryInterface;
use App\Modules\Compras\Application\Interfaces\CompraRepositoryInterface;
use App\Modules\Caja\Application\Interfaces\CajaRepositoryInterface;

class GastoService
{
    public function __construct(
        protected GastoRepositoryInterface $gastoRepository,
        protected CompraRepositoryInterface $compraRepository,
        protected CajaRepositoryInterface $cajaRepository
    ) {}

    public function paginate(array $filters = [], int $perPage = 10)
    {
        return $this->gastoRepository->paginate($filters, $perPage);
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
        return DB::transaction(function () use ($dto) {
            $categoria = $this->gastoRepository->findCategoriaGastoById($dto->categoria_gasto_id);

            if (!$categoria) {
                throw new HttpException(422, 'La categoría de gasto no existe.');
            }

            if (!(bool) $categoria->is_active) {
                throw new HttpException(422, 'La categoría de gasto está inactiva.');
            }

            $tipoCaja = $dto->medio_pago === 'efectivo' ? 'efectivo' : 'digital';

            $caja = $this->compraRepository->getCajaAbiertaPorTipo($tipoCaja);

            if (!$caja) {
                throw new HttpException(422, "No hay caja {$dto->medio_pago} abierta.");
            }

            $ingresos = $this->cajaRepository->sumMovimientosByTipo($caja->id, 'ingreso');
            $egresos = $this->cajaRepository->sumMovimientosByTipo($caja->id, 'egreso');

            $saldoActual = $ingresos - $egresos;

            if($dto->valor > $saldoActual)
            {
                throw new HttpException(422, 'No hay saldo suficiente en caja');
            }

            /*if ($dto->medio_pago === 'efectivo') {
                $saldoDisponible = $this->gastoRepository->getSaldoEfectivoCaja($caja->id);

                if ((float) $dto->valor > $saldoDisponible) {
                    throw new HttpException(422, 'No hay suficiente saldo en caja para registrar este gasto.');
                }
            }*/

            $gasto = $this->gastoRepository->create([
                'fecha_gasto' => $dto->fecha_gasto,
                'proveedor_id' => $dto->proveedor_id,
                'categoria_gasto_id' => $dto->categoria_gasto_id,
                'caja_id' => $caja->id,
                'user_id' => $dto->user_id,
                'medio_pago' => $dto->medio_pago,
                'valor' => $dto->valor,
                'descripcion' => $dto->descripcion,
                'estado' => 'registrado',
            ]);

            $this->gastoRepository->createMovimientoCaja([
                'caja_id' => $caja->id,
                'tipo_movimiento' => 'egreso',
                'categoria_movimiento' => 'gasto',
                'origen_modulo' => 'gastos',
                'origen_id' => $gasto->id,
                'medio_pago' => $dto->medio_pago,
                'monto' => $dto->valor,
                'descripcion' => 'Gasto: ' . $dto->descripcion,
                'user_id' => $dto->user_id,
                'fecha_movimiento' => now(),
            ]);

            return $this->findById($gasto->id);
        });
    }

    public function anular(int $id, string $motivoAnulacion, int $userId): Gasto
    {
        return DB::transaction(function () use ($id, $motivoAnulacion, $userId) {
            $gasto = $this->findById($id);

            if ($gasto->estado === 'anulado') {
                throw new HttpException(422, 'El gasto ya se encuentra anulado.');
            }

            $tipoCaja = $gasto->medio_pago === 'efectivo' ? 'efectivo' : 'digital';

            $caja = $this->compraRepository->getCajaAbiertaPorTipo($tipoCaja);

            if (!$caja) {
                throw new HttpException(422, "No hay caja {$tipoCaja} abierta para anular el gasto.");
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
}