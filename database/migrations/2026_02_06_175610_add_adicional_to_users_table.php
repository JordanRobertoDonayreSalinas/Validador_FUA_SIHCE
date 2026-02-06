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
        Schema::table('users', function (Blueprint $table) {

            $table->string('tipo_doc', 20)->default('DNI')->after('email'); 
            
            // 2. Número de documento (Aumenté a 20 por si es CE, pero puedes dejarlo en 8)
            $table->string('num_doc', 20)->unique()->nullable()->after('tipo_doc');
            
            // 3. Datos personales
            $table->string('apellido_paterno')->nullable()->after('num_doc');
            $table->string('apellido_materno')->nullable()->after('apellido_paterno');
            $table->string('nombres')->nullable()->after('apellido_materno');
            
            // 4. Código EESS
            $table->string('cod_eess')->nullable()->index()->after('nombres');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tipo_doc', 'num_doc', 'apellido_paterno', 'apellido_materno', 'nombres', 'cod_eess']);
        });
    }
};
