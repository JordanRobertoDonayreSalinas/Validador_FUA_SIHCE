<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuaSmi extends Model
{
    protected $table = 'fua_smi'; 
    
    // Deshabilitamos la protección para poder hacer inserciones masivas rápidas
    protected $guarded = [];
}
