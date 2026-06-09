<?php

namespace App\Modules\Administrative\Http\Requests;

use App\Modules\Administrative\Models\Gestion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CambiarEstadoGestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::in(Gestion::ESTADOS)],
        ];
    }
}
