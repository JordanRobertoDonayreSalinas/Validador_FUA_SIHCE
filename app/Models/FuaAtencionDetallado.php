<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuaAtencionDetallado extends Model
{
    protected $table = 'fua_atencion_detallado';

    // Deshabilitamos la protección para poder hacer inserciones masivas rápidas
    protected $guarded = [];

    // --- AGREGA ESTO PARA CORREGIR EL ERROR ---
    protected $casts = [
        'fecha_atencion' => 'datetime',
        'fecha_nacimiento_paciente' => 'date',
        'fecha_registro' => 'date',
        'fecha_probable_parto' => 'date',
        'fecha_parto' => 'date',
        'fecha_ingreso' => 'datetime',
        'fecha_envio_sis' => 'datetime',

    ];

    public function consumos()
    {
        // Asumiendo que fua_consumo tiene una columna fua_id que se relaciona con fua_id de esta tabla
        // Ojo: Si la relación es por 'id', ajustar. Pero el Excel suele usar 'fua_id' string.
        return $this->hasMany(FuaConsumo::class, 'fua_id', 'fua_id');
    }

    public function smi()
    {
        return $this->hasMany(FuaSmi::class, 'fua_id', 'fua_id');
    }

    public function reporte()
    {
        return $this->hasOne(FuaReporteEstado::class, 'fua_id', 'fua_id');
    }
}
