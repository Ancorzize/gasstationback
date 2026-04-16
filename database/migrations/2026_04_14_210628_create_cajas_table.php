<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();

            $table->dateTime('fecha_apertura');
            $table->dateTime('fecha_cierre')->nullable();

            $table->decimal('monto_apertura', 14, 2)->default(0);
            $table->decimal('monto_cierre_sistema', 14, 2)->default(0);
            $table->decimal('monto_cierre_real', 14, 2)->nullable();
            $table->decimal('diferencia_cierre', 14, 2)->default(0);

            $table->string('estado', 20)->default('abierta'); // abierta | cerrada

            $table->foreignId('user_apertura_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('user_cierre_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('observacion_apertura')->nullable();
            $table->text('observacion_cierre')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cajas');
    }
};