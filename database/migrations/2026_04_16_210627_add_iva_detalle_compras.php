<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalle_compras', function (Blueprint $table) {
            $table->integer('iva')->default(0)->after('precio'); // puedes cambiar 'precio' por la columna real
            $table->decimal('soldicom', 12, 2)->default(0)->after('iva');
            $table->decimal('total', 12, 2)->default(0)->after('soldicom');
        });
    }

    public function down(): void
    {
        Schema::table('detalle_compras', function (Blueprint $table) {
            $table->dropColumn(['iva', 'soldicom', 'total']);
        });
    }
};