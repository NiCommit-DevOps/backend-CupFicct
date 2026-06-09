<?php

namespace App\Modules\Academics\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Requisitos de contratación (profesional + maestría + diplomado).
            'profesion' => ['required', 'string', 'max:100'],
            'carga_horaria' => ['nullable', 'integer', 'min:0'],
            'especialidad' => ['nullable', 'string', 'max:100'],
            'tiene_maestria' => ['required', 'accepted'],
            'tiene_diplomado' => ['required', 'accepted'],
            'materias' => ['sometimes', 'array'],
            'materias.*' => ['integer', 'exists:materia,id_materia'],
            'convocatorias' => ['sometimes', 'array'],
            'convocatorias.*' => ['integer', 'exists:convocatoria,id_convocatoria'],
            // Regla del negocio: de 1 a 4 grupos por docente.
            'grupos' => ['sometimes', 'array', 'max:4'],
            'grupos.*' => ['integer', 'exists:grupo,id_grupo'],
        ];
    }

    public function messages(): array
    {
        return [
            'profesion.required' => 'La profesión es obligatoria: solo se contrata a profesionales del área.',
            'tiene_maestria.accepted' => 'El docente debe contar con maestría para ser contratado.',
            'tiene_diplomado.accepted' => 'El docente debe contar con diplomado en educación superior para ser contratado.',
            'grupos.max' => 'Un docente puede ser asignado a un máximo de 4 grupos.',
        ];
    }
}
