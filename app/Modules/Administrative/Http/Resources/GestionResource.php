<?php

namespace App\Modules\Administrative\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_gestion' => $this->id_gestion,
            'nombre' => $this->nombre,
            'fecha_inicio' => $this->fecha_inicio?->format('Y-m-d'),
            'fecha_fin' => $this->fecha_fin?->format('Y-m-d'),
            'estado' => $this->estado,
            'convocatorias_count' => $this->whenCounted('convocatorias'),
        ];
    }
}
