<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorias_producto', function (Blueprint $table) {

            $table->foreignId('destino_recaudo_id')
                ->nullable()
                ->after('descripcion')
                ->constrained('destinos_recaudo');
        });
    }

    public function down(): void
    {
        Schema::table('categorias_producto', function (Blueprint $table) {

            $table->dropForeign([
                'destino_recaudo_id'
            ]);

            $table->dropColumn(
                'destino_recaudo_id'
            );
        });
    }
};