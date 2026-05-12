<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bombas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('estacion_id')
                ->constrained('estaciones')
                ->restrictOnDelete();

            $table->string('codigo', 30);
            $table->string('nombre', 150);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['estacion_id', 'codigo']);

            $table->index('codigo');
            $table->index('nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bombas');
    }
};