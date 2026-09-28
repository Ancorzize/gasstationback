<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resoluciones_facturacion', function (Blueprint $table) {
            $table->id();

            $table->foreignId('configuracion_empresa_id')
                ->nullable()
                ->constrained('configuracion_empresa')
                ->nullOnDelete();

            $table->string('tipo_documento', 50)->default('factura');
            $table->string('prefijo', 20)->nullable();
            $table->string('numero_resolucion', 100);
            $table->date('fecha_resolucion')->nullable();
            $table->unsignedBigInteger('rango_desde')->nullable();
            $table->unsignedBigInteger('rango_hasta')->nullable();
            $table->unsignedBigInteger('consecutivo_actual')->default(1);
            $table->date('fecha_vencimiento')->nullable();
            $table->string('clave_tecnica', 255)->nullable();
            $table->string('proveedor', 50)->nullable();
            $table->string('ambiente', 20)->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['configuracion_empresa_id', 'tipo_documento', 'is_active']);
            $table->index('tipo_documento');
        });

        // Migración inicial de datos desde configuracion_empresa si existe configuración previa
        $empresa = DB::table('configuracion_empresa')->first();
        if ($empresa) {
            $numResolucion = $empresa->numero_resolucion ?: '18764074347312';
            $prefijo = $empresa->prefijo_factura ?: 'SETP';

            DB::table('resoluciones_facturacion')->insert([
                'configuracion_empresa_id' => $empresa->id,
                'tipo_documento' => 'factura',
                'prefijo' => $prefijo,
                'numero_resolucion' => $numResolucion,
                'fecha_resolucion' => $empresa->fecha_resolucion ?? null,
                'rango_desde' => $empresa->rango_desde ?? 1,
                'rango_hasta' => $empresa->rango_hasta ?? 5000,
                'consecutivo_actual' => 1,
                'fecha_vencimiento' => $empresa->fecha_vencimiento ?? null,
                'proveedor' => 'matias',
                'ambiente' => 'sandbox',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('resoluciones_facturacion');
    }
};
