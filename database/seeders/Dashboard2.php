<?php

namespace Database\Seeders;

use App\Models\DashboardWidget;
use Illuminate\Database\Seeder;
//ejecutar: C:\php84\php.exe artisan db:seed --class=Dashboard2

class Dashboard2 extends Seeder
{
    public function run(): void
    {

        DashboardWidget::create([
            'codigo' => 'comparativo_ventas',
            'nombre' => 'Comparativo Ventas',
            'tipo' => 'chart',
            'categoria' => 'ventas',
            'icono' => 'TrendingUp',
            'color' => 'blue',

        ]);

        DashboardWidget::create([
            'codigo' => 'ventas_por_hora',
            'nombre' => 'Ventas por Hora',
            'tipo' => 'chart',
            'categoria' => 'ventas',
            'icono' => 'Clock',
            'color' => 'orange',
            'ancho' => 12,
            'alto' => 2,

        ]);

        DashboardWidget::create([

            'codigo' => 'cartera_vencida',

            'nombre' => 'Cartera Vencida',

            'tipo' => 'card',

            'categoria' => 'cartera',

            'icono' => 'TriangleAlert',

            'color' => 'red',

        ]);
    }
}
