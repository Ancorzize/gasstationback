<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estaciones', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 30)->unique();
            $table->string('nombre', 150);

            $table->string('direccion', 255)->nullable();
            $table->string('telefono', 30)->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('codigo');
            $table->index('nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estaciones');
    }
};