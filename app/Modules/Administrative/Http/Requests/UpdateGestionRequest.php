<?php

namespace App\Modules\Administrative\Http\Requests;

use App\Modules\Administrative\Models\Gestion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('gestion');

        return [
            'nombre' => ['required', 'string', 'max:50', Rule::unique('gestion', 'nombre')->ignore($id, 'id_gestion')],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'estado' => ['sometimes', Rule::in(Gestion::ESTADOS)],
        ];
    }
}
