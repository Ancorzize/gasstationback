<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            // Clasificación tributaria explícita: 'gravado', 'exento', 'excluido'.
            // NULL indica que un producto histórico aún está pendiente de clasificación.
            $table->string('tipo_impuesto', 30)
                ->nullable()
                ->after('precio_venta');

            // Tarifa de IVA con precisión decimal (ej. 19.00, 5.00, 0.00).
            $table->decimal('porcentaje_iva', 6, 2)
                ->nullable()
                ->after('tipo_impuesto');

            // Código externo del tributo respaldado por el catálogo MATÍAS / DIAN (ej. '1' para IVA).
            $table->string('codigo_impuesto', 30)
                ->nullable()
                ->after('porcentaje_iva');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_impuesto',
                'porcentaje_iva',
                'codigo_impuesto',
            ]);
        });
    }
};
