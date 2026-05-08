<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abonos_cartera', function (Blueprint $table) {
            $table->foreignId('turno_islero_id')
                ->nullable()
                ->after('caja_id')
                ->constrained('turnos_islero')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('abonos_cartera', function (Blueprint $table) {
            $table->dropForeign(['turno_islero_id']);
            $table->dropColumn('turno_islero_id');
        });
    }
};