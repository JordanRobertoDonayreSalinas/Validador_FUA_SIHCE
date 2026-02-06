<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    // --- CONSTANTES PARA VALIDACIÓN ---
    const TIPOS_DOCS = ['DNI','CE'];


    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'tipo_doc',
        'num_doc',
        'apellido_paterno',
        'apellido_materno',
        'nombres',
        'cod_eess',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // --- MUTATOR / EVENTO ---
    // Esto concatenará automáticamente los nombres al guardar
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($user) {
            // Solo si hay datos en nombres o apellidos, actualizamos el campo 'name'
            if ($user->nombres || $user->apellido_paterno) {
                $nombreCompleto = trim("{$user->nombres} {$user->apellido_paterno} {$user->apellido_materno}");
                $user->name = strtoupper($nombreCompleto); // Opcional: Guardar en mayúsculas
            }
        });
    }
}
