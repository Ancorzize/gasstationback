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
        Schema::create('aplicaciones_anticipo_cliente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anticipo_cliente_id')
                ->constrained('anticipos_cliente')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('venta_id')
                ->constrained('ventas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->decimal('monto_aplicado', 14, 2);
            $table->decimal('saldo_anterior', 14, 2);
            $table->decimal('saldo_nuevo', 14, 2);
            $table->enum('estado', ['aplicada', 'revertida'])->default('aplicada');
            $table->text('observacion')->nullable();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->timestamp('revertido_at')->nullable();
            $table->foreignId('revertido_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['anticipo_cliente_id', 'estado'], 'idx_app_ant_cli_estado');
            $table->index('venta_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aplicaciones_anticipo_cliente');
    }
};
