<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
//ejecutar: php artisan db:seed --class=RolesAndPermissionsSeeder
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [

            // Clientes
            'ver_clientes',
            'crear_clientes',
            'editar_clientes',
            'eliminar_clientes',

            // Usuarios
            'ver_usuarios',
            'crear_usuarios',
            'editar_usuarios',
            'cambiar_estado_usuarios',

            // Roles
            'ver_roles',
            'editar_roles',

            // Proveedores
            'ver_proveedores',
            'crear_proveedores',
            'editar_proveedores',
            'eliminar_proveedores',

            // Marcas
            'ver_marcas',
            'crear_marcas',
            'editar_marcas',
            'cambiar_estado_marcas',
            'eliminar_marcas',

            // Categorías producto
            'ver_categorias_producto',
            'crear_categorias_producto',
            'editar_categorias_producto',
            'cambiar_estado_categorias_producto',
            'eliminar_categorias_producto',

            // Unidades de medida
            'ver_unidades_medida',
            'crear_unidades_medida',
            'editar_unidades_medida',
            'cambiar_estado_unidades_medida',
            'eliminar_unidades_medida',

            // Productos
            'ver_productos',
            'crear_productos',
            'editar_productos',
            'cambiar_estado_productos',
            'eliminar_productos',

            // Servicios
            'ver_servicios',
            'crear_servicios',
            'editar_servicios',
            'cambiar_estado_servicios',
            'eliminar_servicios',

            // Configuración empresa
            'ver_configuracion_empresa',
            'editar_configuracion_empresa',

            // Bodegas
            'ver_bodegas',
            'crear_bodegas',
            'editar_bodegas',
            'cambiar_estado_bodegas',
            'eliminar_bodegas',

            // Inventario
            'ver_inventario',
            'ver_mis_productos_bodega',
            'ver_movimientos_inventario',
            'crear_movimientos_inventario',
            'importar_inventario',

            // Compras
            'ver_compras',
            'crear_compras',
            'editar_compras',
            'confirmar_compras',
            'ver_pagos_compra',
            'registrar_pagos_compra',

            // Caja
            'ver_caja',
            'abrir_caja',
            'cerrar_caja',
            'ver_movimientos_caja',

            // Categorías gasto
            'ver_categorias_gasto',
            'crear_categorias_gasto',
            'editar_categorias_gasto',
            'cambiar_estado_categorias_gasto',

            // Gastos
            'ver_gastos',
            'crear_gastos',
            'anular_gastos',

            'ver_cartera',
            'configurar_credito_clientes',
            'ver_estado_cuenta_clientes',
            'registrar_abonos_cartera',
            'ver_movimientos_cartera',
            'ver_reporte_cartera',
            'anular_abonos_cartera',

            //ISLEROS
            // Estaciones
            'ver_estaciones',
            'crear_estaciones',
            'editar_estaciones',
            'cambiar_estado_estaciones',

            // Bombas
            'ver_bombas',
            'crear_bombas',
            'editar_bombas',
            'cambiar_estado_bombas',

            // Mangueras
            'ver_mangueras',
            'crear_mangueras',
            'editar_mangueras',
            'cambiar_estado_mangueras',

            // Turnos Islero
            'ver_turnos_islero',
            'abrir_turnos_islero',
            'cerrar_turnos_islero',

            // Cartera
            'ver_cartera_clientes',
            'registrar_abonos_cartera',

            // Ventas
            'ver_ventas',
            'crear_ventas',
            'anular_ventas',

            // Isleros / reportes
            'ver_reportes_turnos_islero',
            'ver_resumen_cierre_turnos_islero',

            'ver_precios_combustible',
            'crear_precios_combustible',
            'cambiar_estado_precios_combustible',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'sanctum',
            ]);
        }

        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'sanctum',
        ]);

        $adminRole->syncPermissions($permissions);

        Role::firstOrCreate(['name' => 'cajero', 'guard_name' => 'sanctum']);
        Role::firstOrCreate(['name' => 'vendedor', 'guard_name' => 'sanctum']);
        Role::firstOrCreate(['name' => 'islero', 'guard_name' => 'sanctum']);

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('Admin123*'),
                'is_active' => true,
            ]
        );

        if (!$adminUser->hasRole('admin')) {
            $adminUser->assignRole($adminRole);
        }

        $adminUser->syncPermissions($permissions);
    }
}