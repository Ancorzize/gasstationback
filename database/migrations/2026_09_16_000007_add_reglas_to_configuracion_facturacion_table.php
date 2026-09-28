<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracion_facturacion', function (Blueprint $table) {
            $table->boolean('facturar_ventas_pos')->default(true)->after('facturacion_electronica_activa');
            $table->boolean('facturar_ventas_combustible')->default(true)->after('facturar_ventas_pos');
            $table->boolean('permitir_ventas_sin_datos_fiscales')->default(true)->after('facturar_ventas_combustible');
        });
    }

    public function down(): void
    {
        Schema::table('configuracion_facturacion', function (Blueprint $table) {
            $table->dropColumn([
                'facturar_ventas_pos',
                'facturar_ventas_combustible',
                'permitir_ventas_sin_datos_fiscales',
            ]);
        });
    }
};
