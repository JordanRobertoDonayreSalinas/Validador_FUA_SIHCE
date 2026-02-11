<?php

namespace App\Services;

use App\Models\FuaAtencionDetallado;
use Illuminate\Support\Facades\Log;
// Aquí importas tus reglas individuales
use App\Services\Reglas\ReglaRC12;
use App\Services\Reglas\ReglaRC94;

class ReglasValidacionService
{
    /**
     * REGISTRO DE REGLAS ACTIVAS
     * Para agregar una nueva validación, simplemente añade su clase a este array.
     */
    protected $reglas = [
        ReglaRC12::class, // Consistencia de Medicamentos/Procedimientos obligatorios
        //ReglaRC94::class, // Consistencia de Dispositivos Médicos (Kit vs Nutrición)
        // ReglaRC87::class,
    ];

    public function ejecutarValidacion()
    {
        // Cargamos relaciones necesarias (Optimización Eager Loading)
        $registros = FuaAtencionDetallado::with(['consumos', 'reporte'])
                        ->whereIn('estado_validacion', [0, 2]) // Solo pendientes o con errores previos
                        ->get();
        
        $totalErrores = 0;

        Log::info('ReglasValidacionService - registros a procesar', ['count' => $registros->count()]);

        foreach ($registros as $fua) {
            $listaErrores = [];
            $listaSoluciones = [];

            Log::debug('ReglasValidacionService - procesando fua', ['fua_id' => $fua->fua_id, 'estado_fua' => $fua->estado_fua]);

            // --- MOTOR DE VALIDACIÓN ---
            foreach ($this->reglas as $claseRegla) {
                // Instanciamos la regla dinámicamente
                $regla = new $claseRegla();
                
                // Ejecutamos la validación
                $resultado = $regla->validar($fua);

                if ($resultado) {
                    $listaErrores[] = $resultado['error'];
                    $listaSoluciones[] = $resultado['solucion'];
                    Log::warning('ReglasValidacionService - regla detectada', [
                        'fua_id' => $fua->fua_id,
                        'regla' => $claseRegla,
                        'error' => $resultado['error'],
                    ]);
                }
            }

            // --- GUARDADO DE RESULTADOS ---
            if (count($listaErrores) > 0) {
                $fua->estado_validacion = 2; // ROJO: Con Errores
                $fua->observaciones_reglas = implode(' | ', $listaErrores);
                $fua->soluciones_reglas = implode(' | ', $listaSoluciones);
                $totalErrores++;
            } else {
                $fua->estado_validacion = 1; // VERDE: Conforme
                $fua->observaciones_reglas = 'Conforme';
                $fua->soluciones_reglas = null;
            }
            
            $fua->save();
        }

        return $totalErrores;
    }
}