<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalle_ventas', function (Blueprint $table) {
            // Ampliar precisión de cantidad de (14, 2) a (14, 3) para preservar los 3 decimales de galones de combustible.
            $table->decimal('cantidad', 14, 3)->change();

            // Adaptar la columna histórica 'iva' de integer a decimal(6, 2) para soportar tarifas decimales manteniendo retrocompatibilidad.
            $table->decimal('iva', 6, 2)->default(0)->change();

            // Snapshot histórico de la clasificación tributaria aplicada en el momento exacto de la venta ('gravado', 'exento', 'excluido').
            // NULL para ventas históricas previas sin clasificación explícita.
            $table->string('tipo_impuesto', 30)
                ->nullable()
                ->after('descuento');

            // Snapshot histórico explícito de la tarifa de IVA con precisión decimal.
            $table->decimal('porcentaje_iva', 6, 2)
                ->nullable()
                ->after('tipo_impuesto');

            // Snapshot histórico del código de tributo homologado para MATÍAS / DIAN (ej. '1' para IVA, NULL para excluido).
            $table->string('codigo_impuesto', 30)
                ->nullable()
                ->after('porcentaje_iva');
        });
    }

    public function down(): void
    {
        Schema::table('detalle_ventas', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_impuesto',
                'porcentaje_iva',
                'codigo_impuesto',
            ]);

            $table->integer('iva')->default(0)->change();
            $table->decimal('cantidad', 14, 2)->change();
        });
    }
};
