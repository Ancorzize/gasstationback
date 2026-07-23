<?php

namespace Database\Seeders;

use App\Models\DashboardWidget;
use Illuminate\Database\Seeder;
//ejecutar: C:\php84\php.exe artisan db:seed --class=Dashboard

class Dashboard extends Seeder
{
    public function run(): void
    {
        DashboardWidget::create([
            'codigo' => 'ventas_hoy',
            'nombre' => 'Ventas Hoy',
            'tipo' => 'card',
            'categoria' => 'ventas',
            'icono' => 'DollarSign',
            'color' => 'green',
        ]);

        DashboardWidget::create([
            'codigo' => 'compras_hoy',
            'nombre' => 'Compras Hoy',
            'tipo' => 'card',
            'categoria' => 'compras',
            'icono' => 'ShoppingCart',
            'color' => 'blue',
        ]);

        DashboardWidget::create([
            'codigo' => 'gastos_hoy',
            'nombre' => 'Gastos Hoy',
            'tipo' => 'card',
            'categoria' => 'gastos',
            'icono' => 'Wallet',
            'color' => 'red',
        ]);

        DashboardWidget::create([
            'codigo' => 'top_productos',
            'nombre' => 'Top Productos',
            'tipo' => 'chart',
            'categoria' => 'ventas',
            'icono' => 'Package',
            'color' => 'blue',
            'ancho' => 6,
            'alto' => 2,
        ]);

        DashboardWidget::create([
            'codigo' => 'ventas_por_islero',
            'nombre' => 'Ventas por Islero',
            'tipo' => 'chart',
            'categoria' => 'ventas',
            'icono' => 'Users',
            'color' => 'blue',
            'ancho' => 6,
            'alto' => 2,
        ]);

        DashboardWidget::create([
            'codigo'=>'galones_combustible',
            'nombre'=>'Galones por Combustible',
            'tipo'=>'chart',
            'categoria'=>'combustibles',
            'icono'=>'Fuel',
            'color'=>'orange',
            'ancho'=>6,
            'alto'=>2,
        ]);

        DashboardWidget::create([
            'codigo' => 'estado_cajas',
            'nombre' => 'Estado de Cajas',
            'tipo' => 'table',
            'categoria' => 'caja',
            'icono' => 'Wallet',
            'color' => 'green',
            'ancho' => 12,
            'alto' => 2,
        ]);

        DashboardWidget::create([
            'codigo' => 'productos_criticos',
            'nombre' => 'Productos Críticos',
            'tipo' => 'table',
            'categoria' => 'inventario',
            'icono' => 'TriangleAlert',
            'color' => 'red',
            'ancho' => 6,
            'alto' => 2,

        ]);

        DashboardWidget::create([
            'codigo' => 'turnos_abiertos_detalle',
            'nombre' => 'Turnos Abiertos',
            'tipo' => 'table',
            'categoria' => 'turnos',
            'icono' => 'Clock',
            'color' => 'orange',
            'ancho' => 6,
            'alto' => 2,

        ]);

        DashboardWidget::create([
            'codigo' => 'ultimas_ventas',
            'nombre' => 'Últimas Ventas',
            'tipo' => 'table',
            'categoria' => 'ventas',
            'icono' => 'Receipt',
            'color' => 'green',
            'ancho' => 12,
            'alto' => 2
        ]);

        DashboardWidget::create([
            'codigo' => 'ultimos_gastos',
            'nombre' => 'Últimos Gastos',
            'tipo' => 'table',
            'categoria' => 'gastos',
            'icono' => 'Wallet',
            'color' => 'red',
            'ancho' => 6,
            'alto' => 2
        ]);

        DashboardWidget::create([
            'codigo' => 'ultimas_compras',
            'nombre' => 'Últimas Compras',
            'tipo' => 'table',
            'categoria' => 'compras',
            'icono' => 'Truck',
            'color' => 'blue',
            'ancho' => 6,
            'alto' => 2
        ]);

        DashboardWidget::create([
            'codigo' => 'ventas_destino_recaudo',
            'nombre' => 'Ventas por Destino de Recaudo',
            'tipo' => 'chart',
            'categoria' => 'ventas',
            'icono' => 'PieChart',
            'color' => 'green',
            'ancho' => 6,
            'alto' => 2,
        ]);

        DashboardWidget::create([
            'codigo' => 'top_clientes',
            'nombre' => 'Top Clientes',
            'tipo' => 'chart',
            'categoria' => 'ventas',
            'icono' => 'Users',
            'color' => 'blue',
            'ancho' => 6,
            'alto' => 2,

        ]);

        DashboardWidget::create([
            'codigo' => 'top_proveedores',
            'nombre' => 'Top Proveedores',
            'tipo' => 'chart',
            'categoria' => 'compras',
            'icono' => 'Truck',
            'color' => 'orange',
            'ancho' => 6,
            'alto' => 2,

        ]);

        DashboardWidget::create([
            'codigo'=>'recaudo_medio_pago',
            'nombre'=>'Recaudo por Medio de Pago',
            'tipo'=>'chart',
            'categoria'=>'caja',
            'icono'=>'CreditCard',
            'color'=>'green',
            'ancho'=>6,
            'alto'=>2,

        ]);

        DashboardWidget::create([

            'codigo'=>'flujo_caja',
            'nombre'=>'Flujo Diario de Caja',
            'tipo'=>'chart',
            'categoria'=>'caja',
            'icono'=>'TrendingUp',
            'color'=>'blue',
            'ancho'=>12,
            'alto'=>2,

        ]);

        DashboardWidget::create([

            'codigo'=>'ingresos_egresos',
            'nombre'=>'Ingresos vs Egresos',
            'tipo'=>'chart',
            'categoria'=>'caja',
            'icono'=>'Scale',
            'color'=>'orange',
            'ancho'=>6,
            'alto'=>2,

        ]);

        DashboardWidget::create([
            'codigo' => 'ventas_mes',
            'nombre' => 'Ventas del Período',
            'tipo' => 'card',
            'categoria' => 'ventas',
            'icono' => 'DollarSign',
            'color' => 'green',
        ]);

        DashboardWidget::create([
            'codigo' => 'compras_mes',
            'nombre' => 'Compras del Período',
            'tipo' => 'card',
            'categoria' => 'compras',
            'icono' => 'ShoppingCart',
            'color' => 'blue',
        ]);

        DashboardWidget::create([
            'codigo' => 'gastos_mes',
            'nombre' => 'Gastos del Período',
            'tipo' => 'card',
            'categoria' => 'gastos',
            'icono' => 'Wallet',
            'color' => 'red',
        ]);

        DashboardWidget::create([
            'codigo' => 'clientes_totales',
            'nombre' => 'Clientes Totales',
            'tipo' => 'card',
            'categoria' => 'clientes',
            'icono' => 'Users',
            'color' => 'blue',
        ]);

        DashboardWidget::create([
            'codigo' => 'clientes_nuevos',
            'nombre' => 'Clientes Nuevos',
            'tipo' => 'card',
            'categoria' => 'clientes',
            'icono' => 'UserPlus',
            'color' => 'green',
        ]);

        DashboardWidget::create([
            'codigo' => 'productos_activos',
            'nombre' => 'Productos Activos',
            'tipo' => 'card',
            'categoria' => 'inventario',
            'icono' => 'Package',
            'color' => 'blue',
        ]);

        DashboardWidget::create([
            'codigo' => 'productos_bajo_stock',
            'nombre' => 'Productos Bajo Stock',
            'tipo' => 'card',
            'categoria' => 'inventario',
            'icono' => 'TriangleAlert',
            'color' => 'red',
        ]);

        DashboardWidget::create([
            'codigo' => 'cajas_abiertas',
            'nombre' => 'Cajas Abiertas',
            'tipo' => 'card',
            'categoria' => 'caja',
            'icono' => 'Wallet',
            'color' => 'green',
        ]);

        DashboardWidget::create([
            'codigo' => 'turnos_abiertos',
            'nombre' => 'Turnos Abiertos',
            'tipo' => 'card',
            'categoria' => 'turnos',
            'icono' => 'Clock',
            'color' => 'orange',
        ]);

        DashboardWidget::create([
            'codigo' => 'ventas_credito_hoy',
            'nombre' => 'Ventas a Crédito',
            'tipo' => 'card',
            'categoria' => 'ventas',
            'icono' => 'CreditCard',
            'color' => 'orange',
        ]);

        DashboardWidget::create([
            'codigo' => 'abonos_hoy',
            'nombre' => 'Abonos',
            'tipo' => 'card',
            'categoria' => 'cartera',
            'icono' => 'BadgeDollarSign',
            'color' => 'green',
        ]);

        DashboardWidget::create([
            'codigo' => 'saldo_cartera',
            'nombre' => 'Saldo de Cartera',
            'tipo' => 'card',
            'categoria' => 'cartera',
            'icono' => 'FileText',
            'color' => 'red',
        ]);

        DashboardWidget::create([
            'codigo' => 'ventas_30_dias',
            'nombre' => 'Ventas Últimos 30 Días',
            'tipo' => 'chart',
            'categoria' => 'ventas',
            'icono' => 'TrendingUp',
            'color' => 'green',
            'ancho' => 12,
            'alto' => 2,
        ]);

        DashboardWidget::create([
            'codigo' => 'ventas_medio_pago',
            'nombre' => 'Ventas por Medio de Pago',
            'tipo' => 'chart',
            'categoria' => 'ventas',
            'icono' => 'PieChart',
            'color' => 'blue',
            'ancho' => 6,
            'alto' => 2,
        ]);

        DashboardWidget::create([
            'codigo' => 'ticket_promedio',
            'nombre' => 'Ticket Promedio',
            'tipo' => 'card',
            'categoria' => 'ventas',
            'icono' => 'Receipt',
            'color' => 'green',
        ]);
    }
}
