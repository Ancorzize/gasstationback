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
        return $this->cajaRepository->getCajasAbiertas();
    }

    public function abrirCaja(AperturaCajaDTO $dto)
    {
        return DB::transaction(function () use ($dto) {
            if ($this->cajaRepository->existsCajaAbierta()) {
                throw new HttpException(422, 'Ya existen cajas abiertas.');
            }

            $cajaEfectivo = $this->crearCajaPorTipo(
                tipoCaja: 'efectivo',
                montoApertura: $dto->monto_apertura_efectivo,
                medioPago: 'efectivo',
                dto: $dto
            );

            $cajaDigital = $this->crearCajaPorTipo(
                tipoCaja: 'digital',
                montoApertura: $dto->monto_apertura_digital,
                medioPago: 'digital',
                dto: $dto
            );

            return [
                'cajas' => [
                    'efectivo' => $cajaEfectivo,
                    'digital' => $cajaDigital,
                ],
                'resumen' => $this->getResumenCajaActual(),
            ];
        });
    }

    private function crearCajaPorTipo(string $tipoCaja, float $montoApertura, string $medioPago, AperturaCajaDTO $dto)
    {
        $caja = $this->cajaRepository->createCaja([
            'tipo_caja' => $tipoCaja,
            'fecha_apertura' => now(),
            'monto_apertura' => $montoApertura,
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
            'medio_pago' => $medioPago,
            'monto' => $montoApertura,
            'descripcion' => 'Apertura de caja ' . $tipoCaja,
            'user_id' => $dto->user_id,
            'fecha_movimiento' => now(),
        ]);

        return $this->cajaRepository->findById($caja->id);
    }

    public function cerrarCaja(CierreCajaDTO $dto)
    {
        return DB::transaction(function () use ($dto) {
            $cajaEfectivo = $this->cajaRepository->getCajaAbiertaByTipo('efectivo');
            $cajaDigital = $this->cajaRepository->getCajaAbiertaByTipo('digital');

            if (!$cajaEfectivo || !$cajaDigital) {
                throw new HttpException(422, 'No existen las dos cajas abiertas.');
            }

            $this->cerrarCajaPorTipo(
                caja: $cajaEfectivo,
                montoCierreReal: $dto->monto_cierre_real_efectivo,
                userId: $dto->user_id,
                observacion: $dto->observacion_cierre
            );

            $this->cerrarCajaPorTipo(
                caja: $cajaDigital,
                montoCierreReal: $dto->monto_cierre_real_digital,
                userId: $dto->user_id,
                observacion: $dto->observacion_cierre
            );

            return [
                'cajas' => [
                    'efectivo' => $this->cajaRepository->findById($cajaEfectivo->id),
                    'digital' => $this->cajaRepository->findById($cajaDigital->id),
                ],
            ];
        });
    }

    private function cerrarCajaPorTipo($caja, float $montoCierreReal, int $userId, ?string $observacion): void
    {
        $resumen = $this->getResumenCaja($caja->id);

        $montoCierreSistema = $resumen['saldo_sistema'];
        $diferencia = $montoCierreReal - $montoCierreSistema;

        $this->cajaRepository->updateCaja($caja, [
            'fecha_cierre' => now(),
            'monto_cierre_sistema' => $montoCierreSistema,
            'monto_cierre_real' => $montoCierreReal,
            'diferencia_cierre' => $diferencia,
            'estado' => 'cerrada',
            'user_cierre_id' => $userId,
            'observacion_cierre' => $observacion,
        ]);
    }

    public function getResumenCajaActual(): array
    {
        $cajaEfectivo = $this->cajaRepository->getCajaAbiertaByTipo('efectivo');
        $cajaDigital = $this->cajaRepository->getCajaAbiertaByTipo('digital');

        if (!$cajaEfectivo || !$cajaDigital) {
            throw new HttpException(422, 'No existen las dos cajas abiertas.');
        }

        return [
            'efectivo' => $this->getResumenCaja($cajaEfectivo->id),
            'digital' => $this->getResumenCaja($cajaDigital->id),
        ];
    }

    private function getResumenCaja(int $cajaId): array
    {
        $ingresos = $this->cajaRepository->sumMovimientosByTipo($cajaId, 'ingreso');
        $egresos = $this->cajaRepository->sumMovimientosByTipo($cajaId, 'egreso');

        return [
            'caja_id' => $cajaId,
            'ingresos' => $ingresos,
            'egresos' => $egresos,
            'saldo_sistema' => $ingresos - $egresos,
        ];
    }

    public function paginateMovimientos(array $filters = [], int $perPage = 10)
    {
        return $this->cajaRepository->paginateMovimientos($filters, $perPage);
    }

    public function paginateHistorico(array $filters = [], int $perPage = 10)
    {
        return $this->cajaRepository->paginateHistorico($filters, $perPage);
    }
}