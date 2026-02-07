<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fua_atencion_detallado', function (Blueprint $table) {
            $table->string('cod_prestacion', 10)->nullable()->after('tipo_atencion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fua_atencion_detallado', function (Blueprint $table) {
            $table->dropColumn('cod_prestacion');
        });
    }
};
