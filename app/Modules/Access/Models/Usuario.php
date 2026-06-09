<?php

namespace App\Modules\Access\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Usuario extends Authenticatable implements JWTSubject
{
    use Notifiable;
    protected $table = 'usuario';

    protected $primaryKey = 'id_usuario';

    public $timestamps = false;

    protected $fillable = [
        'id_rol',
        'ci',
        'nombres',
        'apellidos',
        'correo',
        'telefono1',
        'telefono2',
        'fecha_nacimiento',
        'sexo',
        'contrasena',
        'EstaActivo',
    ];

    protected $hidden = [
        'contrasena',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'EstaActivo' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Mapeo de credenciales (columnas no estándar)
    |--------------------------------------------------------------------------
    | El esquema usa "contrasena" en lugar de "password". Sobrescribimos los
    | accesores de autenticación para que el guard valide contra la columna real.
    */

    public function getAuthPassword(): string
    {
        return $this->contrasena;
    }

    public function getAuthPasswordName(): string
    {
        return 'contrasena';
    }

    /*
    |--------------------------------------------------------------------------
    | Contrato JWTSubject
    |--------------------------------------------------------------------------
    */

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'rol' => $this->rol?->nombre,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers de autorización
    |--------------------------------------------------------------------------
    */

    /**
     * Colección de códigos de permiso (campo "modulo") asignados al rol del usuario.
     */
    public function permisos(): array
    {
        return $this->rol
            ? $this->rol->permisos->pluck('modulo')->all()
            : [];
    }

    public function tienePermiso(string $codigo): bool
    {
        return in_array($codigo, $this->permisos(), true);
    }
}
