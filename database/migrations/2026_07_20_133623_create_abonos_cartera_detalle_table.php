<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abonos_cartera_detalle', function (Blueprint $table) {

            $table->id();

            $table->foreignId('abono_cartera_id')
                ->constrained('abonos_cartera')
                ->cascadeOnDelete();

            $table->foreignId('venta_id')
                ->constrained('ventas')
                ->cascadeOnDelete();

            $table->decimal('valor_aplicado',15,2);

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'abonos_cartera_detalle'
        );
    }
};