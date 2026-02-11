<?php

namespace App\Services\Reglas;

use App\Models\FuaAtencionDetallado;

class ReglaRC94 implements ReglaAuditoriaInterface
{
    public function validar(FuaAtencionDetallado $fua): ?array
    {
        // Extraemos todos los insumos de este FUA en un array simple
        $insumosPresentes = $fua->consumos->pluck('cod_insumo')->filter()->toArray();

        // Si no tiene el "Kit de Ropa" (54854), no aplicamos esta regla
        if (!in_array('54854', $insumosPresentes)) {
            return null;
        }

        // Si tiene el Kit, DEBE tener alguno de estos medicamentos (Nutrición Parenteral)
        $medicamentosRequeridos = [
            '55051', '55046', '54961', '52910', '52802', '50915' 
            // ... (puedes agregar toda la lista del CSV aquí)
        ];

        $medsPresentes = $fua->consumos->pluck('cod_medicamento')->filter()->toArray();

        // Verificamos si hay intersección (al menos una coincidencia)
        $tieneVinculo = !empty(array_intersect($medicamentosRequeridos, $medsPresentes));

        if (!$tieneVinculo) {
            return [
                'error' => 'RC-94: Se registró Kit de Ropa (54854) sin medicamento de Nutrición Parenteral asociado.',
                'solucion' => 'Debe registrar uno de los medicamentos de nutrición (Ej: 55051, 55046...) para justificar el uso del Kit.'
            ];
        }

        return null;
    }
}