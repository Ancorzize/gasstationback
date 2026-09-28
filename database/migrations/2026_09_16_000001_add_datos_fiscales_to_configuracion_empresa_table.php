<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracion_empresa', function (Blueprint $table) {
            $table->string('tipo_persona', 20)->nullable()->after('direccion');
            $table->string('tipo_documento', 20)->nullable()->after('tipo_persona');
            $table->string('tipo_regimen', 50)->nullable()->after('regimen');
            $table->json('responsabilidades_fiscales')->nullable()->after('tipo_regimen');
            $table->string('codigo_postal', 10)->nullable()->after('ciudad_id');
            $table->string('matricula_mercantil', 50)->nullable()->after('codigo_postal');
        });
    }

    public function down(): void
    {
        Schema::table('configuracion_empresa', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_persona',
                'tipo_documento',
                'tipo_regimen',
                'responsabilidades_fiscales',
                'codigo_postal',
                'matricula_mercantil',
            ]);
        });
    }
};
