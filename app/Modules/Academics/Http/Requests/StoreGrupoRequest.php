<?php

namespace App\Modules\Academics\Http\Requests;

use App\Modules\Academics\Models\Grupo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGrupoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sigla' => ['required', 'string', 'max:20', 'unique:grupo,sigla'],
            'nombre' => ['required', 'string', 'max:50'],
            'turno' => ['required', Rule::in(Grupo::TURNOS)],
            'capacidad_max' => ['required', 'integer', 'min:1'],
            'id_aula' => ['nullable', 'integer', 'exists:aula,id_aula'],
        ];
    }
}
