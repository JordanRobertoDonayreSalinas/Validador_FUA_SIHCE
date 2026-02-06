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
        Schema::create('fua_consumo', function (Blueprint $table) {
            $table->id();

            // Relación principal
            $table->string('fua_id')->index(); // Columna "FUA"

            // Datos generales del paciente y atención (redundantes pero presentes en el reporte)
            $table->string('beneficiario');
            $table->string('historia_clinica')->nullable();
            $table->string('id_servicio')->nullable(); // Columna "Servicio" (código)
            $table->string('contrato')->nullable();
            
            // Datos del Diagnóstico asociado al consumo
            $table->integer('nro_dx');
            $table->string('tipo_dx')->nullable(); // DEFINITIVO, REPETIDO, PRESUNTIVO
            $table->string('cie10', 10)->nullable();
            $table->text('diagnostico')->nullable();

            // --- PROCEDIMIENTOS (CPMS) ---
            $table->string('cpms')->nullable(); // Código del procedimiento
            $table->text('descripcion_procedimiento')->nullable();
            $table->decimal('proc_cant_indicada', 8, 2)->nullable();
            $table->decimal('proc_cant_entregada', 8, 2)->nullable();
            $table->string('resultado')->nullable(); // A veces viene vacío

            // --- MEDICAMENTOS ---
            $table->string('cod_medicamento')->nullable();
            $table->text('descripcion_medicamento')->nullable();
            $table->decimal('med_cant_prescrita', 8, 2)->nullable();
            $table->decimal('med_cant_entregada', 8, 2)->nullable();

            // --- INSUMOS ---
            $table->string('cod_insumo')->nullable();
            $table->text('descripcion_insumo')->nullable();
            $table->decimal('ins_cant_prescrita', 8, 2)->nullable();
            $table->decimal('ins_cant_entregada', 8, 2)->nullable();

            // Datos adicionales de estado
            $table->string('gestante')->nullable(); // SI/NO/NO GESTANTE
            $table->string('estado_fua')->nullable(); // VALIDADO, OBSERVADO, etc.

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fua_consumo');
    }
};
