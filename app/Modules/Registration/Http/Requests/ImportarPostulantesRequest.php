<?php

namespace App\Modules\Registration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CU14 — Validación del archivo de carga masiva de postulantes.
 */
class ImportarPostulantesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // CSV/Excel. Se acepta también texto plano porque algunos navegadores
            // envían el CSV como text/plain.
            'archivo' => ['required', 'file', 'mimes:csv,txt,xls,xlsx', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'archivo.required' => 'Selecciona un archivo CSV o Excel.',
            'archivo.mimes' => 'El archivo debe ser CSV (o Excel guardado como CSV).',
            'archivo.max' => 'El archivo no debe superar 5 MB.',
        ];
    }
}
