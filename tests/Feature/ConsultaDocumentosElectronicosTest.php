<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\DocumentoElectronico;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class ConsultaDocumentosElectronicosTest extends TestCase
{
    use RefreshDatabase;

    protected User $userConPermiso;
    protected User $userSinPermiso;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear permisos y roles
        $permiso = Permission::firstOrCreate(['name' => 'ver_documentos_electronicos', 'guard_name' => 'sanctum']);
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        $role->givePermissionTo($permiso);

        $this->userConPermiso = User::create([
            'name' => 'Usuario Con Permiso',
            'email' => 'con_permiso_' . uniqid() . '@gasstation.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->userConPermiso->assignRole($role);

        $this->userSinPermiso = User::create([
            'name' => 'Usuario Sin Permiso',
            'email' => 'sin_permiso_' . uniqid() . '@gasstation.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
    }

    /** @test */
    public function caso_a_consulta_hoy_devuelve_unicamente_documentos_de_hoy(): void
    {
        $hoy = Carbon::now()->toDateString();

        // Documento de hoy
        $docHoy = new DocumentoElectronico([
            'tipo_documento' => 'factura_electronica',
            'estado' => 'emitido',
            'proveedor' => 'matias',
            'ambiente' => 'sandbox',
            'numero_documento' => 'SETP-001',
            'prefijo' => 'SETP',
            'cufe' => 'cufe-hoy-123',
        ]);
        $docHoy->created_at = Carbon::now();
        $docHoy->save();

        // Documento de ayer
        $docAyer = new DocumentoElectronico([
            'tipo_documento' => 'factura_electronica',
            'estado' => 'emitido',
            'proveedor' => 'matias',
            'ambiente' => 'sandbox',
            'numero_documento' => 'SETP-000',
            'prefijo' => 'SETP',
            'cufe' => 'cufe-ayer-123',
        ]);
        $docAyer->created_at = Carbon::now()->subDay();
        $docAyer->save();

        $response = $this->actingAs($this->userConPermiso, 'sanctum')
            ->getJson("/api/documentos-electronicos?fecha_inicial={$hoy}&fecha_final={$hoy}");

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.numero_documento', 'SETP-001');
    }

    /** @test */
    public function caso_b_rango_varios_dias_devuelve_documentos_dentro_del_rango(): void
    {
        $hace3Dias = Carbon::now()->subDays(3)->toDateString();
        $hoy = Carbon::now()->toDateString();

        // Documento dentro del rango (hace 2 días)
        $docRango = new DocumentoElectronico([
            'tipo_documento' => 'factura_electronica',
            'estado' => 'emitido',
            'numero_documento' => 'SETP-002',
        ]);
        $docRango->created_at = Carbon::now()->subDays(2);
        $docRango->save();

        // Documento fuera del rango (hace 5 días)
        $docFuera = new DocumentoElectronico([
            'tipo_documento' => 'factura_electronica',
            'estado' => 'emitido',
            'numero_documento' => 'SETP-999',
        ]);
        $docFuera->created_at = Carbon::now()->subDays(5);
        $docFuera->save();

        $response = $this->actingAs($this->userConPermiso, 'sanctum')
            ->getJson("/api/documentos-electronicos?fecha_inicial={$hace3Dias}&fecha_final={$hoy}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.numero_documento', 'SETP-002');
    }

    /** @test */
    public function caso_c_fecha_inicial_posterior_a_fecha_final_retorna_422(): void
    {
        $hoy = Carbon::now()->toDateString();
        $ayer = Carbon::now()->subDay()->toDateString();

        $response = $this->actingAs($this->userConPermiso, 'sanctum')
            ->getJson("/api/documentos-electronicos?fecha_inicial={$hoy}&fecha_final={$ayer}");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fecha_final']);
    }

    /** @test */
    public function caso_d_usuario_sin_permiso_retorna_403(): void
    {
        $hoy = Carbon::now()->toDateString();

        $response = $this->actingAs($this->userSinPermiso, 'sanctum')
            ->getJson("/api/documentos-electronicos?fecha_inicial={$hoy}&fecha_final={$hoy}");

        $response->assertStatus(403);
    }

    /** @test */
    public function caso_e_rango_sin_documentos_retorna_200_con_coleccion_vacia(): void
    {
        $hace10Dias = Carbon::now()->subDays(10)->toDateString();
        $hace8Dias = Carbon::now()->subDays(8)->toDateString();

        $response = $this->actingAs($this->userConPermiso, 'sanctum')
            ->getJson("/api/documentos-electronicos?fecha_inicial={$hace10Dias}&fecha_final={$hace8Dias}");

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonCount(0, 'data.items')
            ->assertJsonPath('data.pagination.total', 0);
    }
}
