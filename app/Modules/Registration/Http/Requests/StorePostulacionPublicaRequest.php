<?php

namespace App\Modules\Registration\Http\Requests;

/**
 * Validación del auto-registro público de postulantes (landing → "Postular").
 *
 * Reutiliza íntegramente las reglas del alta interna del staff (StorePostulanteRequest):
 * mismos campos, misma unicidad de CI/correo. La diferencia está en el servicio
 * (registrarPublico: cuenta inactiva, sin contraseña funcional), no en la validación.
 */
class StorePostulacionPublicaRequest extends StorePostulanteRequest
{
}
