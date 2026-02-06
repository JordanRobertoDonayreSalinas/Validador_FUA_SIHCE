<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuaAtencionDetallado extends Model
{
    protected $table = 'fua_atencion_detallado'; 
    
    // Deshabilitamos la protección para poder hacer inserciones masivas rápidas
    protected $guarded = [];
}
