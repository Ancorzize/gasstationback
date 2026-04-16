<?php

namespace App\Modules\Caja\Application\Services;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Caja\Application\DTOs\AperturaCajaDTO;
use App\Modules\Caja\Application\DTOs\CierreCajaDTO;
use App\Modules\Caja\Application\Interfaces\CajaRepositoryInterface;

class CajaService
{
    public function __construct(
        protected CajaRepositoryInterface $cajaRepository
    ) {}

    public function getCajaActual()
    {
        return $this->cajaRepository->getCajaAbierta();
    }

    public function abrirCaja(AperturaCajaDTO $dto)
    {
        return DB::transaction(function () use ($dto) {
            $cajaAbierta = $this->cajaRepository->getCajaAbierta();

            if ($cajaAbierta) {
                throw new HttpException(422, 'Ya existe una caja abierta.');
            }

            $caja = $this->cajaRepository->createCaja([
                'fecha_apertura' => now(),
                'monto_apertura' => $dto->monto_apertura,
                'monto_cierre_sistema' => 0,
                'monto_cierre_real' => null,
                'diferencia_cierre' => 0,
                'estado' => 'abierta',
                'user_apertura_id' => $dto->user_id,
                'user_cierre_id' => null,
                'observacion_apertura' => $dto->observacion_apertura,
                'observacion_cierre' => null,
            ]);

            $this->cajaRepository->createMovimiento([
                'caja_id' => $caja->id,
                'tipo_movimiento' => 'ingreso',
                'categoria_movimiento' => 'apertura',
                'origen_modulo' => 'caja',
                'origen_id' => $caja->id,
                'medio_pago' => 'efectivo',
                'monto' => $dto->monto_apertura,
                'descripcion' => 'Apertura de caja',
                'user_id' => $dto->user_id,
                'fecha_movimiento' => now(),
            ]);

            return $this->buildCajaActualResponse($caja->id);
        });
    }

    public function cerrarCaja(CierreCajaDTO $dto)
    {
        return DB::transaction(function () use ($dto) {
            $caja = $this->cajaRepository->getCajaAbierta();

            if (!$caja) {
                throw new HttpException(422, 'No existe una caja abierta.');
            }

            $resumen = $this->getResumenCaja($caja->id);

            $montoCierreSistema = $resumen['saldo_efectivo_sistema'];
            $diferencia = (float) $dto->monto_cierre_real - (float) $montoCierreSistema;

            $this->cajaRepository->updateCaja($caja, [
                'fecha_cierre' => now(),
                'monto_cierre_sistema' => $montoCierreSistema,
                'monto_cierre_real' => $dto->monto_cierre_real,
                'diferencia_cierre' => $diferencia,
                'estado' => 'cerrada',
                'user_cierre_id' => $dto->user_id,
                'observacion_cierre' => $dto->observacion_cierre,
            ]);

            return $this->cajaRepository->findById($caja->id);
        });
    }

    public function paginateMovimientos(array $filters = [], int $perPage = 10)
    {
        return $this->cajaRepository->paginateMovimientos($filters, $perPage);
    }

    public function getResumenCajaActual(): array
    {
        $caja = $this->cajaRepository->getCajaAbierta();

        if (!$caja) {
            throw new HttpException(422, 'No existe una caja abierta.');
        }

        return $this->getResumenCaja($caja->id);
    }

    private function getResumenCaja(int $cajaId): array
    {
        $ingresosEfectivo = $this->cajaRepository->sumMovimientosByTipoAndMedio($cajaId, 'ingreso', 'efectivo');
        $egresosEfectivo = $this->cajaRepository->sumMovimientosByTipoAndMedio($cajaId, 'egreso', 'efectivo');

        $ingresosElectronico = $this->cajaRepository->sumMovimientosByTipoAndMedio($cajaId, 'ingreso', 'electronico');
        $egresosElectronico = $this->cajaRepository->sumMovimientosByTipoAndMedio($cajaId, 'egreso', 'electronico');

        return [
            'caja_id' => $cajaId,
            'ingresos_efectivo' => $ingresosEfectivo,
            'egresos_efectivo' => $egresosEfectivo,
            'saldo_efectivo_sistema' => $ingresosEfectivo - $egresosEfectivo,
            'ingresos_electronico' => $ingresosElectronico,
            'egresos_electronico' => $egresosElectronico,
            'saldo_electronico_sistema' => $ingresosElectronico - $egresosElectronico,
        ];
    }

    private function buildCajaActualResponse(int $cajaId): array
    {
        $caja = $this->cajaRepository->findById($cajaId);
        $resumen = $this->getResumenCaja($cajaId);

        return [
            'caja' => $caja,
            'resumen' => $resumen,
        ];
    }
}