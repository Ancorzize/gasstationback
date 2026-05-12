<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mangueras', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bomba_id')
                ->constrained('bombas')
                ->restrictOnDelete();

            $table->foreignId('producto_id')
                ->constrained('productos')
                ->restrictOnDelete();

            $table->string('codigo', 30);
            $table->string('nombre', 150);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['bomba_id', 'codigo']);

            $table->index('codigo');
            $table->index('nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mangueras');
    }
};