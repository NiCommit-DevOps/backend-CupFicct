<?php

namespace App\Modules\Access\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RolResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_rol' => $this->id_rol,
            'nombre' => $this->nombre,
            'permisos_count' => $this->whenCounted('permisos'),
            'permisos' => PermisoResource::collection($this->whenLoaded('permisos')),
        ];
    }
}
