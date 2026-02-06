<?php

namespace App\Imports;

use App\Models\FuaReporteEstado;
use App\Imports\Traits\DateTransformable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class ReporteEstadoImport implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    use DateTransformable;

    public function model(array $row)
    {
        // Excel header: "N° FUA" -> Laravel: "n_fua"
        if (!isset($row['n_fua'])) return null;

        return new FuaReporteEstado([
            'fua_id'                   => $row['n_fua'],
            'nro_paquete'              => $row['n_de_paquete'] ?? null,
            'responsable_envio'        => $row['responsable_envio'] ?? null,
            'fecha_envio_set_sis'      => $this->transformDate($row['fecha_envio_set_sis'] ?? null),
            'fecha_atencion'           => $this->transformDate($row['fecha_atencion'] ?? null),
            'fecha_edicion'            => $this->transformDate($row['fecha_edicion'] ?? null),
            'cod_servicio'             => $row['cod_servicio'] ?? null,
            'descripcion_servicio'     => $row['descripcion_de_servicio'] ?? null,
            'cod_prestacional'         => $row['cod_prestacional'] ?? null,
            'descripcion_prestacional' => $row['descripcion_prestacional'] ?? null,
            'estado_fua'               => $row['estado_fua'],
            'firma_atencion'           => $row['firma_atencion'] ?? null,
            'firma_farmacia'           => $row['firma_farmacia'] ?? null,
        ]);
    }

    public function batchSize(): int { return 1000; }
    public function chunkSize(): int { return 1000; }
}