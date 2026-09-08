<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turno_islero_recaudos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('turno_islero_id')
                ->constrained('turnos_islero')
                ->cascadeOnDelete();

            $table->foreignId('destino_recaudo_id')
                ->constrained('destinos_recaudo')
                ->restrictOnDelete();

            $table->decimal('efectivo', 15, 2)->default(0);
            $table->decimal('qr', 15, 2)->default(0);
            $table->decimal('datafono', 15, 2)->default(0);
            $table->decimal('transferencia', 15, 2)->default(0);
            $table->decimal('consignacion', 15, 2)->default(0);

            $table->decimal('total', 15, 2)->default(0);

            $table->timestamps();

            $table->unique([
                'turno_islero_id',
                'destino_recaudo_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turno_islero_recaudos');
    }
};