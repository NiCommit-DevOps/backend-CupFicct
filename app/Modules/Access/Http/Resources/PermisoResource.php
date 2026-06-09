<?php

namespace App\Modules\Access\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermisoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_permiso' => $this->id_permiso,
            'modulo' => $this->modulo,
            'descripcion' => $this->descripcion,
        ];
    }
}
