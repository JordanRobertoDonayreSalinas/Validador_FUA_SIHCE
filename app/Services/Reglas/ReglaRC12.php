<?php

namespace App\Services\Reglas;

use App\Models\FuaAtencionDetallado;

class ReglaRC12 implements ReglaAuditoriaInterface
{
    /**
     * Matriz de configuración
     */
    protected $reglas = [
        '007' => ['med' => true, 'proc' => true, 'desc' => 'Suplemento de micronutrientes'],
        '008' => ['med' => true, 'proc' => true, 'desc' => 'Profilaxis antiparasitaria'],
        '011' => ['med' => false, 'proc' => true, 'desc' => 'Exámenes de laboratorio gestante'],
        '013' => ['med' => false, 'proc' => true, 'desc' => 'Exámenes de ecografía obstétrica'],
        '015' => ['med' => false, 'proc' => true, 'desc' => 'Diagnóstico del embarazo'],
        '019' => ['med' => false, 'proc' => true, 'desc' => 'Detección de trastornos de agudeza visual'],
        '020' => ['med' => false, 'proc' => true, 'desc' => 'Salud Bucal'],
        '911' => ['med' => false, 'proc' => true, 'desc' => 'Instrucción de Higiene Oral'],
        '021' => ['med' => false, 'proc' => true, 'desc' => 'Prevención de caries'],
        '023' => ['med' => false, 'proc' => true, 'desc' => 'Deteccion precoz cancer prostata'],
        '024' => ['med' => false, 'proc' => true, 'desc' => 'Deteccion precoz cáncer cérvico-uterino'],
        '025' => ['med' => false, 'proc' => true, 'desc' => 'Deteccion precoz cancer mama'],
        '026' => ['med' => true, 'proc' => false, 'desc' => 'Profilaxis gestante VIH'],
        '027' => ['med' => true, 'proc' => false, 'desc' => 'Profilaxis niños expuestos al VIH'],
        '050' => ['med' => true, 'proc' => true, 'desc' => 'Atención inmediata del recién nacido'],
        '051' => ['med' => true, 'proc' => true, 'desc' => 'Internamiento RN patología no quirúrgica'],
        '052' => ['med' => true, 'proc' => true, 'desc' => 'Internamiento RN quirúrgica'],
        '053' => ['med' => true, 'proc' => false, 'desc' => 'Tx. VIH/SIDA (0 - 19 años)'],
        '054' => ['med' => true, 'proc' => true, 'desc' => 'Atención de parto vaginal'],
        '055' => ['med' => true, 'proc' => true, 'desc' => 'Cesárea'],
        '902' => ['med' => true, 'proc' => true, 'desc' => 'Atención Preconcepcional'],
        '903' => ['med' => false, 'proc' => true, 'desc' => 'Adulto Mayor'],
        '904' => ['med' => false, 'proc' => true, 'desc' => 'Joven y Adulto'],
        '906' => ['med' => false, 'proc' => true, 'desc' => 'Consulta no médicos'],
        '056' => ['med' => true, 'proc' => true, 'desc' => 'Consulta externa'],
        '057' => ['med' => false, 'proc' => true, 'desc' => 'Restauracion dental simple'],
        '058' => ['med' => false, 'proc' => true, 'desc' => 'Restauracion dental compuesta'],
        '059' => ['med' => true, 'proc' => true, 'desc' => 'Extraccion dental'],
        '061' => ['med' => false, 'proc' => true, 'desc' => 'Atención en tópico'],
        '062' => ['med' => true, 'proc' => true, 'desc' => 'Emergencia'],
        '063' => ['med' => true, 'proc' => true, 'desc' => 'Emergencia con observación'],
        '064' => ['med' => true, 'proc' => true, 'desc' => 'Intervención ambulatoria'],
        '065' => ['med' => true, 'proc' => true, 'desc' => 'Internamiento sin intervención'],
        '066' => ['med' => true, 'proc' => true, 'desc' => 'Internamiento quirúrgica menor'],
        '067' => ['med' => true, 'proc' => true, 'desc' => 'Internamiento quirúrgica mayor'],
        '068' => ['med' => true, 'proc' => true, 'desc' => 'Internamiento UCI'],
        '069' => ['med' => false, 'proc' => true, 'desc' => 'Transfusión'],
        '070' => ['med' => true, 'proc' => true, 'desc' => 'Odontología especializada'],
        '071' => ['med' => false, 'proc' => true, 'desc' => 'Apoyo al diagnóstico'],
        '074' => ['med' => true, 'proc' => true, 'desc' => 'ITS'],
        '029' => ['med' => false, 'proc' => true, 'desc' => 'Tamizaje Neonatal'],
        '901' => ['med' => false, 'proc' => true, 'desc' => 'Apoyo al Tratamiento'],
        '900' => ['med' => true, 'proc' => true, 'desc' => 'Prótesis Dental'],
        '200' => ['med' => false, 'proc' => true, 'desc' => 'Rehabilitación'],
        '300' => ['med' => false, 'proc' => true, 'desc' => 'Telemedicina'],
        '301' => ['med' => true, 'proc' => true, 'desc' => 'Cuidado Integral Niño'],
        '302' => ['med' => true, 'proc' => true, 'desc' => 'Cuidado Integral Adolescente'],
        '303' => ['med' => true, 'proc' => true, 'desc' => 'Cuidado Integral Joven'],
        '304' => ['med' => true, 'proc' => true, 'desc' => 'Cuidado Integral Adulto'],
        '305' => ['med' => true, 'proc' => true, 'desc' => 'Cuidado Integral Adulto Mayor'],
        '306' => ['med' => true, 'proc' => true, 'desc' => 'Cuidado Integral Prenatal'],
    ];

