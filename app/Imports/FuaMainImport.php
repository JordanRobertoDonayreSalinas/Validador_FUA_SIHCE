<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class FuaMainImport implements WithMultipleSheets
{
    protected $opciones;

    public function __construct(array $opciones)
    {
        $this->opciones = $opciones;
    }

    public function sheets(): array
    {
        $sheets = [];

        // Las claves (keys) deben coincidir EXACTAMENTE con el nombre de la pestaña del Excel
        if (in_array('atencion', $this->opciones)) {
            $sheets['ATENCIÓN - DETALLADO'] = new AtencionDetalladoImport();
        }

        if (in_array('principal_adicional', $this->opciones)) {
            $sheets['PRINCIPAL Y ADICIONAL'] = new PrincipalAdicionalImport();
        }

        if (in_array('consumo', $this->opciones)) {
            $sheets['CONSUMO'] = new ConsumoImport();
        }

        if (in_array('smi', $this->opciones)) {
            $sheets['SMI'] = new SmiImport();
        }

        if (in_array('reporte_estado', $this->opciones)) {
            $sheets['REPORTE ESTADO'] = new ReporteEstadoImport();
        }

        return $sheets;
    }
}