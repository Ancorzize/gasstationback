<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lecturas_mangueras', function (Blueprint $table) {
            $table->id();

            $table->foreignId('turno_islero_id')
                ->constrained('turnos_islero')
                ->cascadeOnDelete();

            $table->foreignId('manguera_id')
                ->constrained('mangueras')
                ->restrictOnDelete();

            $table->decimal('lectura_inicial', 14, 3);
            $table->decimal('lectura_final', 14, 3)->nullable();

            $table->decimal('galones_vendidos', 14, 3)->default(0);
            $table->decimal('precio_galon', 14, 2);
            $table->decimal('total_venta', 14, 2)->default(0);

            $table->timestamps();

            $table->unique(['turno_islero_id', 'manguera_id']);
            $table->index('manguera_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturas_mangueras');
    }
};