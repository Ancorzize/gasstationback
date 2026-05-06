<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abonos_cartera', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cliente_id')
                ->constrained('clientes')
                ->restrictOnDelete();

            $table->foreignId('caja_id')
                ->nullable()
                ->constrained('cajas')
                ->nullOnDelete();

            $table->date('fecha_abono');

            $table->decimal('valor', 14, 2);

            $table->string('medio_pago', 30);
            // efectivo | transferencia | consignacion | datafono | qr

            $table->text('observacion')->nullable();

            $table->string('estado', 20)->default('registrado');
            // registrado | anulado

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();

            $table->index('cliente_id');
            $table->index('fecha_abono');
            $table->index('medio_pago');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abonos_cartera');
    }
};