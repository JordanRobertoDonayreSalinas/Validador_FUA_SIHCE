<?php

namespace App\Imports;

use App\Models\FuaPrincipalAdicional;
use App\Imports\Traits\DateTransformable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class PrincipalAdicionalImport implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    use DateTransformable;

    public function model(array $row)
    {
        // El header en excel es "N° Formato", laravel lo convierte a "n_formato"
        if (!isset($row['n_formato'])) return null;

        return new FuaPrincipalAdicional([
            'nro_formato'      => $row['n_formato'],
            'dni'              => $row['dni'],
            'fecha'            => $this->transformDate($row['fecha']),
            'beneficiario'     => $row['beneficiario'],
            'fecha_nacimiento' => $this->transformDate($row['fnac']), // Ojo: en excel dice F.Nac -> f_nac o fnac
            'edad'             => $row['edad'],
            'sexo'             => $row['sexo'],
            'eess'             => $row['eess'],
            'id_servicio'      => $row['id_servicio'],
            'servicio'         => $row['servicio'],
            'tipo_profesional' => $row['tipo_profesional'] ?? null,
            'profesional'      => $row['profesional'],
            'nro_dx'           => $row['nro_dx'],
            'cie10'            => $row['cie10'],
            'diagnostico'      => $row['diagnostico'],
        ]);
    }

    public function batchSize(): int { return 1000; }
    public function chunkSize(): int { return 1000; }
}