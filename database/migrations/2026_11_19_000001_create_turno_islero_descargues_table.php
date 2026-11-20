<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turno_islero_descargues', function (Blueprint $table) {
            $table->id();

            $table->foreignId('turno_islero_id')
                ->constrained('turnos_islero')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->decimal('monto', 14, 2);
            $table->timestamp('fecha_descargue');
            $table->string('observacion', 255)->nullable();

            $table->timestamps();

            $table->index(['turno_islero_id', 'fecha_descargue'], 'idx_turno_descargues_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turno_islero_descargues');
    }
};