    public function validar(FuaAtencionDetallado $fua): ?array
    {
        // 1. Obtener código prestacional
        $codPrestacion = $fua->reporte->cod_prestacional ?? null;

        // --- CORRECCIÓN AQUÍ: Usar $this->reglas ---
        if (!$codPrestacion || !isset($this->reglas[$codPrestacion])) {
            return null;
        }

        $requisito = $this->reglas[$codPrestacion]; // <--- CORRECCIÓN AQUÍ TAMBIÉN

        // 2. EXCEPCIONES
        if ($codPrestacion == '056' && $this->esExcepcion056($fua)) return null;
        
        if (in_array($codPrestacion, ['054', '055']) && $this->esExcepcionPaquete($fua)) return null;

        // 3. VERIFICACIÓN DE EXISTENCIAS
        $tieneMed = $fua->consumos->whereNotNull('cod_medicamento')->isNotEmpty() 
                 || $fua->consumos->whereNotNull('cod_insumo')->isNotEmpty();
        
        $tieneProc = $fua->consumos->whereNotNull('cpms')->isNotEmpty();

        // 4. EVALUACIÓN
        if ($requisito['med'] && !$tieneMed) {
            return [
                'error' => "RC-12: Prestación $codPrestacion ({$requisito['desc']}) exige registrar MEDICAMENTOS o INSUMOS.",
                'solucion' => 'Registrar los medicamentos o insumos administrados al paciente.'
            ];
        }

        if ($requisito['proc'] && !$tieneProc) {
            return [
                'error' => "RC-12: Prestación $codPrestacion ({$requisito['desc']}) exige registrar PROCEDIMIENTOS (CPMS).",
                'solucion' => 'Registrar el procedimiento realizado o el apoyo al diagnóstico.'
            ];
        }

        return null;
    }

    private function esExcepcion056($fua)
    {
        // Por ahora, deshabilitamos esta excepción ya que la tabla de diagnósticos aún no existe
        // TODO: Implementar cuando se agregue la tabla de diagnósticos
        return false;
    }

    private function esExcepcionPaquete($fua)
    {
        $paquete = $fua->reporte->nro_paquete ?? '';
        return in_array($paquete, ['001', '002']);
    }
}