<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class M1Prestacion extends Model
{
    // Define la tabla exacta
    protected $table = 'm1_prestaciones';
    
    // Desactiva la protección contra asignación masiva
    protected $guarded = [];
}