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
        Schema::create('m1_protocolos_atencion', function (Blueprint $table) {
            $table->id();

            // Relación con el diagnóstico (usamos el código CIE10 como llave foránea lógica)
            $table->string('cie10_codigo')->index(); 
            
            // Detalles del item sugerido
            $table->enum('tipo', ['PROCEDIMIENTO', 'LABORATORIO', 'MEDICAMENTO']);
            $table->string('codigo_item'); // CPMS o Código SISMED
            $table->string('descripcion');
            
            // Reglas asociadas
            $table->boolean('es_obligatorio')->default(false); // Ej: Hemoglobina en Anemia
            $table->string('regla_asociada')->nullable(); // Ej: "RC-31", "RC-12"

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('m1_protocolos_atencion');
    }
};
