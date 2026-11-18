<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos_electronicos', function (Blueprint $table) {
            $table->index(
                ['venta_id', 'tipo_documento', 'estado'],
                'idx_doc_elec_venta_tipo_estado'
            );
        });
    }

    public function down(): void
    {
        Schema::table('documentos_electronicos', function (Blueprint $table) {
            $table->dropIndex('idx_doc_elec_venta_tipo_estado');
        });
    }
};
