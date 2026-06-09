<?php

namespace App\Modules\Academics\Http\Resources;

use App\Modules\Access\Http\Resources\UsuarioResource;
use App\Modules\Exams\Http\Resources\MateriaResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocenteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_docente' => $this->id_docente,
            'profesion' => $this->profesion,
            'carga_horaria' => $this->carga_horaria,
            'especialidad' => $this->especialidad,
            'tiene_maestria' => $this->tiene_maestria,
            'tiene_diplomado' => $this->tiene_diplomado,
            'usuario' => new UsuarioResource($this->whenLoaded('usuario')),
            'materias' => MateriaResource::collection($this->whenLoaded('materias')),
            'convocatorias' => $this->whenLoaded('convocatorias', fn () => $this->convocatorias->map(fn ($c) => [
                'id_convocatoria' => $c->id_convocatoria,
                'nombre' => $c->nombre,
                'id_gestion' => $c->id_gestion,
            ])->values()),
            'grupos' => $this->whenLoaded('grupos', fn () => $this->grupos->map(fn ($g) => [
                'id_grupo' => $g->id_grupo,
                'sigla' => $g->sigla,
                'nombre' => $g->nombre,
                'turno' => $g->turno,
            ])->values()),
        ];
    }
}
