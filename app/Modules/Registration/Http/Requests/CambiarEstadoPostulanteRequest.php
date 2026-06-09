<?php

namespace App\Modules\Registration\Http\Requests;

use App\Modules\Registration\Models\Inscripcion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * CU04/CU09 — Cambio de estado académico de la inscripción del postulante
 * (p. ej. PENDIENTE → ELEGIBLE para habilitarlo a la asignación de grupos).
 */
class CambiarEstadoPostulanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado_academico' => ['required', Rule::in(Inscripcion::ESTADOS)],
        ];
    }
}
