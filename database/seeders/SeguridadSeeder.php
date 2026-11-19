<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeguridadSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Permissions
        $permissionsPath = database_path('seeders/data/permissions.json');
        if (file_exists($permissionsPath)) {
            $permissions = json_decode(file_get_contents($permissionsPath), true);
            foreach ($permissions as $p) {
                DB::table('permissions')->updateOrInsert(
                    ['id' => $p['id']],
                    [
                        'name' => $p['name'],
                        'guard_name' => $p['guard_name'] ?? 'web',
                        'created_at' => $p['created_at'] ?? now(),
                        'updated_at' => $p['updated_at'] ?? now(),
                    ]
                );
            }
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("SELECT setval(pg_get_serial_sequence('permissions', 'id'), COALESCE((SELECT MAX(id) FROM permissions), 1));");
            }
        }

        // 2. Roles
        $rolesPath = database_path('seeders/data/roles.json');
        if (file_exists($rolesPath)) {
            $roles = json_decode(file_get_contents($rolesPath), true);
            foreach ($roles as $r) {
                DB::table('roles')->updateOrInsert(
                    ['id' => $r['id']],
                    [
                        'name' => $r['name'],
                        'guard_name' => $r['guard_name'] ?? 'web',
                        'created_at' => $r['created_at'] ?? now(),
                        'updated_at' => $r['updated_at'] ?? now(),
                    ]
                );
            }
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("SELECT setval(pg_get_serial_sequence('roles', 'id'), COALESCE((SELECT MAX(id) FROM roles), 1));");
            }
        }

        // 3. Role has permissions
        $rolePermissionsPath = database_path('seeders/data/role_has_permissions.json');
        if (file_exists($rolePermissionsPath)) {
            $rolePermissions = json_decode(file_get_contents($rolePermissionsPath), true);
            foreach ($rolePermissions as $rp) {
                DB::table('role_has_permissions')->updateOrInsert(
                    [
                        'permission_id' => $rp['permission_id'],
                        'role_id' => $rp['role_id'],
                    ]
                );
            }
        }

        // 4. Users (depends on bodegas for bodega_id)
        $usersPath = database_path('seeders/data/users.json');
        if (file_exists($usersPath)) {
            $users = json_decode(file_get_contents($usersPath), true);
            foreach ($users as $u) {
                DB::table('users')->updateOrInsert(
                    ['id' => $u['id']],
                    [
                        'name' => $u['name'],
                        'email' => $u['email'],
                        'email_verified_at' => $u['email_verified_at'] ?? null,
                        'password' => $u['password'],
                        'remember_token' => $u['remember_token'] ?? null,
                        'is_active' => (bool) ($u['is_active'] ?? true),
                        'bodega_id' => $u['bodega_id'] ?? null,
                        'created_at' => $u['created_at'] ?? now(),
                        'updated_at' => $u['updated_at'] ?? now(),
                    ]
                );
            }
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("SELECT setval(pg_get_serial_sequence('users', 'id'), COALESCE((SELECT MAX(id) FROM users), 1));");
            }
        }

        // 5. Model has roles
        $modelRolesPath = database_path('seeders/data/model_has_roles.json');
        if (file_exists($modelRolesPath)) {
            $modelRoles = json_decode(file_get_contents($modelRolesPath), true);
            foreach ($modelRoles as $mr) {
                DB::table('model_has_roles')->updateOrInsert(
                    [
                        'role_id' => $mr['role_id'],
                        'model_type' => $mr['model_type'],
                        'model_id' => $mr['model_id'],
                    ]
                );
            }
        }

        // 6. Model has permissions
        $modelPermissionsPath = database_path('seeders/data/model_has_permissions.json');
        if (file_exists($modelPermissionsPath)) {
            $modelPermissions = json_decode(file_get_contents($modelPermissionsPath), true);
            foreach ($modelPermissions as $mp) {
                DB::table('model_has_permissions')->updateOrInsert(
                    [
                        'permission_id' => $mp['permission_id'],
                        'model_type' => $mp['model_type'],
                        'model_id' => $mp['model_id'],
                    ]
                );
            }
        }

        // 7. Dashboard Widgets
        $widgetsPath = database_path('seeders/data/dashboard_widgets.json');
        if (file_exists($widgetsPath)) {
            $widgets = json_decode(file_get_contents($widgetsPath), true);
            foreach ($widgets as $w) {
                DB::table('dashboard_widgets')->updateOrInsert(
                    ['id' => $w['id']],
                    [
                        'codigo' => $w['codigo'],
                        'nombre' => $w['nombre'],
                        'tipo' => $w['tipo'],
                        'categoria' => $w['categoria'],
                        'icono' => $w['icono'] ?? null,
                        'color' => $w['color'] ?? null,
                        'ancho' => $w['ancho'] ?? null,
                        'alto' => $w['alto'] ?? null,
                        'descripcion' => $w['descripcion'] ?? null,
                        'is_active' => (bool) ($w['is_active'] ?? true),
                        'created_at' => $w['created_at'] ?? now(),
                        'updated_at' => $w['updated_at'] ?? now(),
                    ]
                );
            }
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("SELECT setval(pg_get_serial_sequence('dashboard_widgets', 'id'), COALESCE((SELECT MAX(id) FROM dashboard_widgets), 1));");
            }
        }

        // 8. Dashboard Widget Roles
        $widgetRolesPath = database_path('seeders/data/dashboard_widget_roles.json');
        if (file_exists($widgetRolesPath)) {
            $widgetRoles = json_decode(file_get_contents($widgetRolesPath), true);
            foreach ($widgetRoles as $dwr) {
                DB::table('dashboard_widget_roles')->updateOrInsert(
                    ['id' => $dwr['id']],
                    [
                        'dashboard_widget_id' => $dwr['dashboard_widget_id'],
                        'role_id' => $dwr['role_id'],
                        'orden' => $dwr['orden'] ?? 0,
                        'visible' => (bool) ($dwr['visible'] ?? true),
                        'created_at' => $dwr['created_at'] ?? now(),
                        'updated_at' => $dwr['updated_at'] ?? now(),
                    ]
                );
            }
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("SELECT setval(pg_get_serial_sequence('dashboard_widget_roles', 'id'), COALESCE((SELECT MAX(id) FROM dashboard_widget_roles), 1));");
            }
        }
    }
}
