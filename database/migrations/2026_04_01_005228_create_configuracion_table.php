<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion_empresa', function (Blueprint $table) {
            $table->id();

            // Empresa
            $table->string('nombre_empresa', 150);
            $table->string('nombre_comercial', 150)->nullable();
            $table->string('nit', 30);
            $table->string('dv', 5)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('direccion', 200)->nullable();

            // Ubicación
            $table->foreignId('pais_id')
                ->nullable()
                ->constrained('paises')
                ->nullOnDelete();

            $table->foreignId('departamento_id')
                ->nullable()
                ->constrained('departamentos')
                ->nullOnDelete();

            $table->foreignId('ciudad_id')
                ->nullable()
                ->constrained('ciudades')
                ->nullOnDelete();

            // Imagen
            $table->string('logo_url')->nullable();

            // Fiscal
            $table->boolean('responsable_iva')->default(true);
            $table->string('regimen', 100)->nullable();
            $table->decimal('porcentaje_iva', 5, 2)->default(0);
            $table->boolean('maneja_iva_incluido')->default(false);

            // Facturación
            $table->string('prefijo_factura', 20)->nullable();
            $table->string('numero_resolucion', 100)->nullable();
            $table->date('fecha_resolucion')->nullable();
            $table->unsignedBigInteger('rango_desde')->nullable();
            $table->unsignedBigInteger('rango_hasta')->nullable();
            $table->date('fecha_vencimiento')->nullable();

            // Sistema
            $table->string('moneda', 10)->default('COP');
            $table->string('simbolo_moneda', 10)->default('$');
            $table->unsignedInteger('decimales')->default(2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_empresa');
    }
};