<?php

namespace App\Modules\Exams\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CU06 — Validación de la carga masiva de notas por archivo (Excel/CSV).
 *
 * Se carga sobre una convocatoria concreta: cada fila del archivo se cruza por
 * CI con la inscripción del postulante en esa convocatoria.
 */
class ImportarNotasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'archivo' => ['required', 'file', 'mimes:csv,txt,xls,xlsx', 'max:5120'],
            'id_convocatoria' => ['required', 'integer', 'exists:convocatoria,id_convocatoria'],
        ];
    }

    public function messages(): array
    {
        return [
            'archivo.required' => 'Selecciona un archivo CSV o Excel.',
            'archivo.mimes' => 'El archivo debe ser CSV o Excel (.xlsx).',
            'archivo.max' => 'El archivo no debe superar 5 MB.',
            'id_convocatoria.required' => 'Selecciona la convocatoria sobre la que cargar las notas.',
            'id_convocatoria.exists' => 'La convocatoria seleccionada no existe.',
        ];
    }
}
