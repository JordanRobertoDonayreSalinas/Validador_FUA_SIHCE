<?php

namespace App\Http\Controllers\Modulo_Fua;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FuaAtencionDetallado;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Imports\FuaMainImport;
use Maatwebsite\Excel\Facades\Excel;

class FuaElectronicaController extends Controller
{
    public function index()
    {
        return view('modulos.modulo_fua.index');
    }

    public function create()
    {
        return view('modulos.modulo_fua.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'archivo_excel' => 'required|file|mimes:xlsx,xls',
            'pestanas'      => 'required|array|min:1',
        ], [
            'pestanas.required' => 'Debes seleccionar al menos una pestaña para procesar.'
        ]);

        try {
            $file = $request->file('archivo_excel');
            $opciones = $request->input('pestanas');

            // Ejecutamos la importación pasando las opciones seleccionadas
            Excel::import(new FuaMainImport($opciones), $file);

            return redirect()->route('fua.index')->with('success', 'Importación completada con éxito.');

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            // Manejo de errores de validación dentro del excel (si agregas reglas después)
            return back()->withErrors($e->failures());
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al procesar el archivo: ' . $e->getMessage()]);
        }
    }

    // Helper para formatear fechas
    private function parseDate($dateString, $onlyDate = false)
    {
        if (empty($dateString)) return null;
        try {
            // Intenta formato con hora
            if ($onlyDate) {
                 return Carbon::createFromFormat('d/m/Y', substr($dateString, 0, 10))->format('Y-m-d');
            }
            return Carbon::createFromFormat('d/m/Y H:i', $dateString)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            // Fallback si falla el formato (a veces Excel cambia formatos)
            return null; 
        }
    }
}
