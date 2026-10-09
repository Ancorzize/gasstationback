<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->boolean('documento_soporte_electronico')->default(false)->after('estado_pago');
            $table->string('estado_documento_soporte', 30)->default('no_requerido')->after('documento_soporte_electronico');
        });

        Schema::table('gastos', function (Blueprint $table) {
            $table->boolean('documento_soporte_electronico')->default(false)->after('estado');
            $table->string('estado_documento_soporte', 30)->default('no_requerido')->after('documento_soporte_electronico');
        });
    }

    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->dropColumn([
                'documento_soporte_electronico',
                'estado_documento_soporte',
            ]);
        });

        Schema::table('gastos', function (Blueprint $table) {
            $table->dropColumn([
                'documento_soporte_electronico',
                'estado_documento_soporte',
            ]);
        });
    }
};
