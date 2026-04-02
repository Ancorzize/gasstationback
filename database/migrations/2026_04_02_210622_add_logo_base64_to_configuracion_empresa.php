<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracion_empresa', function (Blueprint $table) {
            $table->longText('logo_base64')->nullable()->after('logo_url');
            $table->string('logo_mime_type', 50)->nullable()->after('logo_base64');
        });
    }

    public function down(): void
    {
        Schema::table('configuracion_empresa', function (Blueprint $table) {
            $table->dropColumn(['logo_base64', 'logo_mime_type']);
        });
    }
};