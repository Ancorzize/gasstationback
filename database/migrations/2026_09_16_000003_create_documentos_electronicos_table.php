<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_electronicos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('venta_id')
                ->nullable()
                ->constrained('ventas')
                ->nullOnDelete();

            $table->foreignId('configuracion_facturacion_id')
                ->nullable()
                ->constrained('configuracion_facturacion')
                ->nullOnDelete();

            $table->string('tipo_documento', 50)->default('factura_electronica');
            $table->string('estado', 30)->default('pendiente');
            $table->string('proveedor', 50)->default('matias');
            $table->string('ambiente', 20)->default('sandbox');

            $table->string('identificador_externo', 100)->nullable();
            $table->string('cufe', 255)->nullable();
            $table->string('numero_documento', 50)->nullable();
            $table->string('prefijo', 20)->nullable();
            $table->string('track_id', 100)->nullable();

            $table->text('mensaje')->nullable();
            $table->json('errores')->nullable();
            $table->json('datos_tecnicos')->nullable();

            $table->timestamps();

            // Índices para consultas de trazabilidad
            $table->index('venta_id');
            $table->index('estado');
            $table->index('tipo_documento');
            $table->index('identificador_externo');
            $table->index('cufe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_electronicos');
    }
};
