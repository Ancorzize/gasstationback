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
        Schema::table('anticipos_proveedor', function (Blueprint $table) {
            $table->foreignId('movimiento_caja_id')
                ->nullable()
                ->after('caja_id')
                ->constrained('movimientos_caja')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('anticipos_proveedor', function (Blueprint $table) {
            $table->dropForeign(['movimiento_caja_id']);
            $table->dropColumn('movimiento_caja_id');
        });
    }
};
