<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('anticipos_cliente', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 30)->unique();
            $table->foreignId('cliente_id')
                ->constrained('clientes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('caja_id')
                ->nullable()
                ->constrained('cajas')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignId('abono_cartera_id')
                ->nullable()
                ->unique()
                ->constrained('abonos_cartera')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->enum('origen', ['abono_excedente', 'directo', 'turno_islero']);
            $table->decimal('monto_original', 14, 2);
            $table->decimal('saldo_disponible', 14, 2);
            $table->enum('estado', ['disponible', 'agotado', 'anulado'])->default('disponible');
            $table->string('metodo_pago', 30);
            $table->string('referencia', 100)->nullable();
            $table->text('observacion')->nullable();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['cliente_id', 'estado']);
            $table->index('origen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anticipos_cliente');
    }
};
