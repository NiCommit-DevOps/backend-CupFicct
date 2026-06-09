<?php

namespace App\Modules\Access\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('rol');

        return [
            'nombre' => ['required', 'string', 'max:50', Rule::unique('rol', 'nombre')->ignore($id, 'id_rol')],
        ];
    }
}
