<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class InventarioPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [

            // Inventario
            'ver_inventario',
            'ver_mis_productos_bodega',

            // Movimientos
            'ver_movimientos_inventario',
            'crear_movimientos_inventario',
        ];

        // Crear permisos
        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'sanctum',
            ]);
        }

        // Roles
        $admin = Role::where('name', 'admin')->first();
        $islero = Role::where('name', 'islero')->first();
        $cajero = Role::where('name', 'cajero')->first();

        // Admin → todos los permisos
        if ($admin) {
            $admin->givePermissionTo($permissions);
        }

        // Islero → solo su inventario
        if ($islero) {
            $islero->givePermissionTo([
                'ver_mis_productos_bodega',
            ]);
        }

        // Cajero → consulta general
        if ($cajero) {
            $cajero->givePermissionTo([
                'ver_inventario',
                'ver_movimientos_inventario',
            ]);
        }
    }
}