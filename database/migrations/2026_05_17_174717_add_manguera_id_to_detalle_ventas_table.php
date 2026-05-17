<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalle_ventas', function (Blueprint $table) {
            $table->foreignId('manguera_id')
                ->nullable()
                ->after('producto_id')
                ->constrained('mangueras')
                ->nullOnDelete();

            $table->index('manguera_id');
        });
    }

    public function down(): void
    {
        Schema::table('detalle_ventas', function (Blueprint $table) {
            $table->dropForeign(['manguera_id']);
            $table->dropIndex(['manguera_id']);
            $table->dropColumn('manguera_id');
        });
    }
};