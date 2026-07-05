<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cajas', function (Blueprint $table) {

            $table->string('nombre', 150)
                ->nullable()
                ->after('id');

            $table->foreignId('destino_recaudo_id')
                ->nullable()
                ->after('tipo_caja')
                ->constrained('destinos_recaudo');
        });
    }

    public function down(): void
    {
        Schema::table('cajas', function (Blueprint $table) {

            $table->dropForeign([
                'destino_recaudo_id'
            ]);

            $table->dropColumn([
                'nombre',
                'destino_recaudo_id'
            ]);
        });
    }
};