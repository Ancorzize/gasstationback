<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mapeos_catalogos_facturacion', function (Blueprint $table) {
            $table->id();

            $table->string('proveedor', 50)->default('matias');
            $table->string('categoria', 50); // medio_pago | unidad_medida | impuesto | tipo_documento | regimen
            $table->string('codigo_interno', 100);
            $table->string('codigo_externo', 100);
            $table->string('codigo_externo_secundario', 100)->nullable();
            $table->string('descripcion', 255)->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['proveedor', 'categoria', 'codigo_interno'], 'idx_mapeo_catalogos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mapeos_catalogos_facturacion');
    }
};
