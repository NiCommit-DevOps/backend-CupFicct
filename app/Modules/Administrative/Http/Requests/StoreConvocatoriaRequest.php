<?php

namespace App\Modules\Administrative\Http\Requests;

use App\Modules\Administrative\Models\Convocatoria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConvocatoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_gestion' => ['required', 'integer', 'exists:gestion,id_gestion'],
            'nombre' => ['required', 'string', 'max:50'],
            'fecha_limite_inscripcion' => ['required', 'date'],
            'estado' => ['sometimes', Rule::in(Convocatoria::ESTADOS)],
        ];
    }
}
