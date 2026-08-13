<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aplicaciones_abono_saldo_inicial', function (Blueprint $table) {

            $table->id();

            $table->foreignId('abono_cartera_id')
                ->constrained('abonos_cartera')
                ->restrictOnDelete();

            $table->foreignId('saldo_inicial_id')
                ->constrained('saldos_iniciales_cartera')
                ->restrictOnDelete();

            $table->decimal('valor_aplicado', 15, 2);

            $table->timestamps();

            $table->index('abono_cartera_id');
            $table->index('saldo_inicial_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aplicaciones_abono_saldo_inicial');
    }
};