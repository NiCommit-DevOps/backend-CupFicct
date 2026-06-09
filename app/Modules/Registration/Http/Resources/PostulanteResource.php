<?php

namespace App\Modules\Registration\Http\Resources;

use App\Modules\Access\Http\Resources\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostulanteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_postulante' => $this->id_postulante,
            'codigo_tramite' => $this->codigo_tramite,
            'procedencia' => $this->procedencia,
            'direccion' => $this->direccion,
            'titulo_bachiller' => $this->titulo_bachiller,
            'tiene_titulo_archivo' => (bool) $this->titulo_archivo,
            'anio_egreso' => $this->anio_egreso,
            'otros' => $this->otros,
            'usuario' => new UsuarioResource($this->whenLoaded('usuario')),
            'unidad_educativa' => $this->whenLoaded('unidad', fn () => $this->unidad ? [
                'id_unidad' => $this->unidad->id_unidad,
                'nombre' => $this->unidad->nombre,
            ] : null),
            'inscripcion' => $this->whenLoaded('inscripciones', function () {
                $insc = $this->inscripciones->first();

                if (! $insc) {
                    return null;
                }

                return [
                    'id_inscripcion' => $insc->id_inscripcion,
                    'estado_academico' => $insc->estado_academico,
                    'turno_preferencia' => $insc->turno_preferencia,
                    'fecha_inscripcion' => $insc->fecha_inscripcion?->toDateString(),
                    'promedio_final' => $insc->promedio_final !== null ? (float) $insc->promedio_final : null,
                    'convocatoria' => $insc->relationLoaded('convocatoria') && $insc->convocatoria ? [
                        'id_convocatoria' => $insc->convocatoria->id_convocatoria,
                        'nombre' => $insc->convocatoria->nombre,
                    ] : null,
                    // Carreras ordenadas por preferencia (orden 1 = primera opción).
                    'carreras' => $insc->relationLoaded('carreras')
                        ? $insc->carreras->map(fn ($c) => [
                            'id_carrera' => $c->id_carrera,
                            'nombre' => $c->nombre,
                            'orden' => (int) ($c->pivot->orden ?? 1),
                        ])->values()
                        : [],
                    'carrera_admitida' => $insc->id_carrera_admitida,
                ];
            }),
        ];
    }
}
