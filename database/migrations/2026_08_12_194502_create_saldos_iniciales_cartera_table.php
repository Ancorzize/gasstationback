<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saldos_iniciales_cartera', function (Blueprint $table) {

            $table->id();

            $table->foreignId('cliente_id')
                ->constrained('clientes')
                ->restrictOnDelete();

            $table->date('fecha_documento');

            $table->decimal('valor_original', 15, 2);

            $table->decimal('saldo_pendiente', 15, 2);

            $table->enum('estado', [
                'pendiente',
                'parcial',
                'pagado',
                'anulado'
            ])->default('pendiente');

            $table->text('observacion')->nullable();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('fecha_anulacion')->nullable();

            $table->foreignId('user_anulacion_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index([
                'cliente_id',
                'estado'
            ]);

            $table->index('fecha_documento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saldos_iniciales_cartera');
    }
};