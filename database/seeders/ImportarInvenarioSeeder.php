<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ImportarInvenarioSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'importar_inventario'
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
       

        // Admin → todos los permisos
        if ($admin) {
            $admin->givePermissionTo($permissions);
        }

        
    }
}