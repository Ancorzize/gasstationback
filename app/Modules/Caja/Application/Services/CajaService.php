<?php

namespace App\Modules\Caja\Application\Services;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Caja\Application\DTOs\AperturaCajaDTO;
use App\Modules\Caja\Application\DTOs\CierreCajaDTO;
use App\Modules\Caja\Application\Interfaces\CajaRepositoryInterface;
use App\Modules\Caja\Application\DTOs\IngresoCajaDTO;
use App\Modules\Caja\Application\DTOs\RetiroCajaDTO;
use App\Modules\Caja\Application\DTOs\TransferenciaCajaDTO;
use Illuminate\Support\Str;
class CajaService
{
    public function __construct(
        protected CajaRepositoryInterface $cajaRepository
    ) {}

    public function getCajaActual()
    {
        return $this->cajaRepository->getCajasAbiertas();
    }

    public function abrirCaja(
        AperturaCajaDTO $dto
    )
    {
        return DB::transaction(function () use ($dto) {

            if (
                $this->cajaRepository
                    ->existsCajaAbiertaByTipoAndDestino(
                        $dto->tipo_caja,
                        $dto->destino_recaudo_id
                    )
            ) {
                throw new HttpException(
                    422,
                    'Ya existe una caja abierta para ese destino y tipo.'
                );
            }

            $caja = $this->cajaRepository->createCaja([
                'nombre' => $dto->nombre,
                'tipo_caja' => $dto->tipo_caja,
                'destino_recaudo_id' => $dto->destino_recaudo_id,
                'fecha_apertura' => now(),
                'monto_apertura' => $dto->monto_apertura,
                'monto_cierre_sistema' => 0,
                'monto_cierre_real' => null,
                'diferencia_cierre' => 0,
                'estado' => 'abierta',
                'user_apertura_id' => $dto->user_id,
                'observacion_apertura' => $dto->observacion_apertura,
                'user_cierre_id' => null,
                'observacion_cierre' => null,
            ]);

            $this->cajaRepository->createMovimiento([
                'caja_id' => $caja->id,
                'tipo_movimiento' => 'ingreso',
                'categoria_movimiento' => 'apertura',
                'origen_modulo' => 'caja',
                'origen_id' => $caja->id,
                'medio_pago' => $dto->tipo_caja,
                'monto' => $dto->monto_apertura,
                'descripcion' => 'Apertura caja',
                'user_id' => $dto->user_id,
                'fecha_movimiento' => now(),
            ]);

            return $this->cajaRepository
                ->findById($caja->id);
        });
    }


    public function cerrarCaja(
        CierreCajaDTO $dto
    )
    {
        return DB::transaction(function () use ($dto) {

            $idsCajasAbiertas = $this->cajaRepository->getCajasAbiertas()->pluck('id')->toArray();

            if (empty($idsCajasAbiertas)) { 
                throw new HttpException( 422,'No existen cajas abiertas.');
            }

            $idsEnviados = collect($dto->cierres)->pluck('caja_id')->toArray();

            if (count($idsEnviados) !== count(array_unique($idsEnviados))) {
                throw new HttpException(
                    422,
                    'Existen cajas repetidas en el cierre.'
                );
            }

            $faltantes = array_diff(
                $idsCajasAbiertas,
                $idsEnviados
            );

            if (!empty($faltantes)) {

                throw new HttpException(
                    422,
                    'Debe enviar el arqueo de todas las cajas abiertas.'
                );
            }

            foreach ($dto->cierres as $cierre) {

                $caja = $this->cajaRepository->findById($cierre->caja_id);

                if (!$caja) {
                    throw new HttpException(
                        422,
                        "Caja {$cierre->caja_id} no existe."
                    );
                }

                if ($caja->estado !== 'abierta') {
                    throw new HttpException(
                        422,
                        "Caja {$caja->id} ya está cerrada."
                    );
                }

                $resumen =
                    $this->getResumenCaja(
                        $caja->id
                    );

                $saldoSistema =
                    $resumen['saldo_sistema'];

                $diferencia =
                    $cierre->monto_real
                    - $saldoSistema;

                $this->cajaRepository
                    ->updateCaja(
                        $caja,
                        [
                            'fecha_cierre' => now(),
                            'monto_cierre_sistema'
                                => $saldoSistema,
                            'monto_cierre_real'
                                => $cierre->monto_real,
                            'diferencia_cierre'
                                => $diferencia,
                            'estado' => 'cerrada',
                            'user_cierre_id'
                                => $dto->user_id,
                            'observacion_cierre'
                                => $dto->observacion_cierre,
                        ]
                    );
            }

            return true;
        });
    }

