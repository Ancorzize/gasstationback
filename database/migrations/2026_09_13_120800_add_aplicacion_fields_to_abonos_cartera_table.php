<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abonos_cartera', function (Blueprint $table) {
            $table->dateTime('fecha_aplicacion')->nullable()->after('user_id');

            $table->foreignId('user_aplicacion_id')
                ->nullable()
                ->after('fecha_aplicacion')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('abonos_cartera', function (Blueprint $table) {
            $table->dropForeign(['user_aplicacion_id']);

            $table->dropColumn([
                'fecha_aplicacion',
                'user_aplicacion_id',
            ]);
        });
    }
};
