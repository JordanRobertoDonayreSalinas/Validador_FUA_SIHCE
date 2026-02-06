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
        Schema::create('fua_reporte_estado', function (Blueprint $table) {
            $table->id();

            // Relación principal
            $table->string('fua_id')->unique()->index(); // Columna "N° FUA"

            // Datos de envío y paquete (Administrativo)
            // El paquete a veces viene en notación científica en Excel, mejor string
            $table->string('nro_paquete')->nullable(); 
            $table->string('responsable_envio')->nullable();
            $table->date('fecha_envio_set_sis')->nullable();

            // Fechas de control
            $table->date('fecha_atencion')->nullable();
            $table->date('fecha_edicion')->nullable();

            // Servicios y Prestaciones
            $table->string('cod_servicio')->nullable(); // Ej: 222400
            $table->string('descripcion_servicio')->nullable();
            $table->string('cod_prestacional')->nullable(); // Ej: 056
            $table->string('descripcion_prestacional')->nullable();

            // Estados y Firmas
            $table->string('estado_fua')->index(); // VALIDADO, OBSERVADO, EN PRODUCCION
            $table->string('firma_atencion')->nullable(); // SI/NO
            $table->string('firma_farmacia')->nullable(); // SI/NO

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fua_reporte_estado');
    }
};
