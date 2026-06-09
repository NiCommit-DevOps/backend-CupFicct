<?php

namespace App\Modules\Exams\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarreraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_carrera' => $this->id_carrera,
            'nombre' => $this->nombre,
            'modalidad' => $this->modalidad,
            'codigo' => $this->codigo,
            'plan' => $this->plan,
            'area' => $this->area,
            'cupos' => $this->cupos,
        ];
    }
}
