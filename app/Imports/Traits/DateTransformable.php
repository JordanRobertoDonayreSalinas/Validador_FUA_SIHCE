<?php

namespace App\Imports\Traits;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

trait DateTransformable
{
    public function transformDate($value)
    {
        if (empty($value)) return null;
        try {
            // Si es número (formato interno Excel), convertir
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value);
            }
            // Si es texto 'dd/mm/yyyy', parsear
            // Ajusta el formato si tu excel trae 'm/d/Y' u otro
            return Carbon::createFromFormat('d/m/Y', substr($value, 0, 10)); 
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public function transformDateTime($value)
    {
        if (empty($value)) return null;
        try {
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value);
            }
            return Carbon::createFromFormat('d/m/Y H:i', $value);
        } catch (\Exception $e) {
            return null;
        }
    }
}