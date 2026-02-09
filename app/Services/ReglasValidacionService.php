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
            $this->validarRC06_LimitesMedicamentos($fua, $listaErrores, $listaSoluciones); // NUEVO
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

            // ============================================
            // FASE 7: REGLAS CRÍTICAS ADICIONALES (PHASE 1)
            // ============================================
            $this->validarRC32_LimitesMedicamentos($fua, $listaErrores, $listaSoluciones);
            $this->validarRC33_LimitesOxigeno($fua, $listaErrores, $listaSoluciones);
            $this->validarRC34_DestinoAsegurado($fua, $listaErrores, $listaSoluciones);
            $this->validarRC35_ConsistenciaPrestaciones($fua, $listaErrores, $listaSoluciones);

            // ============================================
            // FASE 8: REGLAS ADICIONALES (PHASE 2)
            // ============================================
            $this->validarRR82_ControlLotesCalendario($fua, $listaErrores, $listaSoluciones);

            // ============================================
            // FASE 9: REGLAS PRÁCTICAS ADICIONALES (PHASE 3)
            // ============================================
            $this->validarRC_CamposObligatorios($fua, $listaErrores, $listaSoluciones);
            $this->validarRC_RangosValores($fua, $listaErrores, $listaSoluciones);
            $this->validarRC_CoherenciaTemporal($fua, $listaErrores, $listaSoluciones);

            // ============================================
            // FASE 10: REGLAS ADICIONALES (PHASE 4)
            // ============================================
            $this->validarRC_DiagnosticoPrestacion($fua, $listaErrores, $listaSoluciones);
            $this->validarRC_LimitesProcedimientos($fua, $listaErrores, $listaSoluciones);
            $this->validarRC_CalificacionProfesional($fua, $listaErrores, $listaSoluciones);
            $this->validarRC_LimitesInsumos($fua, $listaErrores, $listaSoluciones);

            // ============================================
            // FASE 11: REGLAS FINALES (PHASE 5)
            // ============================================
            $this->validarRC_Medicamentos($fua, $listaErrores, $listaSoluciones);
            $this->validarRC_Emergencia($fua, $listaErrores, $listaSoluciones);
            $this->validarRC_Hospitalizacion($fua, $listaErrores, $listaSoluciones);
            $this->validarRC_Neonatal($fua, $listaErrores, $listaSoluciones);

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
        $codPrestacion = $fua->id_servicio;
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
        $codPrestacion = $fua->id_servicio;

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
            ->whereIn('id_servicio', $regla['codigos_relacionados'])
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
            ->where('id_servicio', $fua->id_servicio)
            ->whereYear('fecha_atencion', $anioActual)
            ->where('id', '!=', $fua->id)
            ->count();

        if ($conteoAnio >= $regla['tope_anio']) {
            $listaErrores[] = "[RC_13] Tope excedido para {$regla['nombre']}: {$conteoAnio}/{$regla['tope_anio']} por año";
            $listaSoluciones[] = "El código prestacional {$fua->id_servicio} ({$regla['nombre']}) tiene un tope de {$regla['tope_anio']} atención(es) por año. Verificar si corresponde a otro paciente.";
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

        $codPrestacion = $fua->id_servicio;

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
     * RC_06: Límites de Medicamentos por Forma Farmacéutica
     * OBJETIVO: Validar que las cantidades de medicamentos no excedan los topes permitidos
     * FUENTE: RC_06_final_limpio.csv, RC_06_2_final_limpio.csv, RC_06_3_final_limpio.csv
     * EXCEPCIONES: 19 excepciones documentadas en RC_06_excepciones.txt
     */
    private function validarRC06_LimitesMedicamentos($fua, &$listaErrores, &$listaSoluciones)
    {
        static $reglas = null;
        
        // Cargar reglas desde CSV (solo una vez)
        if ($reglas === null) {
            $reglas = [];
            $archivos = ['RC_06_final_limpio.csv', 'RC_06_2_final_limpio.csv', 'RC_06_3_final_limpio.csv'];
            foreach ($archivos as $archivo) {
                $datos = $this->cargarCSV($archivo);
                if (!empty($datos)) {
                    $reglas = array_merge($reglas, $datos);
                }
            }
        }
        
        if (empty($reglas) || empty($fua->consumos)) {
            return;
        }
        
        // Determinar nivel de EESS (asumiendo que está en el modelo)
        $nivelEESS = $fua->nivel_eess ?? 'I'; // Por defecto nivel I
        $esHospitalizado = !empty($fua->hospitalizado) && $fua->hospitalizado == '1';
        
        // Agrupar consumos por forma farmacéutica
        $consumosPorForma = [];
        foreach ($fua->consumos as $consumo) {
            if (empty($consumo->forma_farmaceutica)) {
                continue;
            }
            
            $forma = strtoupper(trim($consumo->forma_farmaceutica));
            $cantidad = (int) ($consumo->cantidad ?? 0);
            $codigoSISMED = $consumo->codigo_sismed ?? '';
            
            if (!isset($consumosPorForma[$forma])) {
                $consumosPorForma[$forma] = [
                    'cantidad_total' => 0,
                    'items' => []
                ];
            }
            
            $consumosPorForma[$forma]['cantidad_total'] += $cantidad;
            $consumosPorForma[$forma]['items'][] = [
                'codigo' => $codigoSISMED,
                'cantidad' => $cantidad,
                'consumo' => $consumo
            ];
        }
        
        // Validar cada forma farmacéutica
        foreach ($consumosPorForma as $forma => $datos) {
            // Buscar regla para esta forma farmacéutica y nivel
            $regla = null;
            foreach ($reglas as $r) {
                if (isset($r['FORMA TERAPEUTICA']) && strtoupper(trim($r['FORMA TERAPEUTICA'])) == $forma) {
                    // Verificar si aplica al nivel de EESS
                    $nivelesPermitidos = $r['NIVEL DE EESS'] ?? '';
                    if (empty($nivelesPermitidos) || strpos($nivelesPermitidos, $nivelEESS) !== false) {
                        $regla = $r;
                        break;
                    }
                }
            }
            
            if (!$regla) {
                continue; // No hay regla para esta forma
            }
            
            // Determinar tope máximo según hospitalización
            $topeMaximo = $esHospitalizado 
                ? (int) ($regla['MAXIMO_HOSPITALIZADOS'] ?? 999) 
                : (int) ($regla['MAXIMO_NO HOSPITALIZADOS'] ?? 999);
            
            $cantidadTotal = $datos['cantidad_total'];
            
            // ============================================
            // APLICAR EXCEPCIONES
            // ============================================
            
            // Excepción 1: Coberturas Extraordinarias (Ley 29344, D.S. 007-2012-SA)
            // No aplica esta regla - requiere verificación externa
            
            // Excepción 5: Unidades de consumo vs compra
            $usaUnidadesConsumo = $this->verificarUnidadesConsumo($datos['items']);
            if ($usaUnidadesConsumo) {
                $topeMaximo = $esHospitalizado ? 9999 : 999;
                // Excepto para líquidos y gases
                if (in_array($forma, ['LIQ', 'GAS', 'JBE'])) {
                    $topeMaximo = $esHospitalizado 
                        ? (int) ($regla['MAXIMO_HOSPITALIZADOS'] ?? 999) 
                        : (int) ($regla['MAXIMO_NO HOSPITALIZADOS'] ?? 999);
                }
            }
            
            // Excepción 6: Códigos SISMED específicos (38862-38867)
            $codigosSISMEDEspeciales = ['38862', '38863', '38864', '38865', '38866', '38867'];
            foreach ($datos['items'] as $item) {
                if (in_array($item['codigo'], $codigosSISMEDEspeciales)) {
                    $topeMaximo = max($topeMaximo, 999);
                }
            }
            
            // Excepción 7: Diagnóstico N18 con códigos SISMED específicos
            if ($this->tieneDiagnostico($fua, 'N18')) {
                $codigosSISMEDN18 = ['26512', '26513', '26514', '26515', '26516', '26517'];
                foreach ($datos['items'] as $item) {
                    if (in_array($item['codigo'], $codigosSISMEDN18)) {
                        $topeMaximo = max($topeMaximo, 124);
                    }
                }
            }
            
            // Excepción 9: Diagnósticos L40* (hasta 04 cremas tópicas)
            if ($this->tieneDiagnostico($fua, 'L40') && $forma == 'CRM') {
                $topeMaximo = max($topeMaximo, 4);
            }
            
            // Excepción 12: Carbamazepina 200mg para G40.X (hasta 810 tabletas)
            if ($this->tieneDiagnostico($fua, 'G40') && $forma == 'TAB') {
                foreach ($datos['items'] as $item) {
                    if (strpos(strtolower($item['consumo']->nombre ?? ''), 'carbamazepina') !== false) {
                        $topeMaximo = max($topeMaximo, 810);
                    }
                }
            }
            
            // Excepción 14: 180 tabletas para especialistas con RNE
            if (!$esHospitalizado && $forma == 'TAB') {
                $profesionalEspecialista = !empty($fua->rne_profesional);
                if ($profesionalEspecialista) {
                    $topeMaximo = max($topeMaximo, 180);
                }
            }
            
            // Excepción 19: Diagnósticos E10-E14, I10-I15, N18-N19 (hasta 270 tabletas)
            $diagnosticosCronicos = ['E10', 'E11', 'E12', 'E13', 'E14', 'I10', 'I11', 'I12', 'I13', 'I14', 'I15', 'N18', 'N19'];
            foreach ($diagnosticosCronicos as $dx) {
                if ($this->tieneDiagnostico($fua, $dx) && $forma == 'TAB') {
                    $topeMaximo = max($topeMaximo, 270);
                    break;
                }
            }
            
            // Validar contra el tope
            if ($cantidadTotal > $topeMaximo) {
                $descripcionForma = $regla['DESCRIPCION'] ?? $forma;
                $listaErrores[] = "[RC_06] Cantidad de {$descripcionForma} excede el tope: {$cantidadTotal} unidades (máximo: {$topeMaximo})";
                $listaSoluciones[] = "La forma farmacéutica {$forma} tiene un tope de {$topeMaximo} unidades para " . 
                    ($esHospitalizado ? "hospitalizados" : "no hospitalizados") . 
                    " en nivel {$nivelEESS}. Verificar las cantidades registradas o justificar el exceso.";
            }
        }
    }

    /**
     * Verificar si un diagnóstico está presente (búsqueda por prefijo)
     */
    private function tieneDiagnostico($fua, $prefijo)
    {
        $diagnosticos = [
            $fua->diagnostico_motivo_consulta,
            $fua->diagnostico_definitivo,
            $fua->diagnostico_repetitivo
        ];
        
        foreach ($diagnosticos as $dx) {
            if (!empty($dx) && substr($dx, 0, strlen($prefijo)) == $prefijo) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Verificar si usa unidades de consumo (simplificado)
     */
    private function verificarUnidadesConsumo($items)
    {
        // Esta es una simplificación - en producción debería verificar contra lista oficial
        // Por ahora retornamos false
        return false;
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
        $codPrestacion = $fua->id_servicio;

        // Configuración de topes (Ejemplo)
        $topes = [
            '001' => 12, // Cred: 12 al año
            '056' => 4,  // Consulta externa: 4 al año (ejemplo restrictivo)
        ];

        if (isset($topes[$codPrestacion]) && !empty($fua->fecha_atencion) && !empty($fua->num_doc_paciente)) {
            $anio = \Carbon\Carbon::parse($fua->fecha_atencion)->year;
            $count = FuaAtencionDetallado::where('num_doc_paciente', $fua->num_doc_paciente)
                ->where('id_servicio', $codPrestacion)
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
    /**
     * RC_01: Validación Completa de Códigos Prestacionales
     * OBJETIVO: Validar edad, sexo, hospitalización y estado gestante/puérpera según código prestacional
     * FUENTE: RC_01_final_limpio.csv (74 códigos prestacionales)
     */
    private function validarRC01_MedicamentosDiagnostico($fua, &$listaErrores, &$listaSoluciones)
    {
        static $reglas = null;
        
        // Cargar reglas desde CSV (solo una vez)
        if ($reglas === null) {
            $reglas = $this->cargarCSV('RC_01_final_limpio.csv');
        }
        
        if (empty($reglas)) {
            return; // No se pudo cargar el archivo
        }
        
        $codPrestacion = $fua->id_servicio;
        
        // Buscar regla para este código prestacional
        $regla = null;
        foreach ($reglas as $r) {
            if (isset($r['COD_PRESTACIONAL']) && $r['COD_PRESTACIONAL'] == $codPrestacion) {
                $regla = $r;
                break;
            }
        }
        
        if (!$regla) {
            return; // No hay regla para este código
        }
        
        // ============================================
        // 1. VALIDAR EDAD
        // ============================================
        if (!empty($fua->fecha_nacimiento) && !empty($fua->fecha_atencion)) {
            // Para hospitalizados: EDAD = (FECHA_INGRESO) - (FECHA_NACIMIENTO)
            $fechaReferencia = $fua->fecha_atencion;
            if (!empty($fua->fecha_ingreso) && $regla['HOSPITALIZACION'] == 'S') {
                $fechaReferencia = $fua->fecha_ingreso;
            }
            
            $edad = $this->calcularEdadDetallada($fua->fecha_nacimiento, $fechaReferencia);
            
            // Parsear edad mínima y máxima
            $edadMin = $this->parsearEdadMaxima($regla['EDAD_MINIMA'] ?? '');
            $edadMax = $this->parsearEdadMaxima($regla['EDAD_MAXIMA'] ?? '');
            
            // Validar edad mínima
            if ($edadMin) {
                $cumpleMin = false;
                if ($edadMin['tipo'] == 'anios' && $edad['anios'] >= $edadMin['anios']) {
                    $cumpleMin = true;
                } elseif ($edadMin['tipo'] == 'meses' && $edad['meses'] >= $edadMin['meses']) {
                    $cumpleMin = true;
                } elseif ($edadMin['tipo'] == 'dias' && $edad['dias'] >= $edadMin['dias']) {
                    $cumpleMin = true;
                }
                
                if (!$cumpleMin) {
                    $listaErrores[] = "[RC_01] Edad menor al rango permitido para {$regla['PRESTACIONES']}: {$edad['anios']} años (mínimo: {$regla['EDAD_MINIMA']})";
                    $listaSoluciones[] = "El código prestacional {$codPrestacion} requiere edad mínima de {$regla['EDAD_MINIMA']}. Verificar edad del paciente o cambiar código prestacional.";
                }
            }
            
            // Validar edad máxima (aplicando operativización: < edad_max + 1)
            if ($edadMax) {
                $cumpleMax = false;
                if ($edadMax['tipo'] == 'anios' && $edad['anios'] < ($edadMax['anios'] + 1)) {
                    $cumpleMax = true;
                } elseif ($edadMax['tipo'] == 'meses' && $edad['meses'] < ($edadMax['meses'] + 1)) {
                    $cumpleMax = true;
                } elseif ($edadMax['tipo'] == 'dias' && $edad['dias'] < ($edadMax['dias'] + 1)) {
                    $cumpleMax = true;
                }
                
                if (!$cumpleMax) {
                    $listaErrores[] = "[RC_01] Edad excede el rango permitido para {$regla['PRESTACIONES']}: {$edad['anios']} años (máximo: {$regla['EDAD_MAXIMA']})";
                    $listaSoluciones[] = "El código prestacional {$codPrestacion} requiere edad máxima de {$regla['EDAD_MAXIMA']}. Verificar edad del paciente o cambiar código prestacional.";
                }
            }
        }
        
        // ============================================
        // 2. VALIDAR SEXO
        // ============================================
        $sexoRequerido = $regla['SEXO'] ?? 'A';
        if ($sexoRequerido != 'A' && !empty($fua->sexo)) {
            if ($sexoRequerido == 'M' && $fua->sexo != 'M') {
                $listaErrores[] = "[RC_01] Sexo incorrecto para {$regla['PRESTACIONES']}: requiere MASCULINO";
                $listaSoluciones[] = "El código prestacional {$codPrestacion} es exclusivo para pacientes de sexo masculino.";
            } elseif ($sexoRequerido == 'F' && $fua->sexo != 'F') {
                $listaErrores[] = "[RC_01] Sexo incorrecto para {$regla['PRESTACIONES']}: requiere FEMENINO";
                $listaSoluciones[] = "El código prestacional {$codPrestacion} es exclusivo para pacientes de sexo femenino.";
            }
        }
        
        // ============================================
        // 3. VALIDAR HOSPITALIZACIÓN
        // ============================================
        $requiereHospitalizacion = $regla['HOSPITALIZACION'] ?? 'N';
        
        // Excepción especial: CIE-10 O800, O8000, O8001, O801, O808, O809, O840, O841, O842, O848, O849
        $diagnosticosHospitalizacionEspecial = ['O800', 'O8000', 'O8001', 'O801', 'O808', 'O809', 'O840', 'O841', 'O842', 'O848', 'O849'];
        $tieneDxEspecial = false;
        $diagnosticos = [$fua->diagnostico_motivo_consulta, $fua->diagnostico_definitivo, $fua->diagnostico_repetitivo];
        foreach ($diagnosticos as $dx) {
            if (in_array($dx, $diagnosticosHospitalizacionEspecial)) {
                $tieneDxEspecial = true;
                break;
            }
        }
        
        if ($tieneDxEspecial) {
            $requiereHospitalizacion = 'S';
        }
        
        if ($requiereHospitalizacion == 'S') {
            // Debe estar marcado como hospitalizado
            if (empty($fua->hospitalizado) || $fua->hospitalizado != '1') {
                $listaErrores[] = "[RC_01] Falta marcar como hospitalizado para {$regla['PRESTACIONES']}";
                $listaSoluciones[] = "El código prestacional {$codPrestacion} requiere que el paciente esté hospitalizado. Marcar el campo de hospitalización.";
            }
        }
        
        // ============================================
        // 4. VALIDAR ESTADO GESTANTE/PUÉRPERA
        // ============================================
        $requiereGestante = $regla['GESTANTE'] ?? 'N';
        $requierePuerpera = $regla['PUERPERA'] ?? 'N';
        $requiereNoGestanteNoPuerpera = $regla['NO_GESTANTE_NO_PUERPERA'] ?? 'N';
        
        if ($requiereGestante == 'S') {
            if (empty($fua->gestante) || $fua->gestante != '1') {
                $listaErrores[] = "[RC_01] Falta marcar como gestante para {$regla['PRESTACIONES']}";
                $listaSoluciones[] = "El código prestacional {$codPrestacion} requiere que la paciente esté marcada como gestante.";
            }
        }
        
        if ($requierePuerpera == 'S') {
            if (empty($fua->puerpera) || $fua->puerpera != '1') {
                $listaErrores[] = "[RC_01] Falta marcar como puérpera para {$regla['PRESTACIONES']}";
                $listaSoluciones[] = "El código prestacional {$codPrestacion} requiere que la paciente esté marcada como puérpera.";
            }
        }
        
        if ($requiereNoGestanteNoPuerpera == 'S') {
            if ((!empty($fua->gestante) && $fua->gestante == '1') || (!empty($fua->puerpera) && $fua->puerpera == '1')) {
                $listaErrores[] = "[RC_01] Paciente no debe estar gestante ni puérpera para {$regla['PRESTACIONES']}";
                $listaSoluciones[] = "El código prestacional {$codPrestacion} requiere que la paciente NO esté gestante ni puérpera.";
            }
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
        if ($fua->id_servicio == '050') {
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
        $codPrestacion = $fua->id_servicio;

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

        $codPrestacion = $fua->id_servicio;

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
        $codPrestacion = $fua->id_servicio;

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

    // ============================================
    // FASE 7: REGLAS CRÍTICAS ADICIONALES (PHASE 1)
    // ============================================

    /**
     * RC_32: Límites de registro de medicamentos por unidad de medida
     * OBJETIVO: Establecer límites de registro de medicamentos por unidad de medida
     * considerando hospitalizados, no hospitalizados según nivel de atención
     * Fuente: Reglas_de_auditoria_automatizada_20251216.xlsx - RC_06
     */
    private function validarRC32_LimitesMedicamentos($fua, &$listaErrores, &$listaSoluciones)
    {
        // Verificar si hay consumos de medicamentos
        if (!$fua->consumos || $fua->consumos->count() == 0) {
            return;
        }

        $codPrestacion = $fua->id_servicio;
        $nivelEESS = $fua->nivel_establecimiento ?? 'I'; // I, II, III
        
        // Determinar si es hospitalizado
        $prestacionesHospitalizadas = ['051', '052', '054', '055', '065', '066', '067', '068'];
        $esHospitalizado = in_array($codPrestacion, $prestacionesHospitalizadas);

        // Topes por forma farmacéutica (simplificado - los más comunes)
        // Formato: 'forma' => ['no_hosp' => max, 'hosp' => max, 'nivel' => 'I,II,III']
        $topesMedicamentos = [
            'TAB' => ['no_hosp' => 120, 'hosp' => 120, 'nivel_I_II' => 120, 'nivel_III' => 370],
            'CAP' => ['no_hosp' => 180, 'hosp' => 90],
            'INY' => ['nivel_I' => 50, 'nivel_II' => 100, 'nivel_III' => 400],
            'JBE' => ['nivel_I' => 4, 'nivel_II' => 16, 'nivel_III' => 16, 'hosp_I' => 6, 'hosp_II' => 16, 'hosp_III' => 16],
            'SOL' => ['no_hosp' => 100, 'hosp' => 40],
            'CRM' => ['no_hosp_I_II' => 2, 'no_hosp_III' => 6, 'hosp' => 5],
            'GEL' => ['no_hosp' => 2, 'hosp' => 5],
            'SUS' => ['no_hosp' => 4, 'hosp' => 6],
            'OVU' => ['no_hosp' => 20, 'hosp' => 20],
            'SUPOS' => ['no_hosp' => 28, 'hosp' => 10],
            'AER' => ['no_hosp' => 4, 'hosp' => 4],
            'FCO' => ['no_hosp' => 4, 'hosp' => 6],
        ];

        // Obtener diagnósticos para verificar excepciones
        $diagnosticos = [
            $fua->diagnostico_motivo_consulta,
            $fua->diagnostico_definitivo,
            $fua->diagnostico_repetitivo
        ];

        // Verificar si aplica excepción HEARTS (Hipertensión, Diabetes, Enfermedad Renal)
        $esHEARTS = false;
        foreach ($diagnosticos as $dx) {
            if (empty($dx)) continue;
            $codigo = substr($dx, 0, 3);
            // I10-I15 (Hipertensión), E10-E14 (Diabetes), N18-N19 (Enfermedad Renal)
            if (($codigo >= 'I10' && $codigo <= 'I15') ||
                ($codigo >= 'E10' && $codigo <= 'E14') ||
                ($codigo >= 'N18' && $codigo <= 'N19')) {
                $esHEARTS = true;
                break;
            }
        }

        // Procesar cada consumo de medicamento
        foreach ($fua->consumos as $consumo) {
            // Solo validar medicamentos (tipo_consumo = 'medicamento' o similar)
            if (empty($consumo->forma_farmaceutica) || empty($consumo->cantidad)) {
                continue;
            }

            $forma = strtoupper($consumo->forma_farmaceutica);
            $cantidad = (int) $consumo->cantidad;

            // Determinar tope máximo según forma farmacéutica
            $topeMaximo = null;

            if (isset($topesMedicamentos[$forma])) {
                $topes = $topesMedicamentos[$forma];

                // Caso especial: TAB con excepción HEARTS
                if ($forma == 'TAB' && $esHEARTS && !$esHospitalizado && in_array($nivelEESS, ['I', 'II'])) {
                    $topeMaximo = 270; // Excepción HEARTS
                } 
                // Caso especial: INY por nivel
                elseif ($forma == 'INY') {
                    if ($nivelEESS == 'I') $topeMaximo = $topes['nivel_I'];
                    elseif ($nivelEESS == 'II') $topeMaximo = $topes['nivel_II'];
                    elseif ($nivelEESS == 'III') $topeMaximo = $topes['nivel_III'];
                }
                // Caso especial: JBE por nivel y hospitalización
                elseif ($forma == 'JBE') {
                    if ($esHospitalizado) {
                        if ($nivelEESS == 'I') $topeMaximo = $topes['hosp_I'];
                        else $topeMaximo = $topes['hosp_II']; // II y III usan mismo tope
                    } else {
                        if ($nivelEESS == 'I') $topeMaximo = $topes['nivel_I'];
                        else $topeMaximo = $topes['nivel_II']; // II y III usan mismo tope
                    }
                }
                // Caso especial: CRM por nivel
                elseif ($forma == 'CRM') {
                    if ($esHospitalizado) {
                        $topeMaximo = $topes['hosp'];
                    } else {
                        $topeMaximo = ($nivelEESS == 'III') ? $topes['no_hosp_III'] : $topes['no_hosp_I_II'];
                    }
                }
                // Caso especial: TAB por nivel
                elseif ($forma == 'TAB') {
                    if ($nivelEESS == 'III') {
                        $topeMaximo = $topes['nivel_III'];
                    } else {
                        $topeMaximo = $topes['nivel_I_II'];
                    }
                }
                // Casos generales
                elseif (isset($topes['no_hosp']) && isset($topes['hosp'])) {
                    $topeMaximo = $esHospitalizado ? $topes['hosp'] : $topes['no_hosp'];
                }
            }

            // Validar si excede el tope
            if ($topeMaximo !== null && $cantidad > $topeMaximo) {
                $tipoAtencion = $esHospitalizado ? 'hospitalizado' : 'no hospitalizado';
                $listaErrores[] = "[RC_32] Medicamento {$consumo->nombre_medicamento} ({$forma}) excede el tope máximo: {$cantidad} unidades registradas, máximo permitido: {$topeMaximo} para {$tipoAtencion} en nivel {$nivelEESS}";
                $listaSoluciones[] = "Verificar la cantidad registrada del medicamento. El tope máximo para {$forma} en {$tipoAtencion} (nivel {$nivelEESS}) es de {$topeMaximo} unidades. Ajustar la cantidad o verificar si aplica alguna excepción documentada.";
            }
        }
    }

    /**
     * RC_33: Prestaciones en las que se puede brindar oxígeno y usar accesorios para bombas de infusión
     * OBJETIVO: Establecer límites de registro de oxígeno y accesorios de bomba de infusión
     * Fuente: Reglas_de_auditoria_automatizada_20251216.xlsx - RC_09
     */
    private function validarRC33_LimitesOxigeno($fua, &$listaErrores, &$listaSoluciones)
    {
        // Verificar si hay consumos
        if (!$fua->consumos || $fua->consumos->count() == 0) {
            return;
        }

        $codPrestacion = $fua->id_servicio;

        // Códigos SISMED de oxígeno (según documentación)
        $codigosOxigeno = ['08140', '41434', '44648', '44221', '44529', '44222', '22291'];
        
        // Códigos SISMED de accesorios de bomba de infusión
        $codigosAccesorios = ['19681', '19682', '19683', '19684', '10934', '10930', '16730', '16727', '18353', '18352', '19817', '19929', '19818'];

        // Topes de oxígeno por prestación (en litros)
        $topesOxigeno = [
            '050' => 3000,      // Atención inmediata RN
            '051' => 43200,     // Internamiento RN sin cirugía
            '052' => 43200,     // Internamiento RN con cirugía
            '054' => 3000,      // Parto vaginal
            '055' => 3000,      // Cesárea
            '056' => 0,         // Consulta externa (no permitido, excepto quimioterapia)
            '061' => 3000,      // Atención en tópico
            '062' => 43200,     // Emergencia
            '063' => 43200,     // Emergencia con observación
            '064' => 4000,      // Intervención médico-quirúrgica ambulatoria
            '065' => 43200,     // Internamiento sin cirugía
            '066' => 43200,     // Internamiento con cirugía menor
            '067' => 43200,     // Internamiento con cirugía mayor
            '068' => 43200,     // UCI
            '908' => 43200,     // Atención domiciliaria
        ];

        // Topes de accesorios por prestación
        $topesAccesorios = [
            '051' => 30, '052' => 30, '054' => 1, '055' => 1,
            '062' => 30, '063' => 30, '064' => 2, '065' => 30,
            '066' => 30, '067' => 30, '068' => 300, '908' => 2
        ];

        // Validar oxígeno
        $totalOxigeno = 0;
        foreach ($fua->consumos as $consumo) {
            if (in_array($consumo->codigo_sismed, $codigosOxigeno)) {
                $totalOxigeno += (int) $consumo->cantidad;
            }
        }

        if ($totalOxigeno > 0) {
            $topeOxigeno = $topesOxigeno[$codPrestacion] ?? null;
            
            if ($topeOxigeno === 0) {
                $listaErrores[] = "[RC_33] No se permite el registro de oxígeno para la prestación {$codPrestacion}";
                $listaSoluciones[] = "El código prestacional {$codPrestacion} no permite el registro de oxígeno. Verificar si la prestación es correcta o si el oxígeno debe registrarse en otra prestación.";
            } elseif ($topeOxigeno !== null && $totalOxigeno > $topeOxigeno) {
                $listaErrores[] = "[RC_33] Consumo de oxígeno excede el tope máximo: {$totalOxigeno} litros registrados, máximo permitido: {$topeOxigeno} litros";
                $listaSoluciones[] = "El consumo total de oxígeno para la prestación {$codPrestacion} no debe exceder {$topeOxigeno} litros. Verificar la cantidad registrada.";
            }
        }

        // Validar accesorios de bomba de infusión
        $totalAccesorios = 0;
        foreach ($fua->consumos as $consumo) {
            if (in_array($consumo->codigo_sismed, $codigosAccesorios)) {
                $totalAccesorios += (int) $consumo->cantidad;
            }
        }

        if ($totalAccesorios > 0) {
            $topeAccesorios = $topesAccesorios[$codPrestacion] ?? null;
            
            if ($topeAccesorios !== null && $totalAccesorios > $topeAccesorios) {
                $listaErrores[] = "[RC_33] Accesorios de bomba de infusión exceden el tope máximo: {$totalAccesorios} unidades registradas, máximo permitido: {$topeAccesorios}";
                $listaSoluciones[] = "El total de accesorios de bomba de infusión para la prestación {$codPrestacion} no debe exceder {$topeAccesorios} unidades. Verificar la cantidad registrada.";
            }
        }
    }

    /**
     * RC_34: Criterios de registro del destino del asegurado por prestación
     * OBJETIVO: Habilitar únicamente los campos válidos para el destino del asegurado
     * Fuente: Reglas_de_auditoria_automatizada_20251216.xlsx - RC_04
     */
    private function validarRC34_DestinoAsegurado($fua, &$listaErrores, &$listaSoluciones)
    {
        $codPrestacion = $fua->id_servicio;
        $destino = $fua->destino_asegurado;

        if (empty($destino)) {
            return; // No hay destino registrado
        }

        // Destinos válidos por prestación (simplificado - casos más comunes)
        $destinosValidos = [
            '001' => ['ALTA', 'CITADO', 'CONSULTA EXTERNA', 'APOYO AL DIAGNOSTICO'],
            '009' => ['CITADO', 'REFERIDO', 'CONSULTA EXTERNA', 'APOYO AL DIAGNOSTICO', 'CONTRARREFERIDO'],
            '050' => ['ALTA', 'CITADO', 'CONSULTA EXTERNA', 'APOYO AL DIAGNOSTICO', 'CONTRARREFERIDO'],
            '051' => ['ALTA', 'CITADO', 'REFERIDO', 'CONSULTA EXTERNA', 'APOYO AL DIAGNOSTICO', 'CONTRARREFERIDO', 'CORTE ADMINISTRATIVO', 'FALLECIDO'],
            '052' => ['ALTA', 'CITADO', 'REFERIDO', 'CONSULTA EXTERNA', 'APOYO AL DIAGNOSTICO', 'CONTRARREFERIDO', 'CORTE ADMINISTRATIVO', 'FALLECIDO'],
            '054' => ['ALTA', 'CITADO', 'HOSPITALIZADO', 'REFERIDO', 'CONSULTA EXTERNA', 'APOYO AL DIAGNOSTICO', 'CONTRARREFERIDO', 'FALLECIDO'],
            '055' => ['ALTA', 'CITADO', 'HOSPITALIZADO', 'REFERIDO', 'CONSULTA EXTERNA', 'APOYO AL DIAGNOSTICO', 'CONTRARREFERIDO', 'FALLECIDO'],
            '056' => ['ALTA', 'CITADO', 'HOSPITALIZADO', 'REFERIDO', 'CONSULTA EXTERNA', 'APOYO AL DIAGNOSTICO', 'CONTRARREFERIDO'],
            '062' => ['ALTA', 'CITADO', 'HOSPITALIZADO', 'REFERIDO', 'CONSULTA EXTERNA', 'APOYO AL DIAGNOSTICO', 'CONTRARREFERIDO', 'FALLECIDO'],
            '065' => ['ALTA', 'CITADO', 'REFERIDO', 'CONSULTA EXTERNA', 'APOYO AL DIAGNOSTICO', 'CONTRARREFERIDO', 'CORTE ADMINISTRATIVO', 'FALLECIDO'],
            '066' => ['ALTA', 'CITADO', 'REFERIDO', 'CONSULTA EXTERNA', 'APOYO AL DIAGNOSTICO', 'CONTRARREFERIDO', 'CORTE ADMINISTRATIVO', 'FALLECIDO'],
            '067' => ['ALTA', 'CITADO', 'REFERIDO', 'CONSULTA EXTERNA', 'APOYO AL DIAGNOSTICO', 'CONTRARREFERIDO', 'CORTE ADMINISTRATIVO', 'FALLECIDO'],
            '068' => ['ALTA', 'CITADO', 'REFERIDO', 'CONSULTA EXTERNA', 'APOYO AL DIAGNOSTICO', 'CONTRARREFERIDO', 'CORTE ADMINISTRATIVO', 'FALLECIDO'],
            '071' => ['CITADO', 'CONSULTA EXTERNA', 'CONTRARREFERIDO'],
            '111' => [], // Asignación por alimentación - no requiere destino
            '112' => [], // Sepelio óbito fetal - no requiere destino
            '113' => [], // Sepelio niños - no requiere destino
            '114' => [], // Sepelio adolescentes/adultos - no requiere destino
            '116' => [], // Sepelio RN - no requiere destino
            '117' => ['REFERIDO', 'APOYO AL DIAGNOSTICO', 'CONTRARREFERIDO', 'FALLECIDO'], // Traslado emergencia
        ];

        $destinosPermitidos = $destinosValidos[$codPrestacion] ?? null;

        // Si la prestación no tiene destinos definidos, no validar
        if ($destinosPermitidos === null) {
            return;
        }

        // Si la prestación no requiere destino (array vacío)
        if (count($destinosPermitidos) == 0 && !empty($destino)) {
            $listaErrores[] = "[RC_34] La prestación {$codPrestacion} no requiere registrar destino del asegurado";
            $listaSoluciones[] = "El código prestacional {$codPrestacion} no debe tener destino del asegurado registrado. Dejar el campo vacío.";
            return;
        }

        // Validar que el destino esté en la lista permitida
        if (!in_array(strtoupper($destino), $destinosPermitidos)) {
            $destinosTexto = implode(', ', $destinosPermitidos);
            $listaErrores[] = "[RC_34] Destino '{$destino}' no válido para la prestación {$codPrestacion}";
            $listaSoluciones[] = "Los destinos permitidos para el código prestacional {$codPrestacion} son: {$destinosTexto}. Seleccionar uno de estos destinos.";
        }
    }

    /**
     * RC_35: Consistencia de registro de prestaciones por edad, sexo y condiciones del asegurado
     * OBJETIVO: Optimizar la calidad de la información asegurando que las prestaciones
     * se registren únicamente cuando corresponda a la edad, sexo y condición de salud
     * Fuente: Reglas_de_auditoria_automatizada_20251216.xlsx - RC_01
     */
    private function validarRC35_ConsistenciaPrestaciones($fua, &$listaErrores, &$listaSoluciones)
    {
        $codPrestacion = $fua->id_servicio;
        $edad = $this->calcularEdadEnDias($fua->fecha_nacimiento, $fua->fecha_atencion);
        $sexo = strtoupper($fua->sexo ?? '');
        $esGestante = ($fua->gestante == 'S' || $fua->gestante == 1);
        $esPuerpera = ($fua->puerpera == 'S' || $fua->puerpera == 1);

        // Definir requisitos por prestación (simplificado - casos más comunes)
        $requisitos = [
            '301' => ['edad_min' => 0, 'edad_max' => 4379, 'sexo' => 'A', 'gestante' => 'N', 'puerpera' => 'N'], // 0-11 años
            '302' => ['edad_min' => 4380, 'edad_max' => 6569, 'sexo' => 'A', 'gestante' => 'N', 'puerpera' => 'N'], // 12-17 años
            '303' => ['edad_min' => 6570, 'edad_max' => 10949, 'sexo' => 'A', 'gestante' => 'N', 'puerpera' => 'N'], // 18-29 años
            '304' => ['edad_min' => 10950, 'edad_max' => 21899, 'sexo' => 'A', 'gestante' => 'N', 'puerpera' => 'N'], // 30-59 años
            '305' => ['edad_min' => 21900, 'edad_max' => 44164, 'sexo' => 'A', 'gestante' => 'N', 'puerpera' => 'N'], // 60+ años
            '306' => ['edad_min' => 3285, 'edad_max' => 21899, 'sexo' => 'F', 'gestante' => 'S'], // Prenatal 9-59 años
            '009' => ['edad_min' => 3285, 'edad_max' => 21900, 'sexo' => 'F', 'gestante' => 'S'], // Atención prenatal
            '010' => ['edad_min' => 3285, 'edad_max' => 21900, 'sexo' => 'F', 'puerpera' => 'S'], // Puerperio
            '054' => ['edad_min' => 3285, 'edad_max' => 21900, 'sexo' => 'F', 'gestante' => 'S'], // Parto
            '055' => ['edad_min' => 3285, 'edad_max' => 21900, 'sexo' => 'F', 'gestante' => 'S'], // Cesárea
            '050' => ['edad_min' => 0, 'edad_max' => 2, 'sexo' => 'A'], // Atención inmediata RN
            '023' => ['edad_min' => 18250, 'edad_max' => 27375, 'sexo' => 'M'], // Detección cáncer próstata 50-75 años
            '024' => ['edad_min' => 9125, 'edad_max' => 23725, 'sexo' => 'F'], // Detección cáncer cérvico-uterino 25-65 años
            '025' => ['edad_min' => 7300, 'edad_max' => 44164, 'sexo' => 'F'], // Detección cáncer mama 20+ años
        ];

        $req = $requisitos[$codPrestacion] ?? null;

        if ($req === null) {
            return; // No hay requisitos definidos para esta prestación
        }

        // Validar edad
        if (isset($req['edad_min']) && $edad < $req['edad_min']) {
            $edadAnios = floor($edad / 365);
            $edadMinAnios = floor($req['edad_min'] / 365);
            $listaErrores[] = "[RC_35] Edad del paciente ({$edadAnios} años) menor a la edad mínima requerida ({$edadMinAnios} años) para la prestación {$codPrestacion}";
            $listaSoluciones[] = "Verificar la edad del paciente o el código prestacional. La prestación {$codPrestacion} requiere una edad mínima de {$edadMinAnios} años.";
        }

        if (isset($req['edad_max']) && $edad > $req['edad_max']) {
            $edadAnios = floor($edad / 365);
            $edadMaxAnios = floor($req['edad_max'] / 365);
            $listaErrores[] = "[RC_35] Edad del paciente ({$edadAnios} años) mayor a la edad máxima permitida ({$edadMaxAnios} años) para la prestación {$codPrestacion}";
            $listaSoluciones[] = "Verificar la edad del paciente o el código prestacional. La prestación {$codPrestacion} requiere una edad máxima de {$edadMaxAnios} años.";
        }

        // Validar sexo
        if (isset($req['sexo']) && $req['sexo'] != 'A') {
            if ($sexo != $req['sexo']) {
                $sexoRequerido = ($req['sexo'] == 'F') ? 'Femenino' : 'Masculino';
                $listaErrores[] = "[RC_35] Sexo del paciente ({$sexo}) no corresponde al requerido ({$sexoRequerido}) para la prestación {$codPrestacion}";
                $listaSoluciones[] = "La prestación {$codPrestacion} requiere que el paciente sea de sexo {$sexoRequerido}. Verificar el sexo del paciente o el código prestacional.";
            }
        }

        // Validar condición de gestante
        if (isset($req['gestante'])) {
            if ($req['gestante'] == 'S' && !$esGestante) {
                $listaErrores[] = "[RC_35] La prestación {$codPrestacion} requiere que la paciente esté registrada como gestante";
                $listaSoluciones[] = "Marcar el campo 'gestante' como 'S' o verificar si el código prestacional es correcto.";
            } elseif ($req['gestante'] == 'N' && $esGestante) {
                $listaErrores[] = "[RC_35] La prestación {$codPrestacion} no permite pacientes gestantes";
                $listaSoluciones[] = "Verificar el código prestacional o la condición de gestante del paciente.";
            }
        }

        // Validar condición de puérpera
        if (isset($req['puerpera'])) {
            if ($req['puerpera'] == 'S' && !$esPuerpera) {
                $listaErrores[] = "[RC_35] La prestación {$codPrestacion} requiere que la paciente esté registrada como puérpera";
                $listaSoluciones[] = "Marcar el campo 'puérpera' como 'S' o verificar si el código prestacional es correcto.";
            } elseif ($req['puerpera'] == 'N' && $esPuerpera) {
                $listaErrores[] = "[RC_35] La prestación {$codPrestacion} no permite pacientes puérperas";
                $listaSoluciones[] = "Verificar el código prestacional o la condición de puérpera del paciente.";
            }
        }
    }

    /**
     * Calcula la edad en días entre dos fechas
     */
    private function calcularEdadEnDias($fechaNacimiento, $fechaReferencia)
    {
        if (empty($fechaNacimiento) || empty($fechaReferencia)) {
            return 0;
        }

        $nacimiento = Carbon::parse($fechaNacimiento);
        $referencia = Carbon::parse($fechaReferencia);
        
        return $nacimiento->diffInDays($referencia);
    }

    // ============================================
    // FASE 8: REGLAS ADICIONALES FASE 2
    // ============================================

    /**
     * RR_82: Control de lotes autorizados por año calendario
     * OBJETIVO: Garantizar la coherencia entre el lote de FUA y el año calendario correspondiente,
     * restringiendo el registro de prestaciones fuera del período autorizado
     * Fuente: Reglas_de_auditoria_automatizada_20251216.xlsx - RR_82
     */
    private function validarRR82_ControlLotesCalendario($fua, &$listaErrores, &$listaSoluciones)
    {
        // Extraer lote del FUA ID (formato: CodEst-Lote-Correlativo, ej: 00003361-25-00059073)
        $fuaId = $fua->fua_id;
        
        if (empty($fuaId)) {
            return; // Ya validado en RR_00
        }

        $partes = explode('-', $fuaId);
        
        if (count($partes) < 2) {
            return; // Formato inválido, ya validado en RR_03
        }
        
        $lote = $partes[1]; // "24" o "25"
        $fechaAtencion = Carbon::parse($fua->fecha_atencion);
        $codPrestacion = $fua->id_servicio;
        
        // Validar lote 25 (año 2025 completo)
        if ($lote == '25') {
            $inicioLote25 = Carbon::create(2025, 1, 1, 0, 0, 0);
            $finLote25 = Carbon::create(2025, 12, 31, 23, 59, 59);
            
            if ($fechaAtencion->lt($inicioLote25) || $fechaAtencion->gt($finLote25)) {
                $listaErrores[] = "[RR_82] Lote 25 solo permite fechas de atención del 01/01/2025 al 31/12/2025";
                $listaSoluciones[] = "El lote 25 es válido únicamente para atenciones realizadas durante el año 2025. Verificar el lote del FUA o la fecha de atención.";
            }
        }
        
        // Validar lote 24 (solo hasta 30/06/2025, con excepciones)
        elseif ($lote == '24') {
            $inicioLote24 = Carbon::create(2025, 1, 1, 0, 0, 0);
            $finLote24 = Carbon::create(2025, 6, 30, 23, 59, 59);
            
            // Prestaciones de internamiento tienen excepción especial
            $prestacionesInternamiento = ['051', '052', '054', '055', '065', '066', '067', '068'];
            
            if (in_array($codPrestacion, $prestacionesInternamiento)) {
                // EXCEPCIÓN: Internamientos con fecha de ingreso antes del 30/06/2025
                // pueden tener fecha de atención (alta) después del 01/07/2025
                
                if (!empty($fua->fecha_ingreso)) {
                    $fechaIngreso = Carbon::parse($fua->fecha_ingreso);
                    
                    // Validar que la fecha de ingreso esté dentro del período del lote 24
                    if ($fechaIngreso->lt($inicioLote24) || $fechaIngreso->gt($finLote24)) {
                        $listaErrores[] = "[RR_82] Lote 24 requiere fecha de ingreso entre 01/01/2025 y 30/06/2025 para internamientos";
                        $listaSoluciones[] = "Para internamientos con fecha de ingreso después del 30/06/2025, usar lote 25. Para ingresos antes del 01/01/2025, verificar el lote correcto.";
                    }
                    
                    // La fecha de atención (alta) puede ser después del 30/06/2025 si el ingreso fue antes
                    // No validamos la fecha de atención para internamientos con ingreso válido
                } else {
                    // Si no hay fecha de ingreso registrada, validar fecha de atención
                    if ($fechaAtencion->gt($finLote24)) {
                        $listaErrores[] = "[RR_82] Lote 24 no permite fechas de atención después del 30/06/2025";
                        $listaSoluciones[] = "Para atenciones después del 30/06/2025, usar lote 25. Si es un internamiento, registrar la fecha de ingreso.";
                    }
                }
            } else {
                // Prestaciones NO internamiento: fecha de atención debe estar dentro del período
                if ($fechaAtencion->lt($inicioLote24) || $fechaAtencion->gt($finLote24)) {
                    $listaErrores[] = "[RR_82] Lote 24 solo permite fechas de atención del 01/01/2025 al 30/06/2025";
                    $listaSoluciones[] = "El lote 24 es válido únicamente para atenciones del 01/01/2025 al 30/06/2025. Para atenciones después del 30/06/2025, usar lote 25.";
                }
            }
        }
        
        // Advertencia para lotes no reconocidos
        elseif (!in_array($lote, ['24', '25'])) {
            // Opcional: advertir sobre lotes no estándar
            // $listaErrores[] = "[RR_82] Lote '{$lote}' no reconocido en las reglas de validación";
            // $listaSoluciones[] = "Los lotes válidos para 2025 son: 24 (hasta 30/06/2025) y 25 (todo 2025). Verificar el lote del FUA.";
        }
    }

    // ============================================
    // FASE 9: REGLAS PRÁCTICAS ADICIONALES (PHASE 3)
    // ============================================

    /**
     * RC_CAMPOS: Validación de campos obligatorios por prestación
     * OBJETIVO: Garantizar que campos clínicos específicos estén completos según el tipo de prestación
     */
    private function validarRC_CamposObligatorios($fua, &$listaErrores, &$listaSoluciones)
    {
        $codPrestacion = $fua->id_servicio;
        
        // Definir campos obligatorios por prestación
        $camposObligatorios = [
            '009' => [ // Atención prenatal
                'edad_gestacional_semanas' => 'Edad gestacional',
                'peso' => 'Peso',
                'talla' => 'Talla'
            ],
            '054' => [ // Parto
                'edad_gestacional_semanas' => 'Edad gestacional'
            ],
            '055' => [ // Cesárea
                'edad_gestacional_semanas' => 'Edad gestacional'
            ],
            '050' => [ // Atención inmediata RN
                'peso' => 'Peso',
                'talla' => 'Talla',
                'edad_gestacional_semanas' => 'Edad gestacional'
            ],
            '051' => [ // Internamiento RN sin cirugía
                'peso' => 'Peso',
                'talla' => 'Talla'
            ],
            '052' => [ // Internamiento RN con cirugía
                'peso' => 'Peso',
                'talla' => 'Talla'
            ]
        ];
        
        if (!isset($camposObligatorios[$codPrestacion])) {
            return;
        }
        
        foreach ($camposObligatorios[$codPrestacion] as $campo => $nombre) {
            if (empty($fua->$campo) || $fua->$campo == 0) {
                $listaErrores[] = "[RC_CAMPOS] Falta campo obligatorio '{$nombre}' para prestación {$codPrestacion}";
                $listaSoluciones[] = "El código prestacional {$codPrestacion} requiere el registro obligatorio de {$nombre}. Completar el dato.";
            }
        }
    }

    /**
     * RC_RANGOS: Validación de rangos de valores clínicos
     * OBJETIVO: Garantizar que los valores clínicos registrados estén dentro de rangos razonables
     */
    private function validarRC_RangosValores($fua, &$listaErrores, &$listaSoluciones)
    {
        // Validar peso (0.5 kg - 300 kg)
        if (!empty($fua->peso) && $fua->peso > 0) {
            $peso = (float) $fua->peso;
            if ($peso < 0.5 || $peso > 300) {
                $listaErrores[] = "[RC_RANGOS] Peso fuera de rango razonable: {$peso} kg";
                $listaSoluciones[] = "El peso debe estar entre 0.5 kg y 300 kg. Verificar el valor registrado (posible error de digitación).";
            }
        }
        
        // Validar talla (20 cm - 250 cm)
        if (!empty($fua->talla) && $fua->talla > 0) {
            $talla = (float) $fua->talla;
            if ($talla < 20 || $talla > 250) {
                $listaErrores[] = "[RC_RANGOS] Talla fuera de rango razonable: {$talla} cm";
                $listaSoluciones[] = "La talla debe estar entre 20 cm y 250 cm. Verificar el valor registrado (posible error de digitación).";
            }
        }
        
        // Validar APGAR 1 minuto (0-10)
        if (isset($fua->apgar_1min) && $fua->apgar_1min !== '') {
            $apgar1 = (int) $fua->apgar_1min;
            if ($apgar1 < 0 || $apgar1 > 10) {
                $listaErrores[] = "[RC_RANGOS] APGAR 1 minuto fuera de rango: {$apgar1}";
                $listaSoluciones[] = "El APGAR debe estar entre 0 y 10. Verificar el valor registrado.";
            }
        }
        
        // Validar APGAR 5 minutos (0-10)
        if (isset($fua->apgar_5min) && $fua->apgar_5min !== '') {
            $apgar5 = (int) $fua->apgar_5min;
            if ($apgar5 < 0 || $apgar5 > 10) {
                $listaErrores[] = "[RC_RANGOS] APGAR 5 minutos fuera de rango: {$apgar5}";
                $listaSoluciones[] = "El APGAR debe estar entre 0 y 10. Verificar el valor registrado.";
            }
        }
        
        // Validar edad gestacional (20-45 semanas)
        if (!empty($fua->edad_gestacional_semanas) && $fua->edad_gestacional_semanas > 0) {
            $eg = (int) $fua->edad_gestacional_semanas;
            if ($eg < 20 || $eg > 45) {
                $listaErrores[] = "[RC_RANGOS] Edad gestacional fuera de rango: {$eg} semanas";
                $listaSoluciones[] = "La edad gestacional debe estar entre 20 y 45 semanas. Verificar el valor registrado.";
            }
        }
        
        // Validar hemoglobina (5-25 g/dL)
        if (!empty($fua->hemoglobina) && $fua->hemoglobina > 0) {
            $hb = (float) $fua->hemoglobina;
            if ($hb < 5 || $hb > 25) {
                $listaErrores[] = "[RC_RANGOS] Hemoglobina fuera de rango: {$hb} g/dL";
                $listaSoluciones[] = "La hemoglobina debe estar entre 5 y 25 g/dL. Verificar el valor registrado.";
            }
        }
        
        // Validar perímetro cefálico (20-70 cm)
        if (!empty($fua->perimetro_cefalico) && $fua->perimetro_cefalico > 0) {
            $pc = (float) $fua->perimetro_cefalico;
            if ($pc < 20 || $pc > 70) {
                $listaErrores[] = "[RC_RANGOS] Perímetro cefálico fuera de rango: {$pc} cm";
                $listaSoluciones[] = "El perímetro cefálico debe estar entre 20 y 70 cm. Verificar el valor registrado.";
            }
        }
    }

    /**
     * RC_TEMPORAL: Validación de coherencia temporal entre fechas
     * OBJETIVO: Garantizar coherencia lógica entre fechas relacionadas
     */
    private function validarRC_CoherenciaTemporal($fua, &$listaErrores, &$listaSoluciones)
    {
        // Validar fecha ingreso ≤ fecha atención (alta)
        if (!empty($fua->fecha_ingreso) && !empty($fua->fecha_atencion)) {
            $ingreso = Carbon::parse($fua->fecha_ingreso);
            $atencion = Carbon::parse($fua->fecha_atencion);
            
            if ($ingreso->gt($atencion)) {
                $listaErrores[] = "[RC_TEMPORAL] Fecha de ingreso posterior a fecha de atención (alta)";
                $listaSoluciones[] = "La fecha de ingreso no puede ser posterior a la fecha de atención. Verificar las fechas registradas.";
            }
        }
        
        // Validar fecha atención ≤ fecha digitación
        if (!empty($fua->fecha_atencion) && !empty($fua->fecha_digitacion)) {
            $atencion = Carbon::parse($fua->fecha_atencion);
            $digitacion = Carbon::parse($fua->fecha_digitacion);
            
            if ($atencion->gt($digitacion)) {
                $listaErrores[] = "[RC_TEMPORAL] Fecha de atención posterior a fecha de digitación";
                $listaSoluciones[] = "La fecha de atención no puede ser posterior a la fecha de digitación del FUA.";
            }
        }
        
        // Validar coherencia de días de hospitalización
        $prestacionesInternamiento = ['051', '052', '054', '055', '065', '066', '067', '068'];
        if (in_array($fua->id_servicio, $prestacionesInternamiento)) {
            if (!empty($fua->fecha_ingreso) && !empty($fua->fecha_atencion) && !empty($fua->dias_hospitalizacion)) {
                $ingreso = Carbon::parse($fua->fecha_ingreso);
                $alta = Carbon::parse($fua->fecha_atencion);
                $diasCalculados = $ingreso->diffInDays($alta) + 1; // Incluir día de ingreso
                $diasRegistrados = (int) $fua->dias_hospitalizacion;
                
                // Permitir diferencia de ±1 día por redondeos
                if (abs($diasCalculados - $diasRegistrados) > 1) {
                    $listaErrores[] = "[RC_TEMPORAL] Días de hospitalización inconsistente: registrado {$diasRegistrados}, calculado {$diasCalculados}";
                    $listaSoluciones[] = "Los días de hospitalización deben coincidir con la diferencia entre fecha de alta y fecha de ingreso. Verificar las fechas o el número de días registrados.";
                }
            }
        }
        
        // Validar que fecha de atención no sea futura
        if (!empty($fua->fecha_atencion)) {
            $atencion = Carbon::parse($fua->fecha_atencion);
            $hoy = Carbon::now();
            
            if ($atencion->gt($hoy)) {
                $listaErrores[] = "[RC_TEMPORAL] Fecha de atención es futura: {$atencion->format('d/m/Y')}";
                $listaSoluciones[] = "La fecha de atención no puede ser posterior a la fecha actual. Verificar la fecha registrada.";
            }
        }
    }

    // ============================================
    // FASE 10: REGLAS ADICIONALES (PHASE 4)
    // ============================================

    /**
     * RC_DIAGNOSTICO: Coherencia entre diagnóstico y prestación
     * OBJETIVO: Validar que el diagnóstico sea coherente con el tipo de prestación
     */
    private function validarRC_DiagnosticoPrestacion($fua, &$listaErrores, &$listaSoluciones)
    {
        $codPrestacion = $fua->id_servicio;
        $diagnostico = $fua->diagnostico_definitivo ?? $fua->diagnostico_motivo_consulta;
        
        if (empty($diagnostico)) {
            return;
        }
        
        $codigoDx = substr($diagnostico, 0, 3);
        
        // Validaciones específicas por prestación
        
        // Atención prenatal (009) debe tener diagnóstico de embarazo (Z34, Z35, O00-O99)
        if ($codPrestacion == '009') {
            $esEmbarazo = ($codigoDx == 'Z34' || $codigoDx == 'Z35' || 
                          ($codigoDx >= 'O00' && $codigoDx <= 'O99'));
            
            if (!$esEmbarazo) {
                $listaErrores[] = "[RC_DIAGNOSTICO] Diagnóstico '{$diagnostico}' no coherente con atención prenatal";
                $listaSoluciones[] = "La atención prenatal (009) debe tener diagnóstico relacionado con embarazo (Z34, Z35, O00-O99).";
            }
        }
        
        // Puerperio (010) debe tener diagnóstico de puerperio (O85-O92, Z39)
        if ($codPrestacion == '010') {
            $esPuerperio = (($codigoDx >= 'O85' && $codigoDx <= 'O92') || $codigoDx == 'Z39');
            
            if (!$esPuerperio) {
                $listaErrores[] = "[RC_DIAGNOSTICO] Diagnóstico '{$diagnostico}' no coherente con atención de puerperio";
                $listaSoluciones[] = "La atención de puerperio (010) debe tener diagnóstico relacionado (O85-O92, Z39).";
            }
        }
        
        // Atención inmediata RN (050) debe tener diagnóstico de RN (P00-P96, Z38)
        if ($codPrestacion == '050') {
            $esRN = (($codigoDx >= 'P00' && $codigoDx <= 'P96') || $codigoDx == 'Z38');
            
            if (!$esRN) {
                $listaErrores[] = "[RC_DIAGNOSTICO] Diagnóstico '{$diagnostico}' no coherente con atención inmediata RN";
                $listaSoluciones[] = "La atención inmediata del RN (050) debe tener diagnóstico de recién nacido (P00-P96, Z38).";
            }
        }
        
        // Planificación familiar (071) debe tener diagnóstico Z30
        if ($codPrestacion == '071') {
            if ($codigoDx != 'Z30') {
                $listaErrores[] = "[RC_DIAGNOSTICO] Diagnóstico '{$diagnostico}' no coherente con planificación familiar";
                $listaSoluciones[] = "La planificación familiar (071) debe tener diagnóstico Z30 (Atención para la anticoncepción).";
            }
        }
    }

    /**
     * RC_PROCEDIMIENTO: Validación de límites de procedimientos por prestación
     * OBJETIVO: Controlar que no se registren más procedimientos de los permitidos
     */
    private function validarRC_LimitesProcedimientos($fua, &$listaErrores, &$listaSoluciones)
    {
        // Verificar si hay procedimientos registrados
        if (!$fua->procedimientos || $fua->procedimientos->count() == 0) {
            return;
        }
        
        $codPrestacion = $fua->id_servicio;
        $totalProcedimientos = $fua->procedimientos->count();
        
        // Límites de procedimientos por prestación
        $limitesProcedimientos = [
            '056' => 10,  // Consulta externa
            '061' => 15,  // Atención en tópico
            '062' => 20,  // Emergencia
            '063' => 25,  // Emergencia con observación
            '064' => 30,  // Intervención médico-quirúrgica ambulatoria
            '065' => 50,  // Internamiento sin cirugía
            '066' => 60,  // Internamiento con cirugía menor
            '067' => 80,  // Internamiento con cirugía mayor
            '068' => 100, // UCI
        ];
        
        if (isset($limitesProcedimientos[$codPrestacion])) {
            $limite = $limitesProcedimientos[$codPrestacion];
            
            if ($totalProcedimientos > $limite) {
                $listaErrores[] = "[RC_PROCEDIMIENTO] Excede límite de procedimientos: {$totalProcedimientos} registrados, máximo {$limite}";
                $listaSoluciones[] = "El código prestacional {$codPrestacion} permite un máximo de {$limite} procedimientos. Verificar los procedimientos registrados.";
            }
        }
    }

    /**
     * RC_PROFESIONAL: Validación de calificación profesional según procedimiento
     * OBJETIVO: Garantizar que solo profesionales calificados registren ciertos procedimientos
     */
    private function validarRC_CalificacionProfesional($fua, &$listaErrores, &$listaSoluciones)
    {
        $tipoProfesional = $fua->tipo_profesional;
        $codPrestacion = $fua->id_servicio;
        
        // Prestaciones que requieren médico (tipo 1)
        $prestacionesMedico = ['054', '055', '064', '066', '067', '068'];
        
        if (in_array($codPrestacion, $prestacionesMedico)) {
            if ($tipoProfesional != '1') {
                $listaErrores[] = "[RC_PROFESIONAL] Prestación {$codPrestacion} requiere profesional médico";
                $listaSoluciones[] = "El código prestacional {$codPrestacion} debe ser registrado por un médico (tipo profesional 1).";
            }
        }
        
        // Atención prenatal puede ser por médico (1) u obstetra (5)
        if ($codPrestacion == '009') {
            if (!in_array($tipoProfesional, ['1', '5'])) {
                $listaErrores[] = "[RC_PROFESIONAL] Atención prenatal requiere médico u obstetra";
                $listaSoluciones[] = "La atención prenatal (009) debe ser registrada por médico (1) u obstetra (5).";
            }
        }
        
        // Planificación familiar puede ser por varios profesionales
        if ($codPrestacion == '071') {
            if (!in_array($tipoProfesional, ['1', '5', '6'])) {
                $listaErrores[] = "[RC_PROFESIONAL] Planificación familiar requiere médico, obstetra o enfermera";
                $listaSoluciones[] = "La planificación familiar (071) debe ser registrada por médico (1), obstetra (5) o enfermera (6).";
            }
        }
    }

    /**
     * RC_INSUMOS: Validación de límites de insumos por prestación
     * OBJETIVO: Controlar que no se registren cantidades excesivas de insumos
     */
    private function validarRC_LimitesInsumos($fua, &$listaErrores, &$listaSoluciones)
    {
        // Verificar si hay consumos de insumos
        if (!$fua->consumos || $fua->consumos->count() == 0) {
            return;
        }
        
        $codPrestacion = $fua->id_servicio;
        
        // Contar insumos (no medicamentos)
        $totalInsumos = 0;
        foreach ($fua->consumos as $consumo) {
            if (!empty($consumo->cod_insumo) && empty($consumo->cod_medicamento)) {
                $totalInsumos++;
            }
        }
        
        if ($totalInsumos == 0) {
            return;
        }
        
        // Límites de insumos por prestación
        $limitesInsumos = [
            '056' => 20,  // Consulta externa
            '061' => 30,  // Atención en tópico
            '062' => 40,  // Emergencia
            '063' => 50,  // Emergencia con observación
            '064' => 60,  // Intervención médico-quirúrgica ambulatoria
            '065' => 100, // Internamiento sin cirugía
            '066' => 120, // Internamiento con cirugía menor
            '067' => 150, // Internamiento con cirugía mayor
            '068' => 200, // UCI
        ];
        
        if (isset($limitesInsumos[$codPrestacion])) {
            $limite = $limitesInsumos[$codPrestacion];
            
            if ($totalInsumos > $limite) {
                $listaErrores[] = "[RC_INSUMOS] Excede límite de insumos: {$totalInsumos} registrados, máximo {$limite}";
                $listaSoluciones[] = "El código prestacional {$codPrestacion} permite un máximo de {$limite} insumos. Verificar los insumos registrados.";
            }
        }
    }

    // ============================================
    // FASE 11: REGLAS FINALES (PHASE 5)
    // ============================================

    /**
     * RC_MEDICAMENTOS: Validación específica de medicamentos
     * OBJETIVO: Validar coherencia de medicamentos con diagnóstico y prestación
     */
    private function validarRC_Medicamentos($fua, &$listaErrores, &$listaSoluciones)
    {
        if (!$fua->consumos || $fua->consumos->count() == 0) {
            return;
        }
        
        $codPrestacion = $fua->id_servicio;
        $totalMedicamentos = 0;
        
        // Contar medicamentos
        foreach ($fua->consumos as $consumo) {
            if (!empty($consumo->cod_medicamento)) {
                $totalMedicamentos++;
            }
        }
        
        if ($totalMedicamentos == 0) {
            return;
        }
        
        // Límites de medicamentos por prestación
        $limitesMedicamentos = [
            '056' => 10,  // Consulta externa
            '061' => 15,  // Atención en tópico
            '062' => 20,  // Emergencia
            '063' => 30,  // Emergencia con observación
            '064' => 25,  // Intervención médico-quirúrgica ambulatoria
            '065' => 50,  // Internamiento sin cirugía
            '066' => 60,  // Internamiento con cirugía menor
            '067' => 80,  // Internamiento con cirugía mayor
            '068' => 100, // UCI
        ];
        
        if (isset($limitesMedicamentos[$codPrestacion])) {
            $limite = $limitesMedicamentos[$codPrestacion];
            
            if ($totalMedicamentos > $limite) {
                $listaErrores[] = "[RC_MEDICAMENTOS] Excede límite de medicamentos: {$totalMedicamentos} registrados, máximo {$limite}";
                $listaSoluciones[] = "El código prestacional {$codPrestacion} permite un máximo de {$limite} medicamentos. Verificar los medicamentos registrados.";
            }
        }
    }

    /**
     * RC_EMERGENCIA: Validaciones específicas para servicios de emergencia
     * OBJETIVO: Validar requisitos específicos de atenciones de emergencia
     */
    private function validarRC_Emergencia($fua, &$listaErrores, &$listaSoluciones)
    {
        $codPrestacion = $fua->id_servicio;
        
        // Solo aplica a emergencias
        if (!in_array($codPrestacion, ['062', '063'])) {
            return;
        }
        
        // Emergencia debe tener diagnóstico definitivo
        if (empty($fua->diagnostico_definitivo)) {
            $listaErrores[] = "[RC_EMERGENCIA] Falta diagnóstico definitivo en atención de emergencia";
            $listaSoluciones[] = "Las atenciones de emergencia (062, 063) requieren obligatoriamente diagnóstico definitivo.";
        }
        
        // Emergencia con observación (063) debe tener fecha de ingreso
        if ($codPrestacion == '063') {
            if (empty($fua->fecha_ingreso)) {
                $listaErrores[] = "[RC_EMERGENCIA] Falta fecha de ingreso en emergencia con observación";
                $listaSoluciones[] = "La emergencia con observación (063) requiere fecha de ingreso a observación.";
            }
            
            // Debe tener al menos 1 día de observación
            if (!empty($fua->fecha_ingreso) && !empty($fua->fecha_atencion)) {
                $ingreso = Carbon::parse($fua->fecha_ingreso);
                $alta = Carbon::parse($fua->fecha_atencion);
                $horas = $ingreso->diffInHours($alta);
                
                if ($horas < 1) {
                    $listaErrores[] = "[RC_EMERGENCIA] Tiempo de observación insuficiente: {$horas} horas";
                    $listaSoluciones[] = "La emergencia con observación debe tener al menos 1 hora de observación.";
                }
            }
        }
        
        // Validar destino del paciente
        if (empty($fua->destino_paciente)) {
            $listaErrores[] = "[RC_EMERGENCIA] Falta destino del paciente en emergencia";
            $listaSoluciones[] = "Las atenciones de emergencia deben registrar el destino del paciente (alta, hospitalización, referencia, etc.).";
        }
    }

    /**
     * RC_HOSPITALIZACION: Validaciones específicas para hospitalización
     * OBJETIVO: Validar requisitos específicos de servicios de hospitalización
     */
    private function validarRC_Hospitalizacion($fua, &$listaErrores, &$listaSoluciones)
    {
        $codPrestacion = $fua->id_servicio;
        
        // Solo aplica a hospitalizaciones
        $prestacionesHospitalizacion = ['051', '052', '065', '066', '067', '068'];
        if (!in_array($codPrestacion, $prestacionesHospitalizacion)) {
            return;
        }
        
        // Debe tener fecha de ingreso
        if (empty($fua->fecha_ingreso)) {
            $listaErrores[] = "[RC_HOSPITALIZACION] Falta fecha de ingreso en hospitalización";
            $listaSoluciones[] = "Los servicios de hospitalización requieren obligatoriamente fecha de ingreso.";
        }
        
        // Debe tener días de hospitalización
        if (empty($fua->dias_hospitalizacion) || $fua->dias_hospitalizacion < 1) {
            $listaErrores[] = "[RC_HOSPITALIZACION] Falta o es inválido el número de días de hospitalización";
            $listaSoluciones[] = "Los servicios de hospitalización deben registrar al menos 1 día de hospitalización.";
        }
        
        // Validar días mínimos según tipo de prestación
        if (!empty($fua->dias_hospitalizacion)) {
            $dias = (int) $fua->dias_hospitalizacion;
            
            // UCI debe tener al menos 1 día
            if ($codPrestacion == '068' && $dias < 1) {
                $listaErrores[] = "[RC_HOSPITALIZACION] UCI requiere al menos 1 día de estancia";
                $listaSoluciones[] = "La atención en UCI (068) debe registrar al menos 1 día de hospitalización.";
            }
            
            // Validar días máximos razonables
            $diasMaximos = [
                '051' => 30,  // RN sin cirugía
                '052' => 60,  // RN con cirugía
                '065' => 90,  // Sin cirugía
                '066' => 90,  // Cirugía menor
                '067' => 120, // Cirugía mayor
                '068' => 90,  // UCI
            ];
            
            if (isset($diasMaximos[$codPrestacion]) && $dias > $diasMaximos[$codPrestacion]) {
                $listaErrores[] = "[RC_HOSPITALIZACION] Días de hospitalización excesivos: {$dias} días (máximo razonable: {$diasMaximos[$codPrestacion]})";
                $listaSoluciones[] = "Verificar los días de hospitalización. Si es correcto, puede requerir autorización especial.";
            }
        }
        
        // Cirugías deben tener procedimientos quirúrgicos
        if (in_array($codPrestacion, ['052', '066', '067'])) {
            $tieneProcedimientoQuirurgico = false;
            
            if ($fua->procedimientos && $fua->procedimientos->count() > 0) {
                $tieneProcedimientoQuirurgico = true;
            }
            
            if (!$tieneProcedimientoQuirurgico) {
                $listaErrores[] = "[RC_HOSPITALIZACION] Falta registro de procedimiento quirúrgico";
                $listaSoluciones[] = "El código prestacional {$codPrestacion} requiere el registro de al menos un procedimiento quirúrgico.";
            }
        }
    }

    /**
     * RC_NEONATAL: Validaciones específicas para atención neonatal
     * OBJETIVO: Validar requisitos específicos de atención a recién nacidos
     */
    private function validarRC_Neonatal($fua, &$listaErrores, &$listaSoluciones)
    {
        $codPrestacion = $fua->id_servicio;
        
        // Solo aplica a atenciones neonatales
        $prestacionesNeonatales = ['050', '051', '052'];
        if (!in_array($codPrestacion, $prestacionesNeonatales)) {
            return;
        }
        
        // Validar edad del paciente (debe ser recién nacido: 0-28 días)
        if (!empty($fua->fecha_nacimiento) && !empty($fua->fecha_atencion)) {
            $edadDias = $this->calcularEdadEnDias($fua->fecha_nacimiento, $fua->fecha_atencion);
            
            if ($edadDias > 28) {
                $listaErrores[] = "[RC_NEONATAL] Edad del paciente excede período neonatal: {$edadDias} días";
                $listaSoluciones[] = "Las prestaciones neonatales (050, 051, 052) son para recién nacidos de 0-28 días. Verificar el código prestacional o la fecha de nacimiento.";
            }
        }
        
        // Validar peso al nacer (500g - 6000g)
        if (!empty($fua->peso)) {
            $peso = (float) $fua->peso;
            
            if ($peso < 0.5 || $peso > 6) {
                $listaErrores[] = "[RC_NEONATAL] Peso del RN fuera de rango: {$peso} kg";
                $listaSoluciones[] = "El peso del recién nacido debe estar entre 0.5 kg (500g) y 6 kg. Verificar el valor registrado.";
            }
            
            // Clasificar según peso
            if ($peso < 1.5) {
                // Muy bajo peso - debe tener atención especial
                if ($codPrestacion == '050') {
                    $listaErrores[] = "[RC_NEONATAL] RN de muy bajo peso ({$peso} kg) requiere hospitalización";
                    $listaSoluciones[] = "Los recién nacidos con peso menor a 1.5 kg requieren hospitalización (051 o 052), no solo atención inmediata (050).";
                }
            }
        }
        
        // Validar APGAR (debe estar registrado)
        if ($codPrestacion == '050') {
            if (empty($fua->apgar_1min) && empty($fua->apgar_5min)) {
                $listaErrores[] = "[RC_NEONATAL] Falta registro de APGAR en atención inmediata del RN";
                $listaSoluciones[] = "La atención inmediata del RN (050) requiere obligatoriamente el registro de APGAR al 1 y 5 minutos.";
            }
            
            // Validar coherencia APGAR
            if (!empty($fua->apgar_1min) && !empty($fua->apgar_5min)) {
                $apgar1 = (int) $fua->apgar_1min;
                $apgar5 = (int) $fua->apgar_5min;
                
                // APGAR a los 5 min generalmente es igual o mayor que a 1 min
                if ($apgar5 < $apgar1 - 2) {
                    $listaErrores[] = "[RC_NEONATAL] APGAR inconsistente: 1min={$apgar1}, 5min={$apgar5}";
                    $listaSoluciones[] = "El APGAR a los 5 minutos generalmente es igual o mayor que a 1 minuto. Verificar los valores registrados.";
                }
            }
        }
        
        // Validar edad gestacional
        if (!empty($fua->edad_gestacional_semanas)) {
            $eg = (int) $fua->edad_gestacional_semanas;
            
            // Clasificar prematuridad
            if ($eg < 37) {
                // Prematuro - debe tener atención especial
                if ($eg < 32 && $codPrestacion == '050') {
                    $listaErrores[] = "[RC_NEONATAL] RN prematuro extremo ({$eg} semanas) requiere hospitalización";
                    $listaSoluciones[] = "Los recién nacidos con edad gestacional menor a 32 semanas requieren hospitalización (051 o 052).";
                }
            }
        }
    }

    // ============================================
    // MÉTODOS AUXILIARES
    // ============================================

    /**
     * Cargar archivo CSV de reglas
     */
    private function cargarCSV($archivo)
    {
        $path = base_path("REGLAS_EXCEPCIONES_SIS/{$archivo}");
        
        if (!file_exists($path)) {
            return [];
        }
        
        $contenido = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $datos = [];
        $headers = null;
        
        foreach ($contenido as $linea) {
            $fila = str_getcsv($linea, ';');
            
            if ($headers === null) {
                $headers = $fila;
            } else {
                if (count($fila) === count($headers)) {
                    $datos[] = array_combine($headers, $fila);
                }
            }
        }
        
        return $datos;
    }

    /**
     * Calcular edad en años, meses y días
     */
    private function calcularEdadDetallada($fechaNacimiento, $fechaReferencia)
    {
        $nacimiento = Carbon::parse($fechaNacimiento);
        $referencia = Carbon::parse($fechaReferencia);
        
        return [
            'anios' => $nacimiento->diffInYears($referencia),
            'meses' => $nacimiento->diffInMonths($referencia),
            'dias' => $nacimiento->diffInDays($referencia),
        ];
    }

    /**
     * Parsear edad máxima del CSV (ej: "4 años", "12 meses", "28 días")
     */
    private function parsearEdadMaxima($edadTexto)
    {
        if (empty($edadTexto)) {
            return null;
        }
        
        // Extraer número
        preg_match('/(\d+)/', $edadTexto, $matches);
        if (!isset($matches[1])) {
            return null;
        }
        
        $numero = (int) $matches[1];
        $texto = strtolower($edadTexto);
        
        // Determinar unidad y aplicar regla de operativización
        if (strpos($texto, 'año') !== false || strpos($texto, 'a') !== false) {
            // Para años: si edad máxima es 3 años, considerar < 4 años
            return ['anios' => $numero, 'tipo' => 'anios'];
        } elseif (strpos($texto, 'mes') !== false) {
            // Para meses: si edad máxima es 12 meses, considerar < 13 meses
            return ['meses' => $numero, 'tipo' => 'meses'];
        } elseif (strpos($texto, 'día') !== false || strpos($texto, 'd') !== false) {
            // Para días: si edad máxima es 28 días, considerar < 29 días
            return ['dias' => $numero, 'tipo' => 'dias'];
        }
        
        return null;
    }
}