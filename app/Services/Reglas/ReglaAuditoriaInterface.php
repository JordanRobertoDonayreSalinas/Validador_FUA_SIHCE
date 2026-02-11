<?php

namespace App\Services\Reglas;

use App\Models\FuaAtencionDetallado;

interface ReglaAuditoriaInterface
{
    /**
     * Evalúa la regla y retorna un array con el error y la solución si falla.
     * Retorna null si la validación es exitosa.
     * * @return array|null ['error' => '...', 'solucion' => '...']
     */
    public function validar(FuaAtencionDetallado $fua): ?array;
}