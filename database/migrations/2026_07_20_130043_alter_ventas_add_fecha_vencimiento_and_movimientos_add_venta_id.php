<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {

            $table->dateTime('fecha_vencimiento')
                ->nullable()
                ->after('fecha_venta');

        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {

            $table->dropColumn('fecha_vencimiento');

        });
    }
};