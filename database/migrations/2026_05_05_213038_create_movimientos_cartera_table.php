<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_cartera', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cliente_id')
                ->constrained('clientes')
                ->restrictOnDelete();

            $table->string('tipo_movimiento', 30);
            // venta_credito | abono | ajuste | anulacion

            $table->string('origen_modulo', 50)->nullable();
            $table->unsignedBigInteger('origen_id')->nullable();

            $table->decimal('valor', 14, 2);
            $table->decimal('saldo_anterior', 14, 2);
            $table->decimal('saldo_nuevo', 14, 2);

            $table->string('medio_pago', 30)->nullable();
            $table->text('descripcion')->nullable();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->dateTime('fecha_movimiento');

            $table->timestamps();

            $table->index('cliente_id');
            $table->index('tipo_movimiento');
            $table->index('fecha_movimiento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_cartera');
    }
};