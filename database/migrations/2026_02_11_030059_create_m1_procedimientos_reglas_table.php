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
        Schema::create('m1_procedimientos_reglas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('prestacion_id')->constrained('m1_prestaciones');
    $table->string('cpms_codigo'); // Ej: '85018' (Hemoglobina) [cite: 111]
    $table->string('denominacion');
    $table->boolean('es_obligatorio')->default(false);
    $table->string('regla_asociada')->nullable(); // Ej: 'RC 31' o 'RC 12' [cite: 194, 216]

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('m1_procedimientos_reglas');
    }
};
