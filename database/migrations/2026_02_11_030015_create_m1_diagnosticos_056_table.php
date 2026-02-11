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
        Schema::create('m1_diagnosticos_056', function (Blueprint $table) {
            $table->id();

            $table->string('cie10_codigo')->index(); // Ej: 'I10X' [cite: 69]
            $table->text('descripcion');
            $table->enum('financiamiento', ['Capitado', 'No Capitado']); // [cite: 59, 69]
            // Banderas de consistencia
            $table->boolean('valida_anemia')->default(false); // Activa RC 61 [cite: 236]
            $table->boolean('valida_hipertension')->default(false); // Activa RC 14 y PAB [cite: 52, 56]
            $table->boolean('valida_diabetes')->default(false); // Activa tamizaje renal/PAB [cite: 54, 56]

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('m1_diagnosticos_056');
    }
};
