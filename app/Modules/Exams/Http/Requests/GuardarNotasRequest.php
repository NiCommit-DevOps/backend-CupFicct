<?php

namespace App\Modules\Exams\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CU06 — Validación de la carga manual de notas por materia.
 *
 * Cada ítem es la nota de una materia dentro de un examen (1..3). La nota debe
 * estar entre 0 y 100; `null` borra esa nota.
 */
class GuardarNotasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notas' => ['present', 'array'],
            'notas.*.numero_examen' => ['required', 'integer', 'between:1,3'],
            'notas.*.id_materia' => ['required', 'integer', 'exists:materia,id_materia'],
            'notas.*.nota' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'notas.*.nota.min' => 'Las notas deben estar entre 0 y 100.',
            'notas.*.nota.max' => 'Las notas deben estar entre 0 y 100.',
        ];
    }
}
