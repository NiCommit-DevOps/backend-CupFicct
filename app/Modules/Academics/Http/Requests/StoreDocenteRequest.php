<?php

namespace App\Modules\Academics\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Datos del usuario base (creación dual transaccional).
            'ci' => ['required', 'string', 'max:20', 'unique:usuario,ci'],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'correo' => ['required', 'email', 'max:100', 'unique:usuario,correo'],
            'telefono1' => ['nullable', 'string', 'max:20'],
            'telefono2' => ['nullable', 'string', 'max:20'],
            'fecha_nacimiento' => ['required', 'date', 'before:today'],
            'sexo' => ['nullable', 'in:M,F,Otro'],
            // La contraseña se genera automáticamente (inicial apellidos + "." + CI).

            // Perfil avanzado del docente. Requisitos de contratación: ser
            // profesional en el área, tener maestría y diplomado en educación
            // superior (todos obligatorios para poder contratar al docente).
            'profesion' => ['required', 'string', 'max:100'],
            'carga_horaria' => ['nullable', 'integer', 'min:0'],
            'especialidad' => ['nullable', 'string', 'max:100'],
            'tiene_maestria' => ['required', 'accepted'],
            'tiene_diplomado' => ['required', 'accepted'],

            // Materias que el docente dicta (de 1 a muchas).
            'materias' => ['nullable', 'array'],
            'materias.*' => ['integer', 'exists:materia,id_materia'],

            // Convocatorias (procesos) en las que participa el docente.
            'convocatorias' => ['nullable', 'array'],
            'convocatorias.*' => ['integer', 'exists:convocatoria,id_convocatoria'],

            // Grupos asignados al docente (regla del negocio: de 1 a 4 grupos).
            'grupos' => ['nullable', 'array', 'max:4'],
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
