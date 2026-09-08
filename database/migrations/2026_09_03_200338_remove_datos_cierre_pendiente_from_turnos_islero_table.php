<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('turnos_islero', function (Blueprint $table) {
            $table->dropColumn([
                'datos_cierre_pendiente',
                'observacion_devolucion',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('turnos_islero', function (Blueprint $table) {
            $table->json('datos_cierre_pendiente')->nullable();
            $table->text('observacion_devolucion')->nullable();
        });
    }
};