<?php

namespace App\Http\Controllers\Modulo_Administrador;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->paginate(10);
        // Asegúrate que esta ruta exista en tu carpeta views
        return view('modulos.administrador.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        
        // Obtenemos la constante directamente del Modelo User
        $tipos_doc = User::TIPOS_DOCS; 

        return view('modulos.administrador.create', compact('roles', 'tipos_doc'));
    }

    public function store(Request $request)
    {
        // 1. Validaciones
        $request->validate([
            // Validamos que el tipo de doc esté dentro de la lista ['DNI', 'CE']
            'tipo_doc'          => ['required', Rule::in(User::TIPOS_DOCS)],
            'num_doc'           => 'required|unique:users,num_doc|max:15',
            'nombres'           => 'required|string|max:100',
            'apellido_paterno'  => 'required|string|max:100',
            'apellido_materno'  => 'required|string|max:100',
            'email'             => 'required|string|email|max:255|unique:users',
            'password'          => 'required|confirmed|min:8',
            'cod_eess'          => 'nullable|string',
            'role'              => 'required'
        ]);

        // 2. Creación del Usuario
        // Nota: No enviamos 'name' porque tu Modelo User ya tiene el evento "boot" 
        // que lo concatena automáticamente.
        $user = User::create([
            'tipo_doc'          => $request->tipo_doc,
            'num_doc'           => $request->num_doc,
            'nombres'           => strtoupper($request->nombres),
            'apellido_paterno'  => strtoupper($request->apellido_paterno),
            'apellido_materno'  => strtoupper($request->apellido_materno),
            'email'             => $request->email,
            'cod_eess'          => $request->cod_eess,
            'password'          => Hash::make($request->password),
        ]);

        // 3. Asignación de Rol (Spatie)
        $user->assignRole($request->role);

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario registrado correctamente.');
    }
}