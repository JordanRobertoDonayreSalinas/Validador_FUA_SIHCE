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
        Schema::create('fua_atencion_detallado', function (Blueprint $table) {
            $table->id();

            // Identificadores principales del FUA
            $table->string('fua_id')->unique();
            $table->dateTime('fecha_atencion');
            $table->string('eess');
            $table->string('componente')->nullable();
            $table->string('tipo_atencion');
            
            // Referencias
            $table->string('eess_ref')->nullable();
            $table->string('nro_hoja_ref')->nullable();
            
            // Datos del Paciente / Asegurado
            $table->string('formato_asegurado')->nullable();
            $table->string('tipo_doc_paciente');
            $table->string('num_doc_paciente')->index(); // Indexado para búsquedas rápidas
            $table->string('apellido_paterno_paciente');
            $table->string('apellido_materno_paciente');
            $table->string('nombres_paciente');
            $table->date('fecha_nacimiento_paciente');
            $table->integer('edad');
            $table->string('sexo', 10); // M o F
            $table->string('historia_clinica')->nullable();
            $table->string('etnia')->nullable();
            
            // Datos del Servicio y Personal
            $table->string('id_servicio');
            $table->string('servicio_descripcion');
            $table->string('digitador')->nullable();
            $table->string('dni_resp_atencion')->nullable();
            $table->string('nombre_profesional')->nullable();
            $table->string('tipo_profesional')->nullable();
            
            // Tiempos y Ubicación
            $table->date('fecha_registro');
            $table->time('hora_registro');
            $table->string('lugar_atencion')->nullable();
            $table->string('destino_asegurado')->nullable();
            
            // Referencias y Contrareferencias
            $table->string('eess_ref_contra')->nullable();
            $table->string('nro_hoja_ref_contra')->nullable();
            $table->string('eess_oferta_flex')->nullable();
            
            // Datos Materno / Gestación
            $table->string('gestante')->nullable(); // Puede ser SI, NO, o un código
            $table->date('fecha_probable_parto')->nullable();
            $table->date('fecha_parto')->nullable();
            
            // Datos Administrativos
            $table->string('periodo', 7); // Ej: 2025-12
            $table->integer('mes');
            $table->string('estado_fua'); // Ej: VALIDADO, OBSERVADO
            $table->string('ups')->nullable();
            $table->dateTime('fecha_ingreso')->nullable();
            $table->dateTime('fecha_envio_sis')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fua_atencion_detallado');
    }
};
