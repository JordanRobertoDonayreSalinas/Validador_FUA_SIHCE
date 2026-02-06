<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuaConsumo extends Model
{
    protected $table = 'fua_consumo'; 
    
    // Deshabilitamos la protección para poder hacer inserciones masivas rápidas
    protected $guarded = [];
}
