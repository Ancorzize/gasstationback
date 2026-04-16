<?php

namespace App\Modules\Gastos\Application\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Gasto;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Gastos\Application\DTOs\CreateGastoDTO;
use App\Modules\Gastos\Application\Interfaces\GastoRepositoryInterface;

class GastoService
{
    public function __construct(
        protected GastoRepositoryInterface $gastoRepository
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

            $caja = $this->gastoRepository->getCajaAbierta();

            if (!$caja) {
                throw new HttpException(422, 'No existe una caja abierta.');
            }

            if ($dto->medio_pago === 'efectivo') {
                $saldoDisponible = $this->gastoRepository->getSaldoEfectivoCaja($caja->id);

                if ((float) $dto->valor > $saldoDisponible) {
                    throw new HttpException(422, 'No hay suficiente saldo en caja para registrar este gasto.');
                }
            }

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
}