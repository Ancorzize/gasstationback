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
        Schema::table('documentos_electronicos', function (Blueprint $table) {
            $table->index(['compra_id', 'estado'], 'idx_doc_elec_compra_estado');
            $table->index(['gasto_id', 'estado'], 'idx_doc_elec_gasto_estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos_electronicos', function (Blueprint $table) {
            $table->dropIndex('idx_doc_elec_compra_estado');
            $table->dropIndex('idx_doc_elec_gasto_estado');
        });
    }
};
