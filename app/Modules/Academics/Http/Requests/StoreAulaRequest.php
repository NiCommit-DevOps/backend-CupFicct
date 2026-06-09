<?php

namespace App\Modules\Academics\Http\Requests;

use App\Modules\Academics\Models\Aula;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreAulaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'piso' => ['required', 'integer', 'min:1', 'max:50'],
            'numero' => ['required', 'integer', 'min:1', 'max:'.Aula::MAX_AULA],
            'capacidad' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Verifica que el aula compuesta (piso+numero) no exista ya.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $nombre = Aula::componerNombre((int) $this->piso, (int) $this->numero);
            $query = Aula::where('nombre', $nombre);

            if ($id = $this->route('aula')) {
                $query->where('id_aula', '!=', $id);
            }

            if ($query->exists()) {
                $v->errors()->add('numero', "Ya existe el aula \"{$nombre}\".");
            }
        });
    }

    public function attributes(): array
    {
        return [
            'piso' => 'piso',
            'numero' => 'número de aula',
            'capacidad' => 'capacidad',
        ];
    }
}
