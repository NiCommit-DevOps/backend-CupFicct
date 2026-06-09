<?php

namespace App\Modules\Access\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_usuario' => $this->id_usuario,
            'ci' => $this->ci,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'correo' => $this->correo,
            'telefono1' => $this->telefono1,
            'telefono2' => $this->telefono2,
            'fecha_nacimiento' => $this->fecha_nacimiento?->toDateString(),
            'sexo' => $this->sexo,
            'esta_activo' => $this->EstaActivo,
            'rol' => new RolResource($this->whenLoaded('rol')),
            'permisos' => $this->when(
                $this->relationLoaded('rol') && $this->rol?->relationLoaded('permisos'),
                fn () => $this->rol->permisos->pluck('modulo')
            ),
        ];
    }
}
