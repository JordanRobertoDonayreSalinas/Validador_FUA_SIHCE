<?php

namespace App\Services;

use App\Models\FuaAtencionDetallado;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReglasValidacionService
{
    private $erroresEncontrados = 0;

    public function ejecutarValidacion()
    {
        $registros = FuaAtencionDetallado::all();
        $this->erroresEncontrados = 0;

        foreach ($registros as $fua) {
            $listaErrores = [];
            $listaSoluciones = [];

            // ============================================
            // REGLAS BÁSICAS (Existentes)
            // ============================================
            $this->validarDNI($fua, $listaErrores, $listaSoluciones);
            $this->validarSexoServicio($fua, $listaErrores, $listaSoluciones);
            $this->validarEdadPediatria($fua, $listaErrores, $listaSoluciones);

            // ============================================
            // FASE 1: REGLAS CRÍTICAS
            // ============================================
            $this->validarRC17_DuplicidadFUA($fua, $listaErrores, $listaSoluciones);
            $this->validarRC26_Extemporaneidad($fua, $listaErrores, $listaSoluciones);
            $this->validarRC13_ConsistenciaPrestacional($fua, $listaErrores, $listaSoluciones);

            // ============================================
            // FASE 2: REGLAS DE CONSISTENCIA DE DATOS
            // ============================================
            $this->validarRC06_EdadSegunPrestacion($fua, $listaErrores, $listaSoluciones);
            $this->validarRC09_SexoSegunDiagnostico($fua, $listaErrores, $listaSoluciones);
            $this->validarRC12_ObligatoriedadConsumos($fua, $listaErrores, $listaSoluciones);

            // ============================================
            // FASE 3: REGLAS DE ACTIVIDADES PREVENTIVAS
            // ============================================
            $this->validarRC14_ActividadesPreventivas($fua, $listaErrores, $listaSoluciones);
            $this->validarRC27_DiagnosticosPreventivas($fua, $listaErrores, $listaSoluciones);

            // ============================================
            // FASE 4: REGLAS DE PROFESIONAL Y PROCEDIMIENTOS
            // ============================================
            $this->validarRC20_ProcedimientosProfesional($fua, $listaErrores, $listaSoluciones);

            // ============================================
            // FASE 5: REGLAS DE REGISTRO (BÁSICAS)
            // ============================================
            $this->validarRR00_DatosBasicos($fua, $listaErrores, $listaSoluciones);
            $this->validarRR02_DatosAsegurado($fua, $listaErrores, $listaSoluciones);
            $this->validarRR03_Establecimiento($fua, $listaErrores, $listaSoluciones);
            $this->validarRR05_DatosProfesional($fua, $listaErrores, $listaSoluciones);
            $this->validarRR08_DatosProfesional($fua, $listaErrores, $listaSoluciones);
            $this->validarRR10_Procedimientos($fua, $listaErrores, $listaSoluciones);
            $this->validarRR82_Medicamentos($fua, $listaErrores, $listaSoluciones);

            // FASE EXTRA: TOPES Y MÁS CONSISTENCIA
            $this->validarRC04_TopesAtencion($fua, $listaErrores, $listaSoluciones);
            $this->validarRC25_AtencionesMaternas($fua, $listaErrores, $listaSoluciones);
            $this->validarRC28_AtencionRN($fua, $listaErrores, $listaSoluciones);
            $this->validarRC29_HorasAtencion($fua, $listaErrores, $listaSoluciones);
            $this->validarRC30_ProfesionalExtranjero($fua, $listaErrores, $listaSoluciones);
            $this->validarRC31_HistoriaClinica($fua, $listaErrores, $listaSoluciones);

            // FASE 6: REGLAS PENDIENTES (RC_01, RR_04, etc)
            $this->validarRC01_MedicamentosDiagnostico($fua, $listaErrores, $listaSoluciones);
            $this->validarRR04_HistoriaClinica($fua, $listaErrores, $listaSoluciones);
            $this->validarRV26_FechasLogicas($fua, $listaErrores, $listaSoluciones);
            $this->validarRV30_CondicionEstablecimiento($fua, $listaErrores, $listaSoluciones);


            // Guardar resultados
            $this->guardarResultadosValidacion($fua, $listaErrores, $listaSoluciones);
        }

        return $this->erroresEncontrados;
    }

    // ... (rest of methods)

    // ============================================
    // FASE 5: REGLAS DE REGISTRO
    // ============================================

    /**
     * RR_00: Identificación y vigencia de cobertura prestacional
     * OBJETIVO: Garantizar registro de datos básicos y cobertura vigente
     */
    private function validarRR00_DatosBasicos($fua, &$listaErrores, &$listaSoluciones)
    {
        // 1. Validar FUA ID
        if (empty($fua->fua_id)) {
            $listaErrores[] = "[RR_00] Falta Identificador Único de Atención (FUA ID)";
            $listaSoluciones[] = "Registrar el número de FUA correspondiente.";
        }

        // 2. Validar Documento de Identidad (General)
        if (empty($fua->num_doc_paciente)) {
            $listaErrores[] = "[RR_00] Falta número de documento del paciente";
            $listaSoluciones[] = "Registrar el documento de identidad del paciente.";
        }

        // 3. Validar Vigencia de Cobertura (Si fechas existen)
        if (!empty($fua->fecha_afiliacion) && !empty($fua->fecha_atencion)) {
            $fechaAtencion = \Carbon\Carbon::parse($fua->fecha_atencion);
            $fechaAfiliacion = \Carbon\Carbon::parse($fua->fecha_afiliacion);

            if ($fechaAtencion->lt($fechaAfiliacion)) {
                $listaErrores[] = "[RR_00] Fecha de atención anterior a fecha de afiliación";
                $listaSoluciones[] = "La atención no puede ser anterior a la afiliación. Verificar las fechas o el estado de aseguramiento.";
            }

            if (!empty($fua->fecha_baja)) {
                $fechaBaja = \Carbon\Carbon::parse($fua->fecha_baja);
                if ($fechaAtencion->gt($fechaBaja)) {
                    $listaErrores[] = "[RR_00] Fecha de atención posterior a fecha de baja";
                    $listaSoluciones[] = "El paciente no contaba con cobertura vigente en la fecha de atención. Verificar vigencia.";
                }
            }
        }
    }

    /**
     * RR_08: Validación de datos del profesional
     * OBJETIVO: Validar colegiatura y especialidad según tipo de profesional
     */
    private function validarRR08_DatosProfesional($fua, &$listaErrores, &$listaSoluciones)
    {
        $tipo = $fua->tipo_profesional; // Código del tipo de profesional

        // Mapa de requisitos por tipo (Basado en hoja RR_08)
        // 1: Médico, 2: Farmacéutico, 3: Odontólogo, 5: Obstetra, 6: Enfermera, etc.
        $requierenColegiatura = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '26'];
        $noRequierenColegiatura = ['11', '12', '13', '15', '16', '25', '27', '28'];
        // 11: Téc. Enf, 12: Aux. Enf, etc.

        // Validar Colegiatura Requerida
        if (in_array($tipo, $requierenColegiatura)) {
            if (empty($fua->colegiatura)) {
                $listaErrores[] = "[RR_08] Falta número de colegiatura para el profesional (Tipo {$tipo})";
                $listaSoluciones[] = "El tipo de profesional registrado requiere obligatoriamente número de colegiatura. Completar el dato.";
            }
        }

        // Validar Colegiatura Prohibida (Opcional, pero buena práctica de limpieza)
        if (in_array($tipo, $noRequierenColegiatura)) {
            if (!empty($fua->colegiatura)) {
                $listaErrores[] = "[RR_08] Número de colegiatura no corresponde al tipo de profesional (Tipo {$tipo})";
                $listaSoluciones[] = "Este tipo de profesional (Técnico/Auxiliar) no debe registrar colegiatura. Verificar el tipo de profesional o limpiar el campo.";
            }
        }

        // Validar Especialidad / RNE
        // Si tiene especialidad registrada, debe tener RNE (Regla general inferida)
        if (!empty($fua->especialidad) && empty($fua->rne)) {
            $listaErrores[] = "[RR_10] Falta Registro Nacional de Especialista (RNE)";
            $listaSoluciones[] = "Si se registra una especialidad, es obligatorio registrar el RNE correspondiente.";
        }
    }


    // ... (rest of methods)

    /**
     * RC_20: Procedimientos según Perfil Profesional
     * OBJETIVO: Garantizar que los procedimientos sean realizados por profesionales autorizados
     * ACCIÓN: Validar tipo de profesional para ciertos procedimientos críticos
     */
    private function validarRC20_ProcedimientosProfesional($fua, &$listaErrores, &$listaSoluciones)
    {
        $codPrestacion = $fua->cod_prestacion;
        $tipoProfesional = $fua->tipo_profesional; // Ej: 1 (Médico), 2 (Enfermera), 3 (Obstetra), etc.
        // Nota: Se asume codificación estándar del MINSA/SIS para tipo de profesional

        // Procedimientos exclusivos de MÉDICOS (Tipo 1)
        $procedimientosMedicos = [
            '055' => 'Cesárea',
            '064' => 'Intervención quirúrgica ambulatoria',
            '065' => 'Internamiento sin intervención',
            '066' => 'Internamiento con intervención menor',
            '067' => 'Internamiento con intervención mayor',
            '068' => 'UCI',
        ];

        // Procedimientos exclusivos de MÉDICOS (1) u OBSTETRAS (3)
        $procedimientosParto = [
            '054' => 'Atención de parto',
        ];

        // Validar Médicos
        if (isset($procedimientosMedicos[$codPrestacion])) {
            // Si el tipo de profesional NO es Médico (1)
            // Nota: Ajustar según la codificación real de la base de datos
            // Asumimos '1' = Médico, si es texto, ajustar 'MEDICO'
            if ($tipoProfesional != '1' && strpos(strtoupper($tipoProfesional), 'MEDICO') === false) {
                $listaErrores[] = "[RC_20] Profesional no autorizado para {$procedimientosMedicos[$codPrestacion]}";
                $listaSoluciones[] = "El código prestacional {$codPrestacion} ({$procedimientosMedicos[$codPrestacion]}) debe ser realizado exclusivamente por un MÉDICO. Verificar el tipo de profesional registrado.";
            }
        }

        // Validar Partos (Médico u Obstetra)
        if (isset($procedimientosParto[$codPrestacion])) {
            // Si NO es Médico (1) Y NO es Obstetra (3)
            $esMedico = ($tipoProfesional == '1' || strpos(strtoupper($tipoProfesional), 'MEDICO') !== false);
            $esObstetra = ($tipoProfesional == '3' || strpos(strtoupper($tipoProfesional), 'OBSTETRA') !== false);

            if (!$esMedico && !$esObstetra) {
                $listaErrores[] = "[RC_20] Profesional no autorizado para {$procedimientosParto[$codPrestacion]}";
                $listaSoluciones[] = "El código prestacional {$codPrestacion} ({$procedimientosParto[$codPrestacion]}) debe ser realizado por MÉDICO u OBSTETRA. Verificar el tipo de profesional registrado.";
            }
        }
    }


    // ============================================
    // REGLAS BÁSICAS
    // ============================================

    private function validarDNI($fua, &$listaErrores, &$listaSoluciones)
    {
        if ($fua->tipo_doc_paciente == '1' && strlen($fua->num_doc_paciente) != 8) {
            $listaErrores[] = '[RB_01] DNI Inválido (Longitud incorrecta)';
            $listaSoluciones[] = 'Verificar ficha RENIEC y corregir a 8 dígitos.';
        }
    }

    private function validarSexoServicio($fua, &$listaErrores, &$listaSoluciones)
    {
        if ($fua->sexo == 'M' && in_array($fua->servicio_descripcion, ['GINECOLOGIA', 'OBSTETRICIA'])) {
            $listaErrores[] = '[RB_02] Paciente MASCULINO en servicio Materno';
            $listaSoluciones[] = 'Cambiar servicio a UROLOGÍA o MEDICINA GENERAL.';
        }
    }

    private function validarEdadPediatria($fua, &$listaErrores, &$listaSoluciones)
    {
        if ($fua->edad > 18 && str_contains($fua->servicio_descripcion, 'PEDIATRIA')) {
            $listaErrores[] = '[RB_03] Mayor de edad en PEDIATRIA';
            $listaSoluciones[] = 'Derivar a MEDICINA ADULTO.';
        }
    }

    // ============================================
    // FASE 1: REGLAS CRÍTICAS
    // ============================================

    /**
     * RC_17: Control de Duplicidad de FUA
     * OBJETIVO: Evitar el registro duplicado de prestaciones
     * ACCIÓN: No permitir FUAs con el mismo fua_id
     */
    private function validarRC17_DuplicidadFUA($fua, &$listaErrores, &$listaSoluciones)
    {
        $duplicados = FuaAtencionDetallado::where('fua_id', $fua->fua_id)
            ->where('id', '!=', $fua->id)
            ->count();

        if ($duplicados > 0) {
            $listaErrores[] = '[RC_17] Número de FUA duplicado en el sistema';
            $listaSoluciones[] = 'Verificar el número de FUA. Este número ya existe en el sistema. Debe usar un número de FUA único.';
        }
    }

    /**
     * RC_26: Control de Extemporaneidad
     * OBJETIVO: Restringir el registro de prestaciones fuera de plazo
     * ACCIÓN: Validar que no exceda los días permitidos según nivel
     * - Nivel I y II: máximo 45 días calendario
     * - Nivel III: máximo 30 días calendario
     */
    private function validarRC26_Extemporaneidad($fua, &$listaErrores, &$listaSoluciones)
    {
        if (!$fua->fecha_atencion || !$fua->fecha_digitacion) {
            return; // No se puede validar sin fechas
        }

        $fechaAtencion = Carbon::parse($fua->fecha_atencion);
        $fechaDigitacion = Carbon::parse($fua->fecha_digitacion);
        $diasTranscurridos = $fechaAtencion->diffInDays($fechaDigitacion);

        // Determinar plazo según nivel
        $nivel = $fua->nivel_establecimiento ?? 'I';
        $plazoMaximo = ($nivel == 'III') ? 30 : 45;

        if ($diasTranscurridos > $plazoMaximo) {
            $listaErrores[] = "[RC_26] FUA extemporáneo: {$diasTranscurridos} días (máximo {$plazoMaximo} días para nivel {$nivel})";
            $listaSoluciones[] = "El FUA debe registrarse dentro de los {$plazoMaximo} días calendario desde la fecha de atención. Solicitar autorización a la GREP para FUAs extemporáneos.";
        }
    }

    /**
     * RC_13: Consistencia de Registro según Código Prestacional
     * OBJETIVO: Evitar el registro duplicado de atención de parto/cesárea en períodos no permitidos
     * ACCIÓN: Validar topes por código prestacional
     */
    private function validarRC13_ConsistenciaPrestacional($fua, &$listaErrores, &$listaSoluciones)
    {
        $codPrestacion = $fua->cod_prestacion;

        // Definir reglas de topes
        $reglasTopesPrestacion = [
            '054' => ['nombre' => 'Atención de parto', 'tope_meses' => 6, 'codigos_relacionados' => ['054', '055']],
            '055' => ['nombre' => 'Cesárea', 'tope_meses' => 6, 'codigos_relacionados' => ['054', '055']],
            '050' => ['nombre' => 'Atención inmediata del RN normal', 'tope_anio' => 1, 'tope_mes' => 1, 'tope_dia' => 1],
            '112' => ['nombre' => 'Sepelio para óbito fetal', 'tope_anio' => 2, 'tope_mes' => 2, 'tope_dia' => 2],
            '116' => ['nombre' => 'Sepelio para recién nacido', 'tope_anio' => 1, 'tope_mes' => 1, 'tope_dia' => 1],
            '113' => ['nombre' => 'Sepelio para niños', 'tope_anio' => 1, 'tope_mes' => 1, 'tope_dia' => 1],
            '114' => ['nombre' => 'Sepelio para adolescentes y adultos', 'tope_anio' => 1, 'tope_mes' => 1, 'tope_dia' => 1],
        ];

        if (!isset($reglasTopesPrestacion[$codPrestacion])) {
            return; // No aplica esta regla para este código
        }

        $regla = $reglasTopesPrestacion[$codPrestacion];

        // Validar partos y cesáreas (códigos 054 y 055)
        if (in_array($codPrestacion, ['054', '055'])) {
            $this->validarTopePartosCesareas($fua, $regla, $listaErrores, $listaSoluciones);
        }

        // Validar atención RN y sepelios (códigos 050, 112-114, 116)
        if (in_array($codPrestacion, ['050', '112', '113', '114', '116'])) {
            $this->validarTopesAnuales($fua, $regla, $listaErrores, $listaSoluciones);
        }

        // --- NUEVA VALIDACIÓN: Consistencia de Diagnósticos (Parto/Cesárea) ---
        // Diagnósticos CIE-10 que indican Parto (O80-O84)
        if ($codPrestacion == '054') { // Atención de parto
            $tieneDiagnosticoParto = false;
            $diagnosticos = [$fua->diagnostico_motivo_consulta, $fua->diagnostico_definitivo, $fua->diagnostico_repetitivo];

            foreach ($diagnosticos as $d) {
                if (empty($d))
                    continue;
                $code = substr($d, 0, 3);
                // Aceptamos O80, O81, O83, O84
                if (in_array($code, ['O80', 'O81', 'O83', 'O84'])) {
                    $tieneDiagnosticoParto = true;
                    break;
                }
            }

            if (!$tieneDiagnosticoParto) {
                $listaErrores[] = "[RC_13] Código prestacional 054 (Parto) requiere diagnóstico de parto vaginal (O80-O84)";
                $listaSoluciones[] = "Para el código 054, registre un diagnóstico CIE-10 de parto vaginal (O80, O81, O83, O84).";
            }

        } elseif ($codPrestacion == '055') { // Cesárea
            $tieneDiagnosticoCesarea = false;
            $diagnosticos = [$fua->diagnostico_motivo_consulta, $fua->diagnostico_definitivo, $fua->diagnostico_repetitivo];

            foreach ($diagnosticos as $d) {
                if (empty($d))
                    continue;
                $code = substr($d, 0, 3);
                if ($code == 'O82') {
                    $tieneDiagnosticoCesarea = true;
                    break;
                }
            }

            if (!$tieneDiagnosticoCesarea) {
                $listaErrores[] = "[RC_13] Código prestacional 055 (Cesárea) requiere diagnóstico O82 (Parto por cesárea)";
                $listaSoluciones[] = "Para el código 055, es obligatorio el diagnóstico O82.";
            }
        }
    }

    /**
     * Validar topes de partos y cesáreas (6 meses)
     */
    private function validarTopePartosCesareas($fua, $regla, &$listaErrores, &$listaSoluciones)
    {
        if (!$fua->fecha_atencion || !$fua->num_doc_paciente) {
            return;
        }

        $fechaActual = Carbon::parse($fua->fecha_atencion);
        $fechaLimite = $fechaActual->copy()->subMonths($regla['tope_meses']);

        // Buscar atenciones previas de parto o cesárea en los últimos 6 meses
        $atencionesPrevia = FuaAtencionDetallado::where('num_doc_paciente', $fua->num_doc_paciente)
            ->whereIn('cod_prestacion', $regla['codigos_relacionados'])
            ->where('fecha_atencion', '>=', $fechaLimite)
            ->where('fecha_atencion', '<', $fechaActual)
            ->where('id', '!=', $fua->id)
            ->orderBy('fecha_atencion', 'desc')
            ->first();

        if ($atencionesPrevia) {
            $fechaPrevia = Carbon::parse($atencionesPrevia->fecha_atencion);
            $mesesTranscurridos = $fechaPrevia->diffInMonths($fechaActual);

            $listaErrores[] = "[RC_13] Registro duplicado de {$regla['nombre']}: última atención hace {$mesesTranscurridos} meses (mínimo 6 meses)";
            $listaSoluciones[] = "No se permite registrar {$regla['nombre']} si existe una atención previa de parto o cesárea en los últimos 6 meses. Verificar historial de la paciente.";
        }
    }

    /**
     * Validar topes anuales (atención RN, sepelios)
     */
    private function validarTopesAnuales($fua, $regla, &$listaErrores, &$listaSoluciones)
    {
        if (!$fua->fecha_atencion || !$fua->num_doc_paciente) {
            return;
        }

        $anioActual = Carbon::parse($fua->fecha_atencion)->year;
        $mesActual = Carbon::parse($fua->fecha_atencion)->month;
        $diaActual = Carbon::parse($fua->fecha_atencion)->day;

        // Contar atenciones en el año actual
        $conteoAnio = FuaAtencionDetallado::where('num_doc_paciente', $fua->num_doc_paciente)
            ->where('cod_prestacion', $fua->cod_prestacion)
            ->whereYear('fecha_atencion', $anioActual)
            ->where('id', '!=', $fua->id)
            ->count();

        if ($conteoAnio >= $regla['tope_anio']) {
            $listaErrores[] = "[RC_13] Tope excedido para {$regla['nombre']}: {$conteoAnio}/{$regla['tope_anio']} por año";
            $listaSoluciones[] = "El código prestacional {$fua->cod_prestacion} ({$regla['nombre']}) tiene un tope de {$regla['tope_anio']} atención(es) por año. Verificar si corresponde a otro paciente.";
        }
    }

    // ============================================
    // GUARDAR RESULTADOS
    // ============================================

    private function guardarResultadosValidacion($fua, $listaErrores, $listaSoluciones)
    {
        if (count($listaErrores) > 0) {
            $fua->estado_validacion = 2; // ROJO
            $fua->observaciones_reglas = implode(' | ', $listaErrores);
            $fua->soluciones_reglas = implode(' | ', $listaSoluciones);
            $this->erroresEncontrados++;
        } else {
            $fua->estado_validacion = 1; // VERDE
            $fua->observaciones_reglas = 'Conforme';
            $fua->soluciones_reglas = null;
        }

        $fua->save();
    }

    // ============================================
    // FASE 2: REGLAS DE CONSISTENCIA DE DATOS
    // ============================================

    /**
     * RC_06: Validación de Edad según Prestación
     * OBJETIVO: Garantizar que las prestaciones se registren dentro del rango de edad permitido
     * ACCIÓN: Validar rangos de edad por código prestacional
     */
    private function validarRC06_EdadSegunPrestacion($fua, &$listaErrores, &$listaSoluciones)
    {
        // Definir rangos de edad por código prestacional
        $rangosPrestacion = [
            '001' => ['nombre' => 'Control CRED 0-4 años', 'edad_min' => 0, 'edad_max' => 4],
            '002' => ['nombre' => 'Control RN < 2,500 gr', 'edad_min' => 0, 'edad_max' => 0],
            '118' => ['nombre' => 'Control CRED 5-9 años', 'edad_min' => 5, 'edad_max' => 9],
            '119' => ['nombre' => 'Control CRED 10-12 años', 'edad_min' => 10, 'edad_max' => 12],
            '017' => ['nombre' => 'Atención integral del adolescente', 'edad_min' => 12, 'edad_max' => 17],
            '009' => ['nombre' => 'Atención prenatal', 'edad_min' => 10, 'edad_max' => 54],
            '054' => ['nombre' => 'Atención de parto', 'edad_min' => 10, 'edad_max' => 54],
            '055' => ['nombre' => 'Cesárea', 'edad_min' => 10, 'edad_max' => 54],
            '050' => ['nombre' => 'Atención inmediata RN', 'edad_min' => 0, 'edad_max' => 0],
            '023' => ['nombre' => 'Detección cáncer de próstata', 'edad_min' => 50, 'edad_max' => 120],
            '024' => ['nombre' => 'Detección cáncer cérvico-uterino', 'edad_min' => 30, 'edad_max' => 64],
            '903' => ['nombre' => 'Atención integral adulto mayor', 'edad_min' => 60, 'edad_max' => 120],
            '904' => ['nombre' => 'Atención integral joven/adulto', 'edad_min' => 18, 'edad_max' => 59],
        ];

        $codPrestacion = $fua->cod_prestacion;

        if (!isset($rangosPrestacion[$codPrestacion])) {
            return; // No aplica validación para este código
        }

        $rango = $rangosPrestacion[$codPrestacion];
        $edadPaciente = $fua->edad_anios ?? 0;

        if ($edadPaciente < $rango['edad_min'] || $edadPaciente > $rango['edad_max']) {
            $listaErrores[] = "[RC_06] Edad fuera de rango para {$rango['nombre']}: {$edadPaciente} años (permitido: {$rango['edad_min']}-{$rango['edad_max']} años)";
            $listaSoluciones[] = "El código prestacional {$codPrestacion} ({$rango['nombre']}) requiere que el paciente tenga entre {$rango['edad_min']} y {$rango['edad_max']} años. Verificar edad del paciente o cambiar código prestacional.";
        }
    }

    /**
     * RC_09: Validación de Sexo según Diagnóstico
     * OBJETIVO: Evitar diagnósticos incompatibles con el sexo del paciente
     * ACCIÓN: Validar que diagnósticos específicos de género coincidan con el sexo registrado
     */
    private function validarRC09_SexoSegunDiagnostico($fua, &$listaErrores, &$listaSoluciones)
    {
        $sexo = $fua->sexo;
        $diagnosticos = [
            $fua->diagnostico_motivo_consulta,
            $fua->diagnostico_definitivo,
            $fua->diagnostico_repetitivo
        ];

        // Diagnósticos exclusivos de MUJERES (Capítulo XV: Embarazo, parto y puerperio)
        $diagnosticosMujeres = [
            // Embarazo
            'O00',
            'O01',
            'O02',
            'O03',
            'O04',
            'O05',
            'O06',
            'O07',
            'O08',
            'O10',
            'O11',
            'O12',
            'O13',
            'O14',
            'O15',
            'O16',
            'O20',
            'O21',
            'O22',
            'O23',
            'O24',
            'O25',
            'O26',
            'O28',
            'O29',
            'O30',
            'O31',
            'O32',
            'O33',
            'O34',
            'O35',
            'O36',
            'O40',
            'O41',
            'O42',
            'O43',
            'O44',
            'O45',
            'O46',
            'O47',
            'O48',
            // Parto
            'O60',
            'O61',
            'O62',
            'O63',
            'O64',
            'O65',
            'O66',
            'O67',
            'O68',
            'O69',
            'O70',
            'O71',
            'O72',
            'O73',
            'O74',
            'O75',
            'O80',
            'O81',
            'O82',
            'O83',
            'O84',
            // Puerperio
            'O85',
            'O86',
            'O87',
            'O88',
            'O89',
            'O90',
            'O91',
            'O92',
            'O94',
            'O95',
            'O96',
            'O97',
            'O98',
            'O99',
            // Ginecología
            'N70',
            'N71',
            'N72',
            'N73',
            'N74',
            'N75',
            'N76',
            'N77',
            'N80',
            'N81',
            'N82',
            'N83',
            'N84',
            'N85',
            'N86',
            'N87',
            'N88',
            'N89',
            'N90',
            'N91',
            'N92',
            'N93',
            'N94',
            'N95',
            'N96',
            'N97',
            'N98',
            'N99',
            'Z30',
            'Z31',
            'Z32',
            'Z33',
            'Z34',
            'Z35',
            'Z36',
            'Z37',
            'Z38',
            'Z39',
        ];

        // Diagnósticos exclusivos de HOMBRES
        $diagnosticosHombres = [
            // Próstata
            'N40',
            'N41',
            'N42',
            'C61', // Cáncer de próstata
            // Testículos
            'N43',
            'N44',
            'N45',
            'N46',
            'N47',
            'N48',
            'N49',
            'N50',
            'N51',
            'C62', // Cáncer de testículo
        ];

        foreach ($diagnosticos as $diagnostico) {
            if (empty($diagnostico))
                continue;

            $codigoDx = substr($diagnostico, 0, 3); // Primeros 3 caracteres

            // Validar diagnósticos de mujeres en hombres
            if ($sexo == 'M' && in_array($codigoDx, $diagnosticosMujeres)) {
                $listaErrores[] = "[RC_09] Diagnóstico incompatible con sexo MASCULINO: {$diagnostico}";
                $listaSoluciones[] = "El diagnóstico {$diagnostico} es exclusivo para pacientes de sexo FEMENINO. Verificar el sexo del paciente o corregir el diagnóstico.";
                break; // Solo reportar una vez
            }

            // Validar diagnósticos de hombres en mujeres
            if ($sexo == 'F' && in_array($codigoDx, $diagnosticosHombres)) {
                $listaErrores[] = "[RC_09] Diagnóstico incompatible con sexo FEMENINO: {$diagnostico}";
                $listaSoluciones[] = "El diagnóstico {$diagnostico} es exclusivo para pacientes de sexo MASCULINO. Verificar el sexo del paciente o corregir el diagnóstico.";
                break; // Solo reportar una vez
            }
        }
    }





    /**
     * RR_02: Validación de Datos del Asegurado
     * OBJETIVO: Verificar formato de documento de identidad
     */
    private function validarRR02_DatosAsegurado($fua, &$listaErrores, &$listaSoluciones)
    {
        $tipoDoc = $fua->tipo_doc_paciente;
        $numDoc = $fua->num_doc_paciente;

        // Normalizar tipo de documento
        // 1=DNI, 3=CE

        if ($tipoDoc == '1' || strtoupper($tipoDoc) == 'DNI') {
            if (strlen($numDoc) != 8 || !is_numeric($numDoc)) {
                $listaErrores[] = "[RR_02] Formato de DNI inválido ({$numDoc})";
                $listaSoluciones[] = "El DNI debe tener exactamente 8 dígitos numéricos.";
            }
        } elseif ($tipoDoc == '3' || strtoupper($tipoDoc) == 'CE') {
            if (strlen($numDoc) < 9) {
                $listaErrores[] = "[RR_02] Formato de CE inválido ({$numDoc})";
                $listaSoluciones[] = "El Carnet de Extranjería suele tener al menos 9 caracteres.";
            }
        }
    }

    /**
     * RR_03: Validación datos del establecimiento
     * OBJETIVO: Verificar código RENIPRESS y nivel de atención
     */
    private function validarRR03_Establecimiento($fua, &$listaErrores, &$listaSoluciones)
    {
        // Validar Código RENIPRESS (Extraído del FUA ID)
        // Formato FUA: 00003361-25-00059073 (CodEst-Anio-Correlativo)
        $fuaId = $fua->fua_id;
        $partes = explode('-', $fuaId);

        if (count($partes) >= 3) {
            $codRenipress = intval($partes[0]); // "00003361" -> 3361
            if ($codRenipress <= 0) {
                $listaErrores[] = "[RR_03] Código RENIPRESS inválido en FUA ID ({$partes[0]})";
                $listaSoluciones[] = "El primer segmento del FUA ID debe ser el código RENIPRESS válido del establecimiento.";
            }
        } else {
            $listaErrores[] = "[RR_03] Formato de FUA ID incorrecto ({$fuaId})";
            $listaSoluciones[] = "El FUA ID debe seguir el formato CodEst-Anio-Correlativo (Ej: 00003361-25-00059073).";
        }

        // Validar Nivel (I-1, I-2, I-3, I-4, II-1, II-2, etc.)
        $nivelesValidos = ['I-1', 'I-2', 'I-3', 'I-4', 'II-1', 'II-2', 'III-1', 'III-2', 'I', 'II', 'III']; // Simplificado
        if (!empty($fua->nivel_establecimiento) && !in_array($fua->nivel_establecimiento, $nivelesValidos)) {
            // Opcional: solo advertencia si el formato es distinto
            // $listaErrores[] = "[RR_03] Nivel de establecimiento no estándar ({$fua->nivel_establecimiento})";
        }
    }

    /**
     * RR_05: Validación datos del profesional
     * OBJETIVO: Verificar documento de identidad del profesional
     */
    private function validarRR05_DatosProfesional($fua, &$listaErrores, &$listaSoluciones)
    {
        // Validar DNI del profesional (Asumiendo que es DNI por defecto si no hay tipo)
        // Validar DNI del profesional (Asumiendo que es DNI por defecto si no hay tipo)
        // O si hay tipo de doc profesional, usarlo.
        // En FUA estándar: dni_resp_atencion

        $docProf = $fua->dni_resp_atencion;
        if (!empty($docProf)) {
            // Si es DNI (longitud 8 y numérico)
            if (strlen($docProf) == 8 && is_numeric($docProf)) {
                // OK
            } elseif (strlen($docProf) >= 9) {
                // OK (Posible CE o CMP)
            } else {
                $listaErrores[] = "[RR_05] Documento del profesional con formato extraño ({$docProf})";
                $listaSoluciones[] = "Verificar el número de documento del profesional. DNI debe tener 8 dígitos.";
            }
        } else {
            $listaErrores[] = "[RR_05] Falta documento de identidad del profesional";
            $listaSoluciones[] = "Es obligatorio registrar el documento del profesional responsable.";
        }
    }

    /**
     * RC_04: Topes de atenciones (Genérico)
     * OBJETIVO: Controlar frecuencia de atenciones por paciente
     */
    private function validarRC04_TopesAtencion($fua, &$listaErrores, &$listaSoluciones)
    {
        // Ejemplo: Consultas externas tope 12 al año si no es crónico (Simulado)
        $codPrestacion = $fua->cod_prestacion;

        // Configuración de topes (Ejemplo)
        $topes = [
            '001' => 12, // Cred: 12 al año
            '056' => 4,  // Consulta externa: 4 al año (ejemplo restrictivo)
        ];

        if (isset($topes[$codPrestacion]) && !empty($fua->fecha_atencion) && !empty($fua->num_doc_paciente)) {
            $anio = \Carbon\Carbon::parse($fua->fecha_atencion)->year;
            $count = FuaAtencionDetallado::where('num_doc_paciente', $fua->num_doc_paciente)
                ->where('cod_prestacion', $codPrestacion)
                ->whereYear('fecha_atencion', $anio)
                ->count();

            if ($count > $topes[$codPrestacion]) {
                // $listaErrores[] = "[RC_04] Excede tope anual de atenciones ({$count}/{$topes[$codPrestacion]})";
                // Comentado para no generar falsos positivos sin reglas de negocio exactas
            }
        }
    }

    /**
     * RR_10: Validación de Procedimientos
     * OBJETIVO: Verificar formato de CPMS/CIE-10 en consumos
     */
    private function validarRR10_Procedimientos($fua, &$listaErrores, &$listaSoluciones)
    {
        foreach ($fua->consumos as $consumo) {
            $cpms = $consumo->cpms;
            if (!empty($cpms)) {
                // Validar longitud (CPMS suelen ser 5 dígitos)
                if (strlen($cpms) != 5 && strlen($cpms) != 6) { // A veces incluye letra
                    //$listaErrores[] = "[RR_10] Formato de procedimiento inválido ({$cpms})";
                }
            }
        }
    }

    /**
     * RR_82: Validación de Medicamentos
     * OBJETIVO: Verificar formato de códigos SISMED
     */
    private function validarRR82_Medicamentos($fua, &$listaErrores, &$listaSoluciones)
    {
        foreach ($fua->consumos as $consumo) {
            $codMed = $consumo->cod_medicamento;
            if (!empty($codMed)) {
                // SISMED son 5 dígitos numéricos
                if (strlen($codMed) != 5 || !is_numeric($codMed)) {
                    $listaErrores[] = "[RR_82] Código SISMED inválido ({$codMed})";
                    $listaSoluciones[] = "El código de medicamento debe tener 5 dígitos numéricos.";
                }
            }
        }
    }

    /**
     * RC_01: Medicamentos por Diagnóstico
     * OBJETIVO: Coherencia clínica (Ejemplo simplificado)
     */
    private function validarRC01_MedicamentosDiagnostico($fua, &$listaErrores, &$listaSoluciones)
    {
        // Ejemplo simplificado: Si hay infección (J00-J99), debería haber antibiótico o analgésico
        // Como no tenemos catálogo, validamos genéricamente que existan consumos si es una atención recuperativa

        $tipoAtencion = $fua->tipo_atencion;
        // 056: Consulta Externa
        if ($tipoAtencion == '056' && $fua->consumos->isEmpty()) {
            // Es sospechoso que una consulta no tenga NINGÚN consumo (ni procedimientos ni medicamentos)
            // $listaErrores[] = "[RC_01] Consulta externa sin consumos registrados"; // Comentado para evitar ruido
        }
    }



    /**
     * RC_25: Atenciones Maternas
     * OBJETIVO: Validar consitencia en atenciones de gestantes
     */
    private function validarRC25_AtencionesMaternas($fua, &$listaErrores, &$listaSoluciones)
    {
        // Si está marcada como gestante y el código no es materno (simplificado)
        if (isset($fua->gestante) && $fua->gestante == '1') {
            // Lógica pendiente de códigos exactos
        }
    }

    /**
     * RC_28: Atención Recién Nacido
     * OBJETIVO: Validar consistencia en atención RN
     */
    private function validarRC28_AtencionRN($fua, &$listaErrores, &$listaSoluciones)
    {
        // Código 050 (Atención inmediata RN) requiere edad < 1 día
        if ($fua->cod_prestacion == '050') {
            // Validar edad en días/horas si es posible
        }
    }

    /**
     * RC_29: Validación de Horas
     * OBJETIVO: Que la hora de atención no sea futura ni absurda
     */
    private function validarRC29_HorasAtencion($fua, &$listaErrores, &$listaSoluciones)
    {
        // Validar que fecha_registro >= fecha_atencion
        if (!empty($fua->fecha_registro) && !empty($fua->fecha_atencion)) {
            if ($fua->fecha_registro < $fua->fecha_atencion->format('Y-m-d')) {
                $listaErrores[] = "[RC_29] Fecha de registro anterior a fecha de atención";
                $listaSoluciones[] = "La fecha de registro no puede ser anterior a la atención.";
            }
        }
    }

    /**
     * RC_30: Documento Profesional Extranjero
     * OBJETIVO: Validar formato si es CE
     */
    private function validarRC30_ProfesionalExtranjero($fua, &$listaErrores, &$listaSoluciones)
    {
        // Si tipo profesional es CE (3), validar longitud
        // Ya cubierto parcialmente en RR_05, pero reforzamos
    }

    /**
     * RR_04: Historia Clínica (Formato)
     * OBJETIVO: Que sea numérico o alfanumérico consistente
     */
    private function validarRR04_HistoriaClinica($fua, &$listaErrores, &$listaSoluciones)
    {
        // HC suele ser DNI o correlativo.
        // Si tiene menos de 4 o más de 15 caracteres, advertir.
        if (!empty($fua->historia_clinica)) {
            if (strlen($fua->historia_clinica) < 4 || strlen($fua->historia_clinica) > 15) {
                // $listaErrores[] = "[RR_04] Historia Clínica con longitud inusual ({$fua->historia_clinica})";
            }
        }
    }

    /**
     * RV_26: Consistencia de Fechas
     * OBJETIVO: Validar fechas lógicas (nacimiento <= atencion)
     */
    private function validarRV26_FechasLogicas($fua, &$listaErrores, &$listaSoluciones)
    {
        if (!empty($fua->fecha_nacimiento_paciente) && !empty($fua->fecha_atencion)) {
            if ($fua->fecha_nacimiento_paciente > $fua->fecha_atencion) {
                $listaErrores[] = "[RV_26] Fecha de nacimiento posterior a fecha de atención";
                $listaSoluciones[] = "Corregir fecha de nacimiento o fecha de atención.";
            }
        }
    }

    /**
     * RV_30: Condición de Establecimiento
     * OBJETIVO: Verificar si estado es 'ACTIVO'
     */
    private function validarRV30_CondicionEstablecimiento($fua, &$listaErrores, &$listaSoluciones)
    {
        // Requiere tabla de establecimientos. 
        // Placeholder: validar que el código no sea nulo (ya cubierto en RR_03)
    }

    /**
     * RC_31: Formato de Historia Clínica
     * OBJETIVO: Validar existencia de HC
     */
    private function validarRC31_HistoriaClinica($fua, &$listaErrores, &$listaSoluciones)
    {
        if (empty($fua->historia_clinica)) {
            $listaErrores[] = "[RC_31] Falta Historia Clínica";
            $listaSoluciones[] = "Es obligatorio registrar el número de historia clínica.";
        }
    }

    // ============================================
    // FASE 3: REGLAS DE ACTIVIDADES PREVENTIVAS
    // ============================================

    /**
     * RC_14: Obligatoriedad de Actividades Preventivas
     * OBJETIVO: Garantizar que las prestaciones preventivas registren actividades obligatorias
     * ACCIÓN: Validar peso, talla, presión arterial, vacunas según código prestacional
     */


    /**
     * RC_27: Diagnósticos Obligatorios en Prestaciones Preventivas
     * OBJETIVO: Asegurar que las prestaciones preventivas registren diagnósticos consistentes
     * ACCIÓN: Validar presencia de diagnósticos Z (factores que influyen en el estado de salud)
     */
    private function validarRC27_DiagnosticosPreventivas($fua, &$listaErrores, &$listaSoluciones)
    {
        $codPrestacion = $fua->cod_prestacion;

        // NUEVA REGLA (Solicitada por usuario): Consistencia Servicio 056 vs Diagnóstico Preventivo Z000
        if ($codPrestacion == '056') {
            $diagnosticos = [
                $fua->diagnostico_motivo_consulta,
                $fua->diagnostico_definitivo,
                $fua->diagnostico_repetitivo
            ];
            foreach ($diagnosticos as $d) {
                if (!empty($d) && substr($d, 0, 4) == 'Z000') {
                    $listaErrores[] = "[RC_27] Inconsistencia: Diagnóstico preventivo Z000 en Consulta Externa (056)";
                    $listaSoluciones[] = "El diagnóstico Z000 (Examen médico general) es preventivo. Debería registrarse en una Atención Integral (Ej: 305 o 903 para adulto mayor) y no en Consulta Externa (056) que es para morbilidad.";
                }
            }
        }

        // Códigos prestacionales preventivos que requieren diagnósticos específicos
        $prestacionesPreventivas = [
            '001' => [ // Control CRED 0-4 años
                'nombre' => 'Control CRED 0-4 años',
                'diagnosticos_requeridos' => ['Z001', 'Z002'],
                'mensaje' => 'Z001 (Control de salud de rutina del niño) o Z002 (Examen de rutina del niño)'
            ],
            '002' => [ // Control RN < 2,500 gr
                'nombre' => 'Control RN < 2,500 gr',
                'diagnosticos_requeridos' => ['Z001', 'Z002', 'Z38'], // Z38x simplificado
                'mensaje' => 'Z001, Z002 o Z38X (Nacidos vivos)'
            ],
            '118' => [ // Control CRED 5-9 años
                'nombre' => 'Control CRED 5-9 años',
                'diagnosticos_requeridos' => ['Z001', 'Z008'],
                'mensaje' => 'Z001 o Z008 (Examen de salud general)'
            ],
            '119' => [ // Control CRED 10-12 años
                'nombre' => 'Control CRED 10-12 años',
                'diagnosticos_requeridos' => ['Z001', 'Z008'],
                'mensaje' => 'Z001 o Z008 (Examen de salud general)'
            ],
            '009' => [ // Atención prenatal
                'nombre' => 'Atención prenatal',
                'diagnosticos_requeridos' => ['Z34', 'Z35'], // Z34x, Z35x simplificado
                'mensaje' => 'Z34X (Supervisión de embarazo normal) o Z35X (Supervisión de embarazo de alto riesgo)'
            ],
            '017' => [ // Atención integral adolescente
                'nombre' => 'Atención integral adolescente',
                'diagnosticos_requeridos' => ['Z008', 'Z003'],
                'mensaje' => 'Z008 (Examen de salud general) o Z003 (Examen de salud de rutina)'
            ],
            '023' => [ // Detección cáncer de próstata
                'nombre' => 'Detección cáncer de próstata',
                'diagnosticos_requeridos' => ['Z121'],
                'mensaje' => 'Z121 (Examen de detección de cáncer de próstata)'
            ],
            '024' => [ // Detección cáncer cérvico-uterino
                'nombre' => 'Detección cáncer cérvico-uterino',
                'diagnosticos_requeridos' => ['Z120', 'Z121'],
                'mensaje' => 'Z120 (Examen de detección de cáncer cérvico-uterino)'
            ],
            '903' => [ // Atención integral adulto mayor
                'nombre' => 'Atención integral adulto mayor',
                'diagnosticos_requeridos' => ['Z008'],
                'mensaje' => 'Z008 (Examen de salud general)'
            ],
            '904' => [ // Atención integral joven/adulto
                'nombre' => 'Atención integral joven/adulto',
                'diagnosticos_requeridos' => ['Z008', 'Z003'],
                'mensaje' => 'Z008 (Examen de salud general) o Z003 (Examen de salud de rutina)'
            ],
        ];

        if (!isset($prestacionesPreventivas[$codPrestacion])) {
            return; // No es una prestación preventiva
        }

        $prestacion = $prestacionesPreventivas[$codPrestacion];

        // Obtener todos los diagnósticos del FUA
        $diagnosticos = [
            $fua->diagnostico_motivo_consulta,
            $fua->diagnostico_definitivo,
            $fua->diagnostico_repetitivo
        ];

        // Verificar si al menos uno de los diagnósticos requeridos está presente
        $tieneDiagnosticoRequerido = false;
        foreach ($diagnosticos as $diagnostico) {
            if (empty($diagnostico))
                continue;

            // Comparar códigos
            // Para Z34 y Z35 (Prenatal), comparar primeros 3
            // Para otros, comparar primeros 4 o 3 según config
            $codigo3 = substr($diagnostico, 0, 3);
            $codigo4 = substr($diagnostico, 0, 4);

            if (
                in_array($codigo4, $prestacion['diagnosticos_requeridos']) ||
                in_array($codigo3, $prestacion['diagnosticos_requeridos'])
            ) {
                $tieneDiagnosticoRequerido = true;
                break;
            }
        }

        if (!$tieneDiagnosticoRequerido) {
            $listaErrores[] = "[RC_27] Falta diagnóstico preventivo obligatorio para {$prestacion['nombre']}";
            $listaSoluciones[] = "El código prestacional {$codPrestacion} ({$prestacion['nombre']}) requiere al menos uno de los siguientes diagnósticos: {$prestacion['mensaje']}. Registrar el diagnóstico correspondiente.";
        }
    }

    /**
     * RC_12: Obligatoriedad de Consumos según Código Prestacional
     * OBJETIVO: Garantizar que las prestaciones registren medicamentos/procedimientos cuando son obligatorios
     * ACCIÓN: Validar existencia de consumos según código prestacional usando la relación 'consumos'
     */
    private function validarRC12_ObligatoriedadConsumos($fua, &$listaErrores, &$listaSoluciones)
    {
        // Códigos prestacionales que requieren consumos obligatorios
        $prestacionesConConsumoObligatorio = [
            '009' => ['nombre' => 'Atención prenatal', 'requiere' => 'medicamentos o procedimientos'],
            '054' => ['nombre' => 'Atención de parto', 'requiere' => 'medicamentos y procedimientos'],
            '055' => ['nombre' => 'Cesárea', 'requiere' => 'medicamentos y procedimientos'],
            '056' => ['nombre' => 'Consulta externa', 'requiere' => 'al menos uno'],
            '062' => ['nombre' => 'Atención por emergencia', 'requiere' => 'al menos uno'],
            '063' => ['nombre' => 'Emergencia con observación', 'requiere' => 'medicamentos y procedimientos'],
            '064' => ['nombre' => 'Intervención médico-quirúrgica ambulatoria', 'requiere' => 'medicamentos y procedimientos'],
            '065' => ['nombre' => 'Internamiento sin intervención quirúrgica', 'requiere' => 'medicamentos y procedimientos'],
            '066' => ['nombre' => 'Internamiento con intervención quirúrgica menor', 'requiere' => 'medicamentos y procedimientos'],
            '067' => ['nombre' => 'Internamiento con intervención quirúrgica mayor', 'requiere' => 'medicamentos y procedimientos'],
            '068' => ['nombre' => 'Internamiento con estancia en UCI', 'requiere' => 'medicamentos y procedimientos'],
        ];

        $codPrestacion = $fua->cod_prestacion;

        if (!isset($prestacionesConConsumoObligatorio[$codPrestacion])) {
            return; // No requiere validación de consumos
        }

        $prestacion = $prestacionesConConsumoObligatorio[$codPrestacion];

        // Verificar si existen consumos registrados usando la relación
        // Importante: Asegurarse de cargar la relación 'consumos' previamente para evitar N+1 queries si es posible
        // $consumos = $fua->consumos; 
        // Si no está cargada, Eloquent la cargará ahora (lazy loading)

        $tieneMedicamentos = false;
        $tieneProcedimientos = false;

        foreach ($fua->consumos as $consumo) {
            if (!empty($consumo->cod_medicamento) || !empty($consumo->cod_insumo)) {
                $tieneMedicamentos = true;
            }
            if (!empty($consumo->cpms)) {
                $tieneProcedimientos = true;
            }
            if ($tieneMedicamentos && $tieneProcedimientos)
                break; // Ya tenemos ambos
        }

        $requiere = $prestacion['requiere'];

        if ($requiere == 'medicamentos y procedimientos') {
            if (!$tieneMedicamentos && !$tieneProcedimientos) {
                $listaErrores[] = "[RC_12] Falta registro de consumos para {$prestacion['nombre']}: requiere medicamentos Y procedimientos";
                $listaSoluciones[] = "El código prestacional {$codPrestacion} ({$prestacion['nombre']}) requiere el registro obligatorio de medicamentos/insumos Y procedimientos/apoyo diagnóstico. Registrar los consumos correspondientes.";
            } elseif (!$tieneMedicamentos) {
                $listaErrores[] = "[RC_12] Falta registro de medicamentos para {$prestacion['nombre']}";
                $listaSoluciones[] = "El código prestacional {$codPrestacion} ({$prestacion['nombre']}) requiere el registro de medicamentos o insumos. Registrar los consumos correspondientes.";
            } elseif (!$tieneProcedimientos) {
                $listaErrores[] = "[RC_12] Falta registro de procedimientos para {$prestacion['nombre']}";
                $listaSoluciones[] = "El código prestacional {$codPrestacion} ({$prestacion['nombre']}) requiere el registro de procedimientos o apoyo diagnóstico. Registrar los consumos correspondientes.";
            }
        } elseif ($requiere == 'al menos uno' || $requiere == 'medicamentos o procedimientos') {

            // Excepción específica para Consulta Externa (056) solicitada por usuario
            if ($codPrestacion == '056') {
                // Códigos CIE-10 que PERMITEN no tener consumos (Excepciones)
                $diagnosticosExcepcion = ['J00', 'A09', 'Z35']; // J00=Rinofa, A09=Diarrea, Z35=Alto Riesgo

                $dxPrincipal = substr($fua->diagnostico_motivo_consulta, 0, 3);

                // Si el diagnóstico NO es una excepción y no tiene consumos -> ERROR
                if (!in_array($dxPrincipal, $diagnosticosExcepcion) && !$tieneMedicamentos && !$tieneProcedimientos) {
                    $listaErrores[] = "[RC_12] Falta registro de consumos para {$prestacion['nombre']}";
                    $listaSoluciones[] = "El código 056 requiere obligatoriamente medicamentos o procedimientos, salvo para diagnósticos J00, A09 o Z35. El diagnóstico registrado ({$fua->diagnostico_motivo_consulta}) no es una excepción.";
                }
            } else {
                // Lógica estándar para otros códigos
                if (!$tieneMedicamentos && !$tieneProcedimientos) {
                    $listaErrores[] = "[RC_12] Falta registro de consumos para {$prestacion['nombre']}: requiere al menos medicamentos O procedimientos";
                    $listaSoluciones[] = "El código prestacional {$codPrestacion} ({$prestacion['nombre']}) requiere el registro de al menos medicamentos/insumos O procedimientos/apoyo diagnóstico. Registrar los consumos correspondientes.";
                }
            }
        }
    }

    // ============================================
    // FASE 3: REGLAS DE ACTIVIDADES PREVENTIVAS
    // ============================================

    /**
     * RC_14: Obligatoriedad de Actividades Preventivas
     * OBJETIVO: Garantizar que las prestaciones preventivas registren actividades obligatorias
     * ACCIÓN: Validar peso, talla, presión arterial, vacunas según código prestacional (tabla SMI)
     */
    private function validarRC14_ActividadesPreventivas($fua, &$listaErrores, &$listaSoluciones)
    {
        $codPrestacion = $fua->cod_prestacion;

        // Mapa de códigos SMI
        $codigosSMI = [
            'peso' => '003',
            'talla' => '004',
            'edad_gestacional_semanas' => '005',
            'altura_uterina' => '010',
            'imc' => '014',
            'pab' => '015',
            'presion_arterial' => '301', // Sistólica y/o Diastólica
            'apgar_1min' => '305',
            'apgar_5min' => '306',
        ];

        // Definir actividades obligatorias por código prestacional
        $actividadesObligatorias = [
            '001' => [ // Control CRED 0-4 años
                'nombre' => 'Control CRED 0-4 años',
                'campos' => ['peso', 'talla'],
                'mensaje' => 'peso y talla'
            ],
            '002' => [ // Control RN < 2,500 gr
                'nombre' => 'Control RN < 2,500 gr',
                'campos' => ['peso', 'talla'],
                'mensaje' => 'peso y talla'
            ],
            '118' => [ // Control CRED 5-9 años
                'nombre' => 'Control CRED 5-9 años',
                'campos' => ['peso', 'talla'],
                'mensaje' => 'peso y talla'
            ],
            '119' => [ // Control CRED 10-12 años
                'nombre' => 'Control CRED 10-12 años',
                'campos' => ['peso', 'talla'],
                'mensaje' => 'peso y talla'
            ],
            '009' => [ // Atención prenatal
                'nombre' => 'Atención prenatal',
                'campos' => ['peso', 'talla', 'edad_gestacional_semanas', 'presion_arterial'],
                'mensaje' => 'peso, talla, edad gestacional y presión arterial'
            ],
            '054' => [ // Atención de parto
                'nombre' => 'Atención de parto',
                'campos' => ['peso', 'talla', 'edad_gestacional_semanas', 'presion_arterial'],
                'mensaje' => 'peso, talla, edad gestacional y presión arterial'
            ],
            '055' => [ // Cesárea
                'nombre' => 'Cesárea',
                'campos' => ['peso', 'talla', 'edad_gestacional_semanas', 'presion_arterial'],
                'mensaje' => 'peso, talla, edad gestacional y presión arterial'
            ],
            '050' => [ // Atención inmediata RN
                'nombre' => 'Atención inmediata RN',
                'campos' => ['peso', 'talla', 'edad_gestacional_semanas', 'apgar_1min', 'apgar_5min'],
                'mensaje' => 'peso, talla, edad gestacional y APGAR'
            ],
            '017' => [ // Atención integral adolescente
                'nombre' => 'Atención integral adolescente',
                'campos' => ['peso', 'talla', 'imc', 'presion_arterial'],
                'mensaje' => 'peso, talla, IMC y presión arterial'
            ],
            '903' => [ // Atención integral adulto mayor
                'nombre' => 'Atención integral adulto mayor',
                'campos' => ['peso', 'talla', 'presion_arterial'],
                'mensaje' => 'peso, talla y presión arterial'
            ],
            '904' => [ // Atención integral joven/adulto
                'nombre' => 'Atención integral joven/adulto',
                'campos' => ['peso', 'talla', 'presion_arterial'],
                'mensaje' => 'peso, talla y presión arterial'
            ],
        ];

        if (!isset($actividadesObligatorias[$codPrestacion])) {
            return; // No requiere actividades preventivas
        }

        $actividad = $actividadesObligatorias[$codPrestacion];

        // Verificar si los campos existen en la tabla SMI
        // Obtenemos todos los códigos SMI registrados para este FUA
        $smiRegistrados = $fua->smi->pluck('cod_smi')->toArray();
        // Normalizar a string por si acaso
        $smiRegistrados = array_map('strval', $smiRegistrados);

        $camposFaltantes = [];
        foreach ($actividad['campos'] as $campoNombre) {
            $codigoRequerido = $codigosSMI[$campoNombre] ?? null;
            if ($codigoRequerido && !in_array((string) $codigoRequerido, $smiRegistrados)) {
                // Caso especial: Presión arterial puede venir como 301, o a veces 302/303 si se registra separado (aunque el estándar suele ser 301)
                // Si no está, lo marcamos como faltante
                $camposFaltantes[] = $campoNombre;
            }
        }

        if (count($camposFaltantes) > 0) {
            $listaErrores[] = "[RC_14] Faltan actividades preventivas para {$actividad['nombre']}: {$actividad['mensaje']}";
            $listaSoluciones[] = "El código prestacional {$codPrestacion} ({$actividad['nombre']}) requiere el registro obligatorio de {$actividad['mensaje']} en la sección SMI. Asegurarse de registrar todas las actividades preventivas correspondientes.";
        }
    }
}