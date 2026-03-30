<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'ver_clientes',
            'crear_clientes',
            'editar_clientes',
            'eliminar_clientes',
            'ver_usuarios',
            'crear_usuarios',
            'editar_usuarios',
            'cambiar_estado_usuarios',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'sanctum',
            ]);
        }

        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'sanctum',
        ]);

        $admin->syncPermissions($permissions);

        Role::firstOrCreate([
            'name' => 'cajero',
            'guard_name' => 'sanctum',
        ]);

        Role::firstOrCreate([
            'name' => 'vendedor',
            'guard_name' => 'sanctum',
        ]);

        Role::firstOrCreate([
            'name' => 'islero',
            'guard_name' => 'sanctum',
        ]);
    }
}