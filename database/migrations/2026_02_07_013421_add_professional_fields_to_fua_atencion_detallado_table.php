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
            // Campos para reglas RR_08 y RR_10
            $table->string('colegiatura')->nullable()->after('tipo_profesional');
            $table->string('especialidad')->nullable()->after('colegiatura');
            $table->string('rne')->nullable()->after('especialidad'); // Registro Nacional de Especialista

            // Campos para regla RR_00
            $table->date('fecha_afiliacion')->nullable()->after('historia_clinica');
            $table->date('fecha_baja')->nullable()->after('fecha_afiliacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fua_atencion_detallado', function (Blueprint $table) {
            $table->dropColumn(['colegiatura', 'especialidad', 'rne', 'fecha_afiliacion', 'fecha_baja']);
        });
    }
};
