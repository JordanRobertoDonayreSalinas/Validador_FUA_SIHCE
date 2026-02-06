<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear Roles del Sistema
        // Usamos firstOrCreate para evitar errores si se ejecuta doble vez
        $roleAdmin = Role::firstOrCreate(['name' => 'admin']);
        $roleDigitador = Role::firstOrCreate(['name' => 'digitador']);
        $rolePersonal = Role::firstOrCreate(['name' => 'personal_salud']);

        // 2. Crear un Usuario Administrador por defecto
        $user = User::firstOrCreate(
            ['email' => 'carlosgutierrezh0@gmail.com'], // Busca por email
            [
                'name' => 'ADMINISTRADOR SISTEMA', // El mutator lo sobreescribirá probablemente
                'nombres' => 'ADMINISTRADOR',
                'apellido_paterno' => 'SISTEMA',
                'apellido_materno' => 'GENERAL',
                'tipo_doc' => 'DNI',
                'num_doc' => '00000000',
                'password' => Hash::make('12345678'), // La contraseña es 'password'
                'cod_eess' => '3361',
            ]
        );

        // 3. Asignar el rol al usuario
        $user->assignRole($roleAdmin);
    }
}