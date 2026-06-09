<?php

namespace App\Modules\Administrative\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConvocatoriaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_convocatoria' => $this->id_convocatoria,
            'id_gestion' => $this->id_gestion,
            'nombre' => $this->nombre,
            'fecha_creacion' => $this->fecha_creacion?->format('Y-m-d'),
            'fecha_limite_inscripcion' => $this->fecha_limite_inscripcion?->format('Y-m-d'),
            'estado' => $this->estado,
            'gestion' => new GestionResource($this->whenLoaded('gestion')),
        ];
    }
}
