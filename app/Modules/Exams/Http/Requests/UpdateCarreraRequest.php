<?php

namespace App\Modules\Exams\Http\Requests;

use App\Modules\Exams\Models\Carrera;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCarreraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('carrera');

        return [
            'nombre' => ['required', 'string', 'max:100'],
            'codigo' => ['required', 'string', 'max:20', Rule::unique('carrera', 'codigo')->ignore($id, 'id_carrera')],
            'modalidad' => ['required', Rule::in(Carrera::MODALIDADES)],
            'area' => ['required', Rule::in(Carrera::AREAS)],
            'plan' => ['nullable', 'string', 'max:50'],
            'cupos' => ['required', 'integer', 'min:0'],
        ];
    }
}
