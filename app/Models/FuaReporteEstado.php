<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuaReporteEstado extends Model
{
    protected $table = 'fua_reporte_estado'; 
    
    // Deshabilitamos la protección para poder hacer inserciones masivas rápidas
    protected $guarded = [];
}
