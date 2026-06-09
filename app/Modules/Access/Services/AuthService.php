<?php

namespace App\Modules\Access\Services;

use App\Modules\Access\Models\Usuario;
use App\Modules\Access\Repositories\UsuarioRepository;
use App\Modules\Administrative\Models\Convocatoria;
use App\Modules\Registration\Models\Inscripcion;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthService
{
    /**
     * Roles permitidos por "tipo de cuenta" del portal (pestaña de login).
     * "administrativo" agrupa Coordinador Académico + Administrador;
     * "estudiante" corresponde al Postulante.
     */
    private const TIPO_ROLES = [
        'estudiante' => ['Postulante'],
        'docente' => ['Docente'],
        'administrativo' => ['Administrador', 'Coordinador Académico'],
    ];

    public function __construct(private readonly UsuarioRepository $usuarios)
    {
    }

    /**
     * CU01 — Autenticación unificada (correo o ci) + validación de estado.
     * Si se indica $tipo (pestaña del portal), la cuenta debe pertenecer a un
     * rol permitido para ese tipo; si no, se rechaza sin emitir token.
     *
     * @return array{token: string, usuario: Usuario}
     */
    public function login(string $login, string $password, ?string $tipo = null): array
    {
        $usuario = $this->usuarios->findByLogin($login);

        if (! $usuario || ! Hash::check($password, $usuario->getAuthPassword())) {
            throw new AuthenticationException('Credenciales inválidas.');
        }

        if (! $usuario->EstaActivo) {
            throw new HttpException(403, 'La cuenta está inhabilitada.');
        }

        if ($tipo !== null) {
            $usuario->loadMissing('rol');
            $rolesPermitidos = self::TIPO_ROLES[$tipo] ?? [];
            if (! in_array($usuario->rol?->nombre, $rolesPermitidos, true)) {
                throw new HttpException(422, 'Esta cuenta no corresponde al perfil seleccionado. Elige el tipo de cuenta correcto.');
            }
        }

        // Postulantes y docentes no pueden ingresar si su convocatoria ya fue
        // dada por concluida por el administrador (CU19).
        $this->verificarConvocatoriaVigente($usuario);

        $token = Auth::guard('api')->login($usuario);

        return ['token' => $token, 'usuario' => $usuario];
    }

    /**
     * Bloquea el acceso de postulantes y docentes cuando su convocatoria ya fue
     * concluida (CU19). El resto del staff (Administrador/Coordinador) no se ve
     * afectado.
     *
     * - Postulante: se bloquea si la convocatoria de su inscripción más reciente
     *   está CONCLUIDA.
     * - Docente: se bloquea si participa en alguna convocatoria pero ninguna
     *   sigue activa (todas concluidas); si tiene al menos una vigente, ingresa.
     */
    private function verificarConvocatoriaVigente(Usuario $usuario): void
    {
        $usuario->loadMissing('rol');
        $rol = $usuario->rol?->nombre;

        if ($rol === 'Postulante') {
            $estado = Inscripcion::where('inscripcion.id_postulante', $usuario->id_usuario)
                ->join('convocatoria', 'convocatoria.id_convocatoria', '=', 'inscripcion.id_convocatoria')
                ->orderByDesc('inscripcion.id_inscripcion')
                ->value('convocatoria.estado');

            if ($estado === Convocatoria::ESTADO_CONCLUIDA) {
                throw new HttpException(403, 'La convocatoria en la que estás inscrito ya fue concluida. Ya no es posible ingresar al sistema.');
            }

            return;
        }

        if ($rol === 'Docente') {
            $asignaciones = DB::table('docente_convocatoria')
                ->join('convocatoria', 'convocatoria.id_convocatoria', '=', 'docente_convocatoria.id_convocatoria')
                ->where('docente_convocatoria.id_docente', $usuario->id_usuario);

            $tieneAlguna = (clone $asignaciones)->exists();
            $tieneVigente = (clone $asignaciones)
                ->where('convocatoria.estado', '!=', Convocatoria::ESTADO_CONCLUIDA)
                ->exists();

            if ($tieneAlguna && ! $tieneVigente) {
                throw new HttpException(403, 'Las convocatorias en las que participas ya fueron concluidas. Ya no es posible ingresar al sistema.');
            }
        }
    }

    /**
     * CU01 — Emisión del contexto de seguridad (usuario + permisos).
     */
    public function me(): Usuario
    {
        /** @var Usuario $usuario */
        $usuario = Auth::guard('api')->user();
        $usuario->loadMissing('rol.permisos');

        return $usuario;
    }

    /**
     * CU01 — Cierre seguro de sesión (invalida el token actual).
     */
    public function logout(): void
    {
        Auth::guard('api')->logout();
    }

    public function refresh(): string
    {
        return Auth::guard('api')->refresh();
    }

    public function ttlSegundos(): int
    {
        return Auth::guard('api')->factory()->getTTL() * 60;
    }
}
