<?php

namespace App\Modules\Academics\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GrupoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $usados = $this->inscripciones_count ?? $this->inscripciones()->count();
        $capacidad = (int) $this->capacidad_max;

        return [
            'id_grupo' => $this->id_grupo,
            'sigla' => $this->sigla,
            'nombre' => $this->nombre,
            'turno' => $this->turno,
            'capacidad_max' => $capacidad,
            'cupo_usado' => (int) $usados,
            'cupo_disponible' => max(0, $capacidad - (int) $usados),
            'aula' => $this->whenLoaded('aula', fn () => $this->aula ? [
                'id_aula' => $this->aula->id_aula,
                'nombre' => $this->aula->nombre,
                'capacidad' => $this->aula->capacidad,
            ] : null),
        ];
    }
}
