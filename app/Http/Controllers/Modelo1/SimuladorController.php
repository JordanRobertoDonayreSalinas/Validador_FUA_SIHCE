<?php

namespace App\Http\Controllers\Modelo1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\M1Diagnostico056;
use App\Models\M1ProfesionalRegla;
use App\Models\M1ProtocoloAtencion;

class SimuladorController extends Controller
{
    public function index(Request $request)
    {
        // 1. Capturar inputs del formulario
        $profesional_seleccionado = $request->input('profesional');
        $cie10_busqueda = $request->input('cie10');
        
        // Variables de respuesta
        $alertas = [];
        $obligatorios = [];
        $diagnostico_obj = null;
        $reglas_profesional = null;

        // 2. Lógica de Validación (Solo si hay datos)
        if ($profesional_seleccionado) {
            // Buscamos reglas para I-3 por defecto o TODOS
            $reglas_profesional = M1ProfesionalRegla::where('tipo_profesional', $profesional_seleccionado)
                ->where(function($q) {
                    $q->where('nivel_eess', 'I-3')->orWhere('nivel_eess', 'TODOS');
                })->first();

            if ($reglas_profesional && !$reglas_profesional->puede_prescribir) {
                $alertas[] = "⚠️ ROL CONSULTOR: No puede prescribir medicamentos ni insumos.";
            }
        }

        if ($cie10_busqueda) {
            $diagnostico_obj = M1Diagnostico056::where('cie10_codigo', $cie10_busqueda)->first();

            if ($diagnostico_obj) {
                // Financiamiento
                if ($diagnostico_obj->financiamiento == 'Capitado') {
                    $alertas[] = "💰 Este diagnóstico es CAPITADO (Pago per cápita).";
                } else {
                    $alertas[] = "📄 Este diagnóstico es NO CAPITADO (Pago por servicio).";
                }

                // Reglas Clínicas
                if ($diagnostico_obj->valida_anemia) {
                    $obligatorios[] = "🩸 Dosaje de Hemoglobina (85018) - OBLIGATORIO por RC-61";
                }
                if ($diagnostico_obj->valida_hipertension) {
                    $obligatorios[] = "📏 Perímetro Abdominal (015) - OBLIGATORIO por Incentivos";
                    $obligatorios[] = "💓 Presión Arterial (301) - OBLIGATORIO";
                }
                if ($diagnostico_obj->cie10_codigo == 'J00X') {
                    $alertas[] = "🚫 ALERTA RC-14: Si receta antibióticos con este diagnóstico único, será observado.";
                }
            }
        }

        // Protocolos
        $protocolos_sugeridos = []; // Nueva variable

        if ($cie10_busqueda) {
            $diagnostico_obj = M1Diagnostico056::where('cie10_codigo', $cie10_busqueda)->first();

            if ($diagnostico_obj) {
                // ... (Tu lógica existente de alertas sigue aquí) ...

                // NUEVO: Buscar protocolos asociados
                $protocolos_sugeridos = M1ProtocoloAtencion::where('cie10_codigo', $cie10_busqueda)->get();
            }
        }

        // 3. Retornar la vista con los datos
        return view('public.simulador_mvc', compact(
            'profesional_seleccionado', 
            'cie10_busqueda', 
            'alertas', 
            'obligatorios', 
            'diagnostico_obj',
            'protocolos_sugeridos'
        ));
    }
}
