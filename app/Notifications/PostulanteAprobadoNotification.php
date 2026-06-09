<?php

namespace App\Notifications;

use App\Modules\Access\Models\Usuario;
use App\Modules\Registration\Models\Postulante;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * CU05 — Notificación de aprobación del postulante.
 *
 * Se envía cuando el administrador o coordinador académico aprueba al postulante
 * cambiando su estado académico a 'ELEGIBLE'. Contiene instrucciones para
 * acceder a la pasarela de pago.
 */
class PostulanteAprobadoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Usuario $usuario,
        private readonly Postulante $postulante,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ci = $this->usuario->ci;
        $nombreCompleto = $this->usuario->nombres.' '.$this->usuario->apellidos;
        $codigoTramite = $this->postulante->codigo_tramite;

        return (new MailMessage)
            ->subject('✓ Tu solicitud de admisión ha sido aprobada - CUP FICCT')
            ->greeting("¡Hola {$nombreCompleto}!")
            ->line('Nos complace informarte que tu solicitud de admisión ha sido **aprobada** por el coordinador académico.')
            ->line('**Próximo paso: Realiza el pago de inscripción a través de nuestra pasarela segura.**')
            ->line('')
            ->line('**Detalles de tu solicitud:**')
            ->line("- Carnet (CI): **{$ci}**")
            ->line("- Código de trámite: **{$codigoTramite}**")
            ->line("- Estado: **ELEGIBLE** (apto para pago y asignación de grupo)")
            ->line('')
            ->line('Para proceder con el pago de tu cupo de inscripción, ingresa a:')
            ->action('Realizar pago de inscripción', config('app.url').'/pagos')
            ->line('')
            ->line('**Importante:**')
            ->line('- Utiliza el mismo número de carnet (CI) que proporcionaste en tu solicitud.')
            ->line('- La pasarela de pago es segura y está integrada con PayPal.')
            ->line('- Una vez aprobado el pago, tu inscripción será procesada y recibirás confirmación por correo.')
            ->line('')
            ->line('Si tienes dudas o inconvenientes, contáctanos a través del correo institucional.')
            ->salutation('Saludos cordiales,'."\n".'Oficina de Admisión - CUP FICCT');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'postulante_id' => $this->postulante->id_postulante,
            'codigo_tramite' => $this->postulante->codigo_tramite,
            'estado' => 'ELEGIBLE',
        ];
    }
}
