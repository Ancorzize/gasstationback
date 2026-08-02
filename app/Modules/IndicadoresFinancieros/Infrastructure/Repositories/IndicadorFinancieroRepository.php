<?php

namespace App\Modules\IndicadoresFinancieros\Infrastructure\Repositories;

use App\Models\Caja;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Inventario;
use App\Modules\IndicadoresFinancieros\Application\Interfaces\IndicadorFinancieroRepositoryInterface;

class IndicadorFinancieroRepository implements IndicadorFinancieroRepositoryInterface
{
    public function capitalTrabajo(): array
    {
        /*
        |--------------------------------------------------------------------------
        | EFECTIVO
        |--------------------------------------------------------------------------
        */

        $efectivo = Caja::query()

            ->where('tipo_caja', 'efectivo')

            ->get()

            ->sum(function ($caja) {

                return $caja->movimientos()

                    ->where('tipo_movimiento', 'ingreso')
                    ->sum('monto')

                    -

                    $caja->movimientos()

                    ->where('tipo_movimiento', 'egreso')
                    ->sum('monto');
            });

        /*
        |--------------------------------------------------------------------------
        | BANCOS
        |--------------------------------------------------------------------------
        */

        $bancos = Caja::query()

            ->where('tipo_caja', 'digital')

            ->get()

            ->sum(function ($caja) {

                return $caja->movimientos()

                    ->where('tipo_movimiento', 'ingreso')
                    ->sum('monto')

                    -

                    $caja->movimientos()

                    ->where('tipo_movimiento', 'egreso')
                    ->sum('monto');
            });

        /*
        |--------------------------------------------------------------------------
        | CARTERA
        |--------------------------------------------------------------------------
        */

        $cartera = Cliente::sum('saldo_credito');

        $inventarioCosto  = Inventario::query()
            ->join(
                'productos',
                'productos.id',
                '=',
                'inventarios.producto_id'
            )
            ->selectRaw("
                SUM(
                    inventarios.cantidad
                    *
                    productos.precio_compra
                ) total
            ")
            ->value('total') ?? 0;


        /*
        |--------------------------------------------------------------------------
        | PROVEEDORES
        |--------------------------------------------------------------------------
        */

        $proveedores = Compra::sum('saldo_pendiente');

        /*
        |--------------------------------------------------------------------------
        | TOTALES
        |--------------------------------------------------------------------------
        */

       $totalActivos = $efectivo + $bancos+ $cartera + $inventarioCosto;

        $totalPasivos = $proveedores;

        $capitalTrabajo =
            $totalActivos
            -
            $totalPasivos;

        return [

           'activos' => [
                'efectivo' => (float) $efectivo,
                'bancos' => (float) $bancos,
                'cartera' => (float) $cartera,
                'inventario' => (float) $inventarioCosto,
                'total_activos' => (float) $totalActivos
            ],

            'pasivos' => [
                'proveedores' => (float)$proveedores,
                'total_pasivos' => (float)$totalPasivos
            ],

            'capital_trabajo' => [
                'valor' => (float)$capitalTrabajo,
                'estado' =>
                    $capitalTrabajo >= 0
                        ? 'SUPERAVIT'
                        : 'DEFICIT',
                'mensaje' =>
                    $capitalTrabajo >= 0
                        ? 'La empresa tiene liquidez suficiente.'
                        : 'Los pasivos superan los activos.',

            ],

        ];
    }
}