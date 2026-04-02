<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

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

            // Categorías
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

            // Configuración
            'ver_configuracion_empresa',
            'editar_configuracion_empresa',
        ];

        // Crear permisos
        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'sanctum',
            ]);
        }

        // Crear rol admin
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'sanctum',
        ]);

        // Asignar TODOS los permisos al admin
        $adminRole->syncPermissions($permissions);

        // Crear otros roles básicos
        Role::firstOrCreate(['name' => 'cajero', 'guard_name' => 'sanctum']);
        Role::firstOrCreate(['name' => 'vendedor', 'guard_name' => 'sanctum']);
        Role::firstOrCreate(['name' => 'islero', 'guard_name' => 'sanctum']);

        // Crear usuario administrador por defecto
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('Admin123*'),
                'is_active' => true,
            ]
        );

        // Asignar rol admin
        if (!$adminUser->hasRole('admin')) {
            $adminUser->assignRole($adminRole);
        }
    }
}