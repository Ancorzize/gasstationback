<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();

            $table->string('tipo_movimiento'); // traslado

            $table->foreignId('producto_id')->constrained()->cascadeOnDelete();

            $table->foreignId('bodega_origen_id')->nullable()->constrained('bodegas')->nullOnDelete();
            $table->foreignId('bodega_destino_id')->nullable()->constrained('bodegas')->nullOnDelete();

            $table->decimal('cantidad', 12, 2);

            $table->text('observacion')->nullable();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};