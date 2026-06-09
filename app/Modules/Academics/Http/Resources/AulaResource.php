<?php

namespace App\Modules\Academics\Http\Resources;

use App\Modules\Academics\Models\Aula;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AulaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_aula' => $this->id_aula,
            'nombre' => $this->nombre,
            'capacidad' => $this->capacidad,
            'ubicacion' => $this->ubicacion,
            // Campos derivados para precargar el formulario.
            'modulo' => Aula::MODULO,
            'piso' => $this->piso,
            'numero' => $this->numero,
        ];
    }
}
