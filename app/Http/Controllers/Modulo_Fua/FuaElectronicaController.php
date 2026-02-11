<?php

namespace App\Http\Controllers\Modulo_Fua;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Imports\FuaMainImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Schema;

// Importamos los modelos para poder limpiarlos
use App\Models\FuaAtencionDetallado;
use App\Models\FuaConsumo;
use App\Models\FuaSmi;
use App\Models\FuaPrincipalAdicional;
use App\Models\FuaReporteEstado;

use App\Services\ReglasValidacionService;

class FuaElectronicaController extends Controller
{
    public function index()
    {
        // Traemos los registros ordenados por fecha, paginados de 15 en 15
        $fuas = FuaAtencionDetallado::orderBy('created_at', 'desc')->paginate(15);
        
        return view('modulos.modulo_fua.index', compact('fuas'));
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

            // Loguear la información recibida para depuración
            \Log::info('Fua import - archivo recibido', [
                'name' => $file ? $file->getClientOriginalName() : null,
                'size' => $file ? $file->getSize() : null,
            ]);
            \Log::info('Fua import - opciones recibidas', ['opciones' => $opciones]);

            // Obtener ruta real del archivo subido y listar nombres de pestañas (sheets)
            $path = $file ? $file->getRealPath() : null;
            try {
                if ($path) {
                    $sheetNames = \PhpOffice\PhpSpreadsheet\IOFactory::load($path)->getSheetNames();
                    \Log::info('Fua import - nombres de pestañas en el Excel', ['sheets' => $sheetNames]);
                }
            } catch (\Exception $e) {
                \Log::warning('Fua import - no se pudo leer nombres de pestañas: ' . $e->getMessage());
            }

            // --- INICIO: LIMPIEZA AUTOMÁTICA DE BASE DE DATOS ---
            // Esto borrará toda la información anterior antes de cargar la nueva.
            
            Schema::disableForeignKeyConstraints(); // Desactivamos protección para borrar libremente

            // 1. Borramos tablas hijas primero
            FuaConsumo::truncate();
            FuaSmi::truncate();
            FuaPrincipalAdicional::truncate();
            FuaReporteEstado::truncate();

            // 2. Borramos tabla padre al final
            FuaAtencionDetallado::truncate();

            Schema::enableForeignKeyConstraints(); // Reactivamos protección
            
            // --- FIN: LIMPIEZA ---

            // Ejecutamos la importación pasando las opciones seleccionadas
            // Usar la ruta real del archivo para la importación (más fiable en algunos entornos)
            Excel::import(new FuaMainImport($opciones), $path ?? $file);

            // Loguear conteos en BD tras la importación para verificar inserts
            \Log::info('Fua import - totales en BD', [
                'atencion_detallado' => FuaAtencionDetallado::count(),
                'consumo' => FuaConsumo::count(),
                'smi' => FuaSmi::count(),
                'principal_adicional' => FuaPrincipalAdicional::count(),
                'reporte_estado' => FuaReporteEstado::count(),
            ]);

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

    public function validar(ReglasValidacionService $validador)
    {
        try {
            // Llamamos a la función con el nombre CORRECTO
            $cantidadErrores = $validador->ejecutarValidacion(); 

            if ($cantidadErrores > 0) {
                return redirect()->route('fua.index')
                    ->with('warning', "Se detectaron $cantidadErrores registros con observaciones.");
            }

            return redirect()->route('fua.index')
                ->with('success', '¡Excelente! Todos los registros son conformes.');

        } catch (\Exception $e) {
            // Esto te mostrará el error real en pantalla si algo falla
            return back()->with('error', 'Error crítico al validar: ' . $e->getMessage() . ' en línea ' . $e->getLine());
        }
    }

    public function destroyAll()
    {
        try {
            // Desactivamos las claves foráneas temporalmente para evitar errores de restricción
            Schema::disableForeignKeyConstraints();

            // Orden de limpieza: Primero las tablas hijas, al final las tablas padres
            FuaConsumo::truncate();
            FuaSmi::truncate();
            FuaPrincipalAdicional::truncate(); // Asegúrate del nombre correcto del modelo
            FuaReporteEstado::truncate();
            
            // Finalmente la tabla maestra
            FuaAtencionDetallado::truncate();

            Schema::enableForeignKeyConstraints();

            return redirect()->route('fua.index')->with('success', 'Base de datos limpiada correctamente. Ahora está vacía.');

        } catch (\Exception $e) {
            Schema::enableForeignKeyConstraints(); // Reactivar siempre si falla
            return back()->withErrors(['error' => 'Error al limpiar la BD: ' . $e->getMessage()]);
        }
    }
}
