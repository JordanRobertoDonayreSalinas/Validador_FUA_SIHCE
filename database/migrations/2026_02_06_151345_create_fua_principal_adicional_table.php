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
        Schema::create('fua_principal_adicional', function (Blueprint $table) {
            $table->id();

            // Identificador principal (N° Formato)
            $table->string('nro_formato')->index(); // Ej: 00003361-25-00059018

            // Datos del Paciente
            $table->string('dni', 20)->nullable();
            $table->date('fecha')->nullable(); // Fecha de la atención
            $table->string('beneficiario'); // Nombre completo
            $table->date('fecha_nacimiento')->nullable(); // F.Nac
            $table->integer('edad')->nullable();
            $table->char('sexo', 1)->nullable(); // M o F

            // Datos del Establecimiento y Servicio
            $table->string('eess')->nullable(); // Ej: LA PALMA GRANDE
            $table->string('id_servicio')->nullable(); // Ej: 056
            $table->string('servicio')->nullable(); // Ej: Consulta externa

            // Datos del Profesional
            $table->string('tipo_profesional')->nullable(); // Columna vacía en tu ejemplo, pero la incluimos
            $table->string('profesional')->nullable(); // Nombre del profesional

            // Datos del Diagnóstico
            $table->integer('nro_dx'); // 1, 2, etc.
            $table->string('cie10', 10)->index(); // Ej: E119
            $table->text('diagnostico'); // Descripción completa del diagnóstico

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fua_principal_adicional');
    }
};
