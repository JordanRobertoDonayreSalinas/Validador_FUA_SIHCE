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
        Schema::create('m1_prestaciones', function (Blueprint $table) {
            $table->id();

            $table->string('codigo')->unique(); // Ej: '056'
            $table->string('denominacion'); // Ej: 'CONSULTA EXTERNA' [cite: 4]
            $table->integer('edad_minima')->default(0); // [cite: 16]
            $table->integer('edad_maxima')->default(120); // [cite: 16]
            $table->string('sexo')->default('Ambos'); // [cite: 16]
            $table->integer('tope_dia')->default(1); // [cite: 18]
            $table->boolean('requiere_fpp')->default(false); // Para gestantes [cite: 36, 213]

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('m1_prestaciones');
    }
};
