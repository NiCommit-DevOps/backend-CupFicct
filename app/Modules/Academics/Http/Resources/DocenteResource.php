<?php

namespace App\Modules\Academics\Http\Resources;

use App\Modules\Access\Http\Resources\UsuarioResource;
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
            // Carreras del docente (texto libre) con sus áreas (materias).
            'carreras' => $this->whenLoaded('carreraAreas', fn () => $this->carreraAreas
                ->groupBy('carrera')
                ->map(fn ($filas, $carrera) => [
                    'carrera' => (string) $carrera,
                    'areas' => $filas->map(fn ($f) => [
                        'id_materia' => (int) $f->id_materia,
                        'nombre' => $f->materia?->nombre,
                    ])->values(),
                ])->values()),
            // Unión de áreas: lo que el docente puede enseñar.
            'areas' => $this->whenLoaded('carreraAreas', fn () => $this->carreraAreas
                ->unique('id_materia')
                ->map(fn ($f) => [
                    'id_materia' => (int) $f->id_materia,
                    'nombre' => $f->materia?->nombre,
                ])->values()),
            // Materias que el docente desea/va a enseñar (⊆ unión de áreas).
            'materias' => $this->whenLoaded('materias', fn () => $this->materias->map(fn ($m) => [
                'id_materia' => (int) $m->id_materia,
                'nombre' => $m->nombre,
            ])->values()),
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
