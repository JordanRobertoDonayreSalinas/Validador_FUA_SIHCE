<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\M1Prestacion;
use App\Models\M1Diagnostico056;
use App\Models\M1ProfesionalRegla;
use App\Models\M1ProcedimientoRegla;
use App\Models\M1ProtocoloAtencion;

class M1Seeder extends Seeder
{
    public function run(): void
    {
        // 1. CREAR LA PRESTACIÓN MAESTRA (056)
        // Fuente: [cite: 4, 16, 18, 36]
        $prestacion = M1Prestacion::create([
            'codigo' => '056',
            'denominacion' => 'CONSULTA EXTERNA',
            'edad_minima' => 0,
            'edad_maxima' => 120,
            'sexo' => 'Ambos',
            'tope_dia' => 1,
            'requiere_fpp' => false // Por defecto false, se activa por regla de gestante
        ]);

        // 2. DIAGNÓSTICOS (CIE-10) Y SUS BANDERAS
        // Aquí definimos qué activa las alertas de validación
        
        $diagnosticos = [
            [
                // Hipertensión Esencial
                'cie10_codigo' => 'I10X', // 
                'descripcion' => 'HIPERTENSION ESENCIAL (PRIMARIA)',
                'financiamiento' => 'Capitado', // 
                'valida_anemia' => false,
                'valida_hipertension' => true, // Activa reglas de presión arterial [cite: 52]
                'valida_diabetes' => false,
            ],
            [
                // Diabetes Tipo 2 (Sin complicaciones)
                'cie10_codigo' => 'E119', // 
                'descripcion' => 'DIABETES MELLITUS TIPO 2, SIN MENCION DE COMPLICACION',
                'financiamiento' => 'No Capitado', // 
                'valida_anemia' => false,
                'valida_hipertension' => false,
                'valida_diabetes' => true, // Activa reglas de tamizaje renal/PAB [cite: 54]
            ],
            [
                // Anemia Ferropénica
                'cie10_codigo' => 'D509', // [cite: 69, 249]
                'descripcion' => 'ANEMIA POR DEFICIENCIA DE HIERRO SIN OTRA ESPECIFICACION',
                'financiamiento' => 'Capitado', // 
                'valida_anemia' => true, // Activa RC 61 (Hemoglobina obligatoria) [cite: 236]
                'valida_hipertension' => false,
                'valida_diabetes' => false,
            ],
            [
                // Resfriado Común (J00X)
                // Nota: Usamos J00X como genérico de Rinofaringitis mencionado en validaciones
                'cie10_codigo' => 'J00X', // 
                'descripcion' => 'RINOFARINGITIS AGUDA',
                'financiamiento' => 'Capitado', // 
                'valida_anemia' => false,
                'valida_hipertension' => false,
                'valida_diabetes' => false,
                // Nota: En lógica de negocio, este DX disparará la alerta de "No antibióticos" (RC 14) [cite: 87]
            ],
            [
                // Control de Embarazo de Alto Riesgo
                'cie10_codigo' => 'Z359', // [cite: 75]
                'descripcion' => 'SUPERVISION DE EMBARAZO DE ALTO RIESGO',
                'financiamiento' => 'Capitado', // [cite: 75]
                'valida_anemia' => true, // Gestantes requieren control de anemia [cite: 213, 238]
                'valida_hipertension' => false,
                'valida_diabetes' => false,
            ]
        ];

        foreach ($diagnosticos as $dx) {
            M1Diagnostico056::create($dx);
        }

        // 3. REGLAS POR PROFESIONAL
        // Definimos quién puede atender y si pueden recetar
        
        // Médico en I-3 (Puede todo)
        M1ProfesionalRegla::create([
            'prestacion_id' => $prestacion->id,
            'tipo_profesional' => 'MEDICO', // [cite: 29]
            'nivel_eess' => 'I-3', // [cite: 29]
            'puede_prescribir' => true,
            'obligatorio_smi' => true,
        ]);

        // Obstetra en I-3 (Puede todo con población adscrita)
        M1ProfesionalRegla::create([
            'prestacion_id' => $prestacion->id,
            'tipo_profesional' => 'OBSTETRA', // [cite: 29]
            'nivel_eess' => 'I-3',
            'puede_prescribir' => true,
            'obligatorio_smi' => true,
        ]);

        // Consultor (Código 300) - RESTRICCIÓN IMPORTANTE
        M1ProfesionalRegla::create([
            'prestacion_id' => $prestacion->id,
            'tipo_profesional' => 'CONSULTOR', // [cite: 182]
            'nivel_eess' => 'TODOS',
            'puede_prescribir' => false, // "NO PRESCRIBE MEDICAMENTOS NI INSUMOS" [cite: 182, 183]
            'obligatorio_smi' => false,
        ]);

        // 4. PROCEDIMIENTOS / REGLAS ASOCIADAS
        // Vinculamos procedimientos críticos con sus reglas del PDF
        
        // Hemoglobina (RC 31 - Obligatorio según CPT)
        M1ProcedimientoRegla::create([
            'prestacion_id' => $prestacion->id,
            'cpms_codigo' => '85018', // [cite: 111, 222]
            'denominacion' => 'HEMOGLOBINA',
            'es_obligatorio' => true, // Si se cumple la condición de diagnóstico de anemia
            'regla_asociada' => 'RC 31', // "Registro de resultados de hemoglobina obligatorios" [cite: 215, 216]
        ]);

        // Perímetro Abdominal (Obligatorio para incentivos)
        M1ProcedimientoRegla::create([
            'prestacion_id' => $prestacion->id,
            'cpms_codigo' => '015', // [cite: 44, 56]
            'denominacion' => 'PERIMETRO ABDOMINAL (PAB)',
            'es_obligatorio' => true, // Obligatorio adicionar registro [cite: 56]
            'regla_asociada' => 'RC 15', // Indicadores de incentivo
        ]);
        
        // Dosaje de Glucosa (Para Diabetes)
        M1ProcedimientoRegla::create([
            'prestacion_id' => $prestacion->id,
            'cpms_codigo' => '82947', // [cite: 109]
            'denominacion' => 'DOSAJE DE GLUCOSA EN SANGRE',
            'es_obligatorio' => false,
            'regla_asociada' => 'RC 12', // Debe haber al menos un apoyo al diagnóstico [cite: 194]
        ]);

        
        // 5. PROTOCOLOS DE ATENCIÓN (Relación CIE-10 -> Procedimientos/Medicamentos)
        $protocolos = [
            // --- CASO 1: ANEMIA (D509) ---
            [
                'cie10_codigo' => 'D509',
                'tipo' => 'LABORATORIO',
                'codigo_item' => '85018', // [cite: 111]
                'descripcion' => 'HEMOGLOBINA (Dosaje)',
                'es_obligatorio' => true,
                'regla_asociada' => 'RC-31 (Rango 1-30)', // [cite: 222, 238]
            ],
            [
                'cie10_codigo' => 'D509',
                'tipo' => 'MEDICAMENTO',
                'codigo_item' => 'SULF_FERR', // Código simulado
                'descripcion' => 'SULFATO FERROSO / HIERRO POLIMALTOSADO',
                'es_obligatorio' => true,
                'regla_asociada' => 'Tratamiento Anemia',
            ],

            // --- CASO 2: DIABETES (E119) ---
            [
                'cie10_codigo' => 'E119',
                'tipo' => 'LABORATORIO',
                'codigo_item' => '82947', // [cite: 109]
                'descripcion' => 'DOSAJE DE GLUCOSA (Cuantitativo)',
                'es_obligatorio' => false,
                'regla_asociada' => 'Apoyo al Diagnóstico',
            ],
            [
                'cie10_codigo' => 'E119',
                'tipo' => 'LABORATORIO',
                'codigo_item' => '82565', // [cite: 110]
                'descripcion' => 'CREATININA EN SANGRE',
                'es_obligatorio' => false,
                'regla_asociada' => 'Tamizaje Renal (Incentivo)',
            ],
            [
                'cie10_codigo' => 'E119',
                'tipo' => 'MEDICAMENTO',
                'codigo_item' => 'METFORMINA',
                'descripcion' => 'METFORMINA 850MG',
                'es_obligatorio' => false,
                'regla_asociada' => 'Tratamiento Habitual',
            ],

            // --- CASO 3: HIPERTENSIÓN (I10X) ---
            [
                'cie10_codigo' => 'I10X',
                'tipo' => 'PROCEDIMIENTO',
                'codigo_item' => '301', // [cite: 35]
                'descripcion' => 'MEDICIÓN DE PRESIÓN ARTERIAL',
                'es_obligatorio' => true,
                'regla_asociada' => 'SMI Obligatorio',
            ],
            [
                'cie10_codigo' => 'I10X',
                'tipo' => 'LABORATORIO',
                'codigo_item' => '80061',
                'descripcion' => 'PERFIL LIPÍDICO',
                'es_obligatorio' => false,
                'regla_asociada' => 'Control de Riesgo',
            ],

            // --- CASO 4: RESFRIADO (J00X) ---
            [
                'cie10_codigo' => 'J00X',
                'tipo' => 'MEDICAMENTO',
                'codigo_item' => 'PARACETAMOL',
                'descripcion' => 'PARACETAMOL 500MG',
                'es_obligatorio' => false,
                'regla_asociada' => 'Sintomático',
            ],
            // Nota importante: NO agregamos antibióticos aquí para no inducir error (RC-14) [cite: 87]
        ];

        foreach ($protocolos as $proto) {
            M1ProtocoloAtencion::create($proto);
        }



    }
}