<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turno_islero_mangueras', function (Blueprint $table) {
            $table->id();

            $table->foreignId('turno_islero_id')
                ->constrained('turnos_islero')
                ->cascadeOnDelete();

            $table->foreignId('manguera_id')
                ->constrained('mangueras')
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique(['turno_islero_id', 'manguera_id']);
            $table->index('manguera_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turno_islero_mangueras');
    }
};