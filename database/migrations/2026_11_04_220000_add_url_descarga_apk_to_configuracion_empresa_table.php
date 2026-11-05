<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracion_empresa', function (Blueprint $table) {
            $table->string('url_descarga_apk', 500)->nullable()->after('logo_url');
        });
    }

    public function down(): void
    {
        Schema::table('configuracion_empresa', function (Blueprint $table) {
            $table->dropColumn('url_descarga_apk');
        });
    }
};
