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
        Schema::create('fua_smi', function (Blueprint $table) {
            $table->id();

            // Relación principal
            $table->string('fua_id')->index(); // Columna "FUA"

            // Datos del Diagnóstico asociado al indicador SMI
            $table->integer('nro_dx');
            $table->string('cie10', 10)->index();
            $table->text('diagnostico');

            // Datos del Indicador SMI
            $table->string('cod_smi', 10); // Ej: 003, 014 (Lo pongo string por si acaso empiece con 0)
            $table->string('servicio_materno_infantil'); // Descripción (Ej: Peso, Talla)
            
            // El resultado suele ser numérico, pero a veces trae texto o formatos como "109/70" (Presión Arterial)
            // Por eso es más seguro usar string para no perder datos.
            $table->string('resultado')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fua_smi');
    }
};
