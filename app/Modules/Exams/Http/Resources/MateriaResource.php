<?php

namespace App\Modules\Exams\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MateriaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_materia' => $this->id_materia,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'docentes_count' => $this->whenCounted('docentes'),
        ];
    }
}
