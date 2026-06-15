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
            // Contratación agrupada por carrera: cada carrera con sus materias (áreas).
            'asignaciones' => $this->whenLoaded('asignaciones', fn () => $this->asignaciones
                ->groupBy('id_carrera')
                ->map(fn ($filas) => [
                    'id_carrera' => (int) $filas->first()->id_carrera,
                    'carrera' => $filas->first()->carrera?->nombre,
                    'materias' => $filas->map(fn ($f) => [
                        'id_materia' => (int) $f->id_materia,
                        'nombre' => $f->materia?->nombre,
                    ])->values(),
                ])->values()),
            // Lista plana de materias distintas (para tabla/resumen).
            'materias' => $this->whenLoaded('asignaciones', fn () => $this->asignaciones
                ->unique('id_materia')
                ->map(fn ($f) => [
                    'id_materia' => (int) $f->id_materia,
                    'nombre' => $f->materia?->nombre,
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
