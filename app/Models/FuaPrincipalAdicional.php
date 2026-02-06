<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuaPrincipalAdicional extends Model
{
    protected $table = 'fua_principal_adicional'; 
    
    // Deshabilitamos la protección para poder hacer inserciones masivas rápidas
    protected $guarded = [];
}
