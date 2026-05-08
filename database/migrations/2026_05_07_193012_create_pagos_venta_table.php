<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_venta', function (Blueprint $table) {
            $table->id();

            $table->foreignId('venta_id')
                ->constrained('ventas')
                ->cascadeOnDelete();

            $table->foreignId('caja_id')
                ->nullable()
                ->constrained('cajas')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->date('fecha_pago');

            $table->decimal('monto', 14, 2);

            $table->string('metodo_pago', 30);
            // efectivo | transferencia | consignacion | datafono | qr | credito

            $table->text('observacion')->nullable();

            $table->timestamps();

            $table->index('venta_id');
            $table->index('caja_id');
            $table->index('user_id');
            $table->index('fecha_pago');
            $table->index('metodo_pago');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_venta');
    }
};