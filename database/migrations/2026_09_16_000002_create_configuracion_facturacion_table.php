<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion_facturacion', function (Blueprint $table) {
            $table->id();

            $table->foreignId('configuracion_empresa_id')
                ->nullable()
                ->constrained('configuracion_empresa')
                ->nullOnDelete();

            $table->string('proveedor_activo', 50)->default('matias');
            $table->string('ambiente', 20)->default('sandbox'); // sandbox | produccion
            $table->boolean('facturacion_electronica_activa')->default(false);
            $table->boolean('reintentos_automaticos')->default(true);
            $table->unsignedInteger('max_reintentos')->default(3);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_facturacion');
    }
};
