<?php

namespace App\Modules\Academics\Http\Requests;

/**
 * Mismas reglas que la creación; el control de unicidad ya excluye el id actual
 * (vía route('aula')) en StoreAulaRequest::withValidator().
 */
class UpdateAulaRequest extends StoreAulaRequest
{
}
