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
        Schema::create('m1_profesional_reglas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('prestacion_id')->constrained('m1_prestaciones');
            $table->string('tipo_profesional'); // Médico, Odontólogo, Obstetra, etc. [cite: 29, 30]
            $table->string('nivel_eess'); // I-1, I-2, I-3, I-4, II-1, etc. [cite: 28, 29, 30]
            $table->boolean('puede_prescribir')->default(true); // Falso si es consultor (300) [cite: 182, 183]
            $table->boolean('obligatorio_smi')->default(true); // Peso, Talla, PA [cite: 32]

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('m1_profesional_reglas');
    }
};
