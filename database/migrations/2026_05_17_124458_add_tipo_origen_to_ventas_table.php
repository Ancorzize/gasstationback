<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('tipo_origen', 30)
                ->default('pos')
                ->after('tipo_venta');

            $table->index('tipo_origen');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropIndex(['tipo_origen']);
            $table->dropColumn('tipo_origen');
        });
    }
};