<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('tipo_persona', 20)->nullable()->after('is_active');
            $table->string('tipo_documento_id', 20)->nullable()->after('tipo_persona');
            $table->unsignedInteger('tipo_organization_id')->nullable()->after('tipo_documento_id');
            $table->unsignedInteger('tax_regime_id')->nullable()->after('tipo_organization_id');
            $table->unsignedInteger('tax_level_id')->nullable()->after('tax_regime_id');
            $table->string('codigo_postal', 10)->nullable()->after('direccion');

            $table->foreignId('ciudad_id')
                ->nullable()
                ->after('codigo_postal')
                ->constrained('ciudades')
                ->nullOnDelete();

            $table->foreignId('pais_id')
                ->nullable()
                ->after('ciudad_id')
                ->constrained('paises')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropForeign(['ciudad_id']);
            $table->dropForeign(['pais_id']);

            $table->dropColumn([
                'tipo_persona',
                'tipo_documento_id',
                'tipo_organization_id',
                'tax_regime_id',
                'tax_level_id',
                'codigo_postal',
                'ciudad_id',
                'pais_id',
            ]);
        });
    }
};
