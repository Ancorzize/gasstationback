<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('turnos_islero', function (Blueprint $table) {
            $table->decimal(
                'total_ventas_combustible_sistema',
                15,
                2
            )->default(0);

            $table->decimal(
                'total_ventas_combustible_fisica',
                15,
                2
            )->default(0);

            $table->decimal(
                'diferencia_combustible',
                15,
                2
            )->default(0);

            $table->decimal(
                'total_recaudo_esperado',
                15,
                2
            )->default(0);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('turnos_islero', function (Blueprint $table) {
            //
        });
    }
};
