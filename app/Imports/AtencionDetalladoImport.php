<?php

namespace App\Imports;

use App\Models\FuaAtencionDetallado;
use App\Imports\Traits\DateTransformable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithUpserts;

class AtencionDetalladoImport implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    use DateTransformable;

    public function model(array $row)
    {
        
        $estado = isset($row['estado_fua']) ? trim($row['estado_fua']) : '';
        
        if ($estado !== 'OBSERVADO POR EL SIS') {
            return null; 
        }

        if (!isset($row['fua'])) return null;

        return new FuaAtencionDetallado([
            'fua_id'           => $row['fua'],
            'fecha_atencion'   => $this->transformDateTime($row['fecha_atencion']), // Usa DateTime si trae hora
            'eess'             => $row['eess'],
            'componente'       => $row['component'] ?? null,
            'tipo_atencion'    => $row['tipo_atencion'],
            'eess_ref'         => $row['eess_ref'] ?? null,
            'nro_hoja_ref'     => $row['nro_hoja_ref'] ?? null,
            'formato_asegurado'=> $row['formato_asegurado'] ?? null,
            'tipo_doc_paciente'=> $row['tipo_doc'],
            'num_doc_paciente' => $row['num_doc'],
            'apellido_paterno_paciente' => $row['ap_paterno'],
            'apellido_materno_paciente' => $row['ap_materno'],
            'nombres_paciente' => $row['nombres'],
            'fecha_nacimiento_paciente' => $this->transformDate($row['fec_nac']),
            'edad'             => $row['edad'],
            'sexo'             => $row['sexo'],
            'historia_clinica' => $row['historia_clinica'],
            'etnia'            => $row['etnia'] ?? null,
            'id_servicio'      => $row['id_servicio'],
            'servicio_descripcion' => $row['servicio'],
            'digitador'        => $row['digitador'] ?? null,
            'dni_resp_atencion'=> $row['dni_resp_aten'] ?? null,
            'nombre_profesional'=> $row['profesional'],
            'tipo_profesional' => $row['tipo_profesional'] ?? null,
            'fecha_registro'   => $this->transformDate($row['fecha_registro']),
            'hora_registro'    => $row['hora_registro'] ?? null, // A veces excel lo trae como decimal
            'lugar_atencion'   => $row['lugar_atencion'] ?? null,
            'destino_asegurado'=> $row['destino_asegurado'] ?? null,
            'gestante'         => $row['gestante'] ?? null,
            'fecha_probable_parto' => $this->transformDate($row['fecha_probable_parto'] ?? null),
            'fecha_parto'      => $this->transformDate($row['fecha_parto'] ?? null),
            'periodo'          => $row['periodo'] ?? null,
            'mes'              => $row['mes'] ?? null,
            'estado_fua'       => $row['estado_fua'] ?? null,
            'ups'              => $row['ups'] ?? null,
            'fecha_ingreso'    => $this->transformDateTime($row['fecha_ingreso'] ?? null),
            'fecha_envio_sis'  => $this->transformDateTime($row['fecha_envio_al_sis'] ?? null),
        ]);
    }

    // 3. Definir la columna clave para identificar duplicados
    public function uniqueBy()
    {
        return 'fua_id';
    }

    public function batchSize(): int { return 1000; }
    public function chunkSize(): int { return 1000; }
}