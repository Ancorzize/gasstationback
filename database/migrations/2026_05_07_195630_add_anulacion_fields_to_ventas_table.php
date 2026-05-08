<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->text('motivo_anulacion')->nullable()->after('observacion');

            $table->foreignId('user_anulacion_id')
                ->nullable()
                ->after('motivo_anulacion')
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('fecha_anulacion')->nullable()->after('user_anulacion_id');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropForeign(['user_anulacion_id']);

            $table->dropColumn([
                'motivo_anulacion',
                'user_anulacion_id',
                'fecha_anulacion',
            ]);
        });
    }
};