    public function getResumenCajaActual(): array
    {
         $cajas = $this->cajaRepository->getCajasAbiertas();

        return $cajas
            ->map(function ($caja) {

                $resumen = $this->getResumenCaja($caja->id);

                return [
                    'id' => $caja->id,
                    'nombre' => $caja->nombre,
                    'tipo_caja' => $caja->tipo_caja,
                    'destino_recaudo_id' => $caja->destino_recaudo_id,
                    'estado' => $caja->estado,
                    'fecha_apertura' => $caja->fecha_apertura,

                    'ingresos' => $resumen['ingresos'],
                    'egresos' => $resumen['egresos'],
                    'saldo_sistema' => $resumen['saldo_sistema'],
                ];
            })
            ->values()
            ->toArray();
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

    public function getSugerenciasApertura()
    {
        return $this->cajaRepository
            ->getSugerenciasApertura();
    }

    public function ingresarDinero(IngresoCajaDTO $dto)
    {
        return DB::transaction(function () use ($dto) {

            $caja = $this->cajaRepository->findById($dto->caja_id);

            if (!$caja) {
                throw new HttpException(
                    422,
                    'La caja no existe.'
                );
            }

            if ($caja->estado !== 'abierta') {
                throw new HttpException(
                    422,
                    'La caja se encuentra cerrada.'
                );
            }

            return $this->cajaRepository->createMovimiento([

                'caja_id' => $dto->caja_id,

                'tipo_movimiento' => 'ingreso',

                'categoria_movimiento' => 'ingreso_manual',

                'origen_modulo' => 'caja',

                'origen_id' => $dto->caja_id,

                'medio_pago' => $dto->medio_pago,

                'monto' => $dto->monto,

                'descripcion' =>
                    $dto->descripcion
                    ?? 'Ingreso manual de caja',

                'user_id' => $dto->user_id,

                'fecha_movimiento' => now()

            ]);
        });
    }

    public function retirarDinero(RetiroCajaDTO $dto)
    {
        return DB::transaction(function () use ($dto) {

            $caja = $this->cajaRepository->findById($dto->caja_id);

            if (!$caja) {
                throw new HttpException(
                    422,
                    'La caja no existe.'
                );
            }

            if ($caja->estado !== 'abierta') {
                throw new HttpException(
                    422,
                    'La caja está cerrada.'
                );
            }

            $resumen = $this->getResumenCaja(
                $dto->caja_id
            );

            if ($dto->monto > $resumen['saldo_sistema']) {

                throw new HttpException(
                    422,
                    'La caja no tiene saldo suficiente.'
                );

            }

            return $this->cajaRepository->createMovimiento([

                'caja_id'=>$dto->caja_id,

                'tipo_movimiento'=>'egreso',

                'categoria_movimiento'=>'retiro_manual',

                'origen_modulo'=>'caja',

                'origen_id'=>$dto->caja_id,

                'medio_pago'=>$dto->medio_pago,

                'monto'=>$dto->monto,

                'descripcion'=>$dto->descripcion
                    ?? 'Retiro manual de caja',

                'user_id'=>$dto->user_id,

                'fecha_movimiento'=>now()

            ]);

        });
    }

    public function transferir(
        TransferenciaCajaDTO $dto
    )
    {
        return DB::transaction(function () use ($dto) {

            $origen = $this->cajaRepository
                ->findById($dto->caja_origen_id);

            $destino = $this->cajaRepository
                ->findById($dto->caja_destino_id);

            if (!$origen) {
                throw new HttpException(
                    422,
                    'La caja origen no existe.'
                );
            }

            if (!$destino) {
                throw new HttpException(
                    422,
                    'La caja destino no existe.'
                );
            }

            if ($origen->estado!='abierta') {

                throw new HttpException(
                    422,
                    'La caja origen está cerrada.'
                );

            }

            if ($destino->estado!='abierta') {

                throw new HttpException(
                    422,
                    'La caja destino está cerrada.'
                );

            }

            $resumen = $this->getResumenCaja(
                $origen->id
            );

            if ($dto->monto>$resumen['saldo_sistema']) {

                throw new HttpException(
                    422,
                    'La caja origen no tiene saldo suficiente.'
                );

            }

            $referencia='TRF-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));

            $egreso=$this->cajaRepository->createMovimiento([

                'referencia'=>$referencia,

                'caja_id'=>$origen->id,

                'tipo_movimiento'=>'egreso',

                'categoria_movimiento'=>'transferencia',

                'origen_modulo'=>'caja',

                'origen_id'=>$origen->id,

                'medio_pago'=>$origen->tipo_caja,

                'monto'=>$dto->monto,

                'descripcion'=>$dto->descripcion
                    ?? 'Transferencia entre cajas',

                'user_id'=>$dto->user_id,

                'fecha_movimiento'=>now()

            ]);

            $ingreso=$this->cajaRepository->createMovimiento([

                'referencia'=>$referencia,

                'caja_id'=>$destino->id,

                'tipo_movimiento'=>'ingreso',

                'categoria_movimiento'=>'transferencia',

                'origen_modulo'=>'caja',

                'origen_id'=>$origen->id,

                'medio_pago'=>$destino->tipo_caja,

                'monto'=>$dto->monto,

                'descripcion'=>$dto->descripcion
                    ?? 'Transferencia entre cajas',

                'user_id'=>$dto->user_id,

                'fecha_movimiento'=>now()

            ]);

            return [

                'referencia'=>$referencia,

                'egreso'=>$egreso,

                'ingreso'=>$ingreso

            ];

        });
    }
}