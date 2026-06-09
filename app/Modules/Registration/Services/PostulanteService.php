<?php

namespace App\Modules\Registration\Services;

use App\Modules\Access\Models\Rol;
use App\Modules\Access\Models\Usuario;
use App\Modules\Administrative\Models\Convocatoria;
use App\Modules\Administrative\Models\Gestion;
use App\Modules\Registration\Models\Inscripcion;
use App\Modules\Registration\Models\Postulante;
use App\Modules\Registration\Repositories\PostulanteRepository;
use App\Modules\Registration\Services\Concerns\AsignaGrupoAutomatico;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostulanteService
{
    use AsignaGrupoAutomatico;

    public function __construct(private readonly PostulanteRepository $postulantes)
    {
    }

    /**
     * @param  array{buscar?:?string,id_gestion?:?int,id_convocatoria?:?int}  $filtros
     */
    public function listar(array $filtros = []): Collection
    {
        return $this->postulantes->all($filtros);
    }

    public function obtener(int $id): Postulante
    {
        return $this->postulantes->find($id) ?? abort(404, 'Postulante no encontrado.');
    }

    /**
     * Convocatoria vigente para inscripción: ABIERTA dentro de una gestión ACTIVA.
     */
    public function convocatoriaVigente(): ?Convocatoria
    {
        return Convocatoria::query()
            ->where('estado', Convocatoria::ESTADO_ABIERTA)
            ->whereHas('gestion', fn ($q) => $q->where('estado', Gestion::ESTADO_ACTIVA))
            ->orderByDesc('id_convocatoria')
            ->first();
    }

    /**
     * Convocatoria visible al público (auto-registro desde la landing).
     *
     * Igual que la vigente, pero además exige que HOY caiga dentro de la ventana
     * de inscripción [fecha_creacion, fecha_limite_inscripcion]. Fuera de esa
     * franja no se expone ninguna convocatoria (la sección no aparece y no se
     * admiten postulaciones).
     */
    public function convocatoriaPublica(): ?Convocatoria
    {
        $hoy = now()->toDateString();

        return Convocatoria::query()
            ->where('estado', Convocatoria::ESTADO_ABIERTA)
            ->whereHas('gestion', fn ($q) => $q->where('estado', Gestion::ESTADO_ACTIVA))
            ->whereDate('fecha_creacion', '<=', $hoy)
            ->whereDate('fecha_limite_inscripcion', '>=', $hoy)
            ->orderByDesc('id_convocatoria')
            ->first();
    }

    /**
     * Auto-registro público del postulante (landing → "Postular").
     *
     * Guarda la SOLICITUD sin habilitar una cuenta funcional: crea el Usuario
     * base con contraseña bloqueada (aleatoria) y EstaActivo=false, junto con su
     * Postulante e Inscripción en estado PENDIENTE, ligado a la convocatoria
     * pública vigente. El staff revisa la solicitud y, si procede, la activa.
     * No se procesa pago en este flujo.
     */
    public function registrarPublico(array $data): Postulante
    {
        $convocatoria = $this->convocatoriaPublica()
            ?? abort(422, 'No hay una convocatoria abierta para inscripción en este momento.');

        return DB::transaction(function () use ($data, $convocatoria) {
            // Sin cuenta funcional: contraseña bloqueada e inactiva hasta revisión.
            $postulante = $this->crearOReutilizarPostulante($data, [
                'contrasena' => Hash::make(Str::random(40)),
                'EstaActivo' => false,
            ]);

            $this->inscribirEnConvocatoria($postulante, $convocatoria->id_convocatoria, $data);

            return $this->obtener($postulante->id_postulante);
        });
    }

    /**
     * CU04 — Alta de postulante por el staff (Registro Dual Transaccional).
     *
     * En una sola transacción crea: Usuario (rol 'Postulante', con contraseña por
     * defecto), Postulante (con código de trámite único), Inscripción (estado
     * PENDIENTE) y la(s) Carrera_Inscripción a la carrera deseada, ligado a la
     * convocatoria vigente. El postulante no se registra solo; lo da de alta el
     * Administrador (espejo del alta de Docentes, CU10).
     */
    public function crear(array $data): Postulante
    {
        $convocatoria = $this->convocatoriaVigente()
            ?? abort(422, 'No hay una convocatoria abierta para inscripción en este momento.');

        return DB::transaction(function () use ($data, $convocatoria) {
            $postulante = $this->crearOReutilizarPostulante($data, [
                // Contraseña por defecto generada a partir de los apellidos + CI.
                'contrasena' => Hash::make(self::contrasenaPorDefecto($data['apellidos'], $data['ci'])),
                'EstaActivo' => true,
            ]);

            $this->inscribirEnConvocatoria($postulante, $convocatoria->id_convocatoria, $data);

            return $this->obtener($postulante->id_postulante);
        });
    }

    /**
     * Crea el usuario+postulante o, si el CI ya existe (la persona vuelve a
     * postular), reutiliza su cuenta y refresca sus datos. El CI es la identidad:
     * una persona = un usuario, con varias inscripciones (una por convocatoria).
     *
     * @param  array<string,mixed>  $atributosNuevoUsuario  extras solo para cuentas nuevas (contraseña, estado)
     */
    private function crearOReutilizarPostulante(array $data, array $atributosNuevoUsuario): Postulante
    {
        $usuario = Usuario::where('ci', $data['ci'])->first();

        // El correo debe ser único frente a OTRAS personas (no contra uno mismo).
        $correoDeOtro = Usuario::where('correo', $data['correo'])
            ->when($usuario, fn ($q) => $q->where('id_usuario', '!=', $usuario->id_usuario))
            ->exists();
        if ($correoDeOtro) {
            abort(422, 'El correo ya está registrado por otra persona.');
        }

        $datosPersonales = [
            'nombres' => $data['nombres'],
            'apellidos' => $data['apellidos'],
            'correo' => $data['correo'],
            'telefono1' => $data['telefono1'] ?? null,
            'telefono2' => $data['telefono2'] ?? null,
            'fecha_nacimiento' => $data['fecha_nacimiento'],
            'sexo' => $data['sexo'] ?? null,
        ];
        $datosEducativos = [
            'id_unidad' => $data['id_unidad'] ?? null,
            'procedencia' => $data['procedencia'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'anio_egreso' => $data['anio_egreso'] ?? null,
            'otros' => $data['otros'] ?? null,
        ];

        if ($usuario) {
            // Misma persona volviendo a postular: reutiliza la cuenta.
            $postulante = Postulante::find($usuario->id_usuario)
                ?? abort(422, 'El CI corresponde a una cuenta que no es de postulante.');

            // Refresca sus datos (sin tocar rol, contraseña ni estado de la cuenta).
            $usuario->update($datosPersonales);
            $postulante->update($datosEducativos + $this->tituloParaActualizar($data, $postulante));

            return $postulante;
        }

        // Persona nueva: crea la cuenta de postulante.
        $usuario = Usuario::create($datosPersonales + [
            'id_rol' => Rol::where('nombre', 'Postulante')->value('id_rol'),
            'ci' => $data['ci'],
        ] + $atributosNuevoUsuario);

        $titulo = $this->resolverTitulo($data);

        return Postulante::create($datosEducativos + [
            'id_postulante' => $usuario->id_usuario,
            'codigo_tramite' => $this->postulantes->siguienteCodigoTramite(),
            'titulo_bachiller' => $titulo['titulo_bachiller'],
            'titulo_archivo' => $titulo['titulo_archivo'],
        ]);
    }

    /**
     * Crea la inscripción del postulante en la convocatoria, evitando duplicar
     * una postulación de la misma persona en la misma convocatoria.
     */
    private function inscribirEnConvocatoria(Postulante $postulante, int $idConvocatoria, array $data): void
    {
        $yaInscrito = Inscripcion::where('id_postulante', $postulante->id_postulante)
            ->where('id_convocatoria', $idConvocatoria)->exists();
        if ($yaInscrito) {
            abort(422, 'Este CI ya tiene una postulación registrada en la convocatoria actual.');
        }

        $inscripcion = Inscripcion::create([
            'id_postulante' => $postulante->id_postulante,
            'id_grupo' => null,
            'id_convocatoria' => $idConvocatoria,
            'turno_preferencia' => $data['turno_preferencia'] ?? null,
            'estado_academico' => Inscripcion::ESTADO_PENDIENTE,
        ]);

        $inscripcion->carreras()->sync($this->mapaCarreras($data));
    }

    /**
     * Campos de título a actualizar cuando la persona vuelve a postular: solo
     * cambian si adjunta un archivo nuevo (que reemplaza al anterior); si no,
     * conserva el título que ya tenía.
     *
     * @return array<string,mixed>
     */
    private function tituloParaActualizar(array $data, Postulante $postulante): array
    {
        $archivo = $data['titulo_archivo'] ?? null;
        if (! $archivo instanceof UploadedFile) {
            return [];
        }

        if ($postulante->titulo_archivo) {
            Storage::disk('local')->delete($postulante->titulo_archivo);
        }

        return ['titulo_bachiller' => true, 'titulo_archivo' => $archivo->store('titulos', 'local')];
    }

    /**
     * El título de bachiller se determina por la presencia de un documento
     * adjunto (PDF o imagen): si se subió, queda Sí + ruta guardada; si no, No.
     *
     * @return array{titulo_bachiller:bool, titulo_archivo:?string}
     */
    private function resolverTitulo(array $data): array
    {
        $archivo = $data['titulo_archivo'] ?? null;

        if ($archivo instanceof UploadedFile) {
            return [
                'titulo_bachiller' => true,
                'titulo_archivo' => $archivo->store('titulos', 'local'),
            ];
        }

        // Sin archivo: por defecto No. La carga masiva por Excel puede pasar el
        // booleano explícito (esa vía no adjunta documento).
        return ['titulo_bachiller' => (bool) ($data['titulo_bachiller'] ?? false), 'titulo_archivo' => null];
    }

    /**
     * CU04 — Modificar datos del postulante.
     *
     * Actualiza en una sola transacción el Usuario base (datos personales, sin
     * tocar la contraseña ni el rol), el perfil Postulante (educativos, dirección,
     * otros) y su Inscripción (turno y carrera de postulación). El CI/correo se
     * validan como únicos ignorando al propio usuario en UpdatePostulanteRequest.
     */
    public function actualizar(int $id, array $data): Postulante
    {
        $postulante = $this->obtener($id);

        return DB::transaction(function () use ($postulante, $data) {
            if ($postulante->usuario) {
                $postulante->usuario->update([
                    'ci' => $data['ci'],
                    'nombres' => $data['nombres'],
                    'apellidos' => $data['apellidos'],
                    'correo' => $data['correo'],
                    'telefono1' => $data['telefono1'] ?? null,
                    'telefono2' => $data['telefono2'] ?? null,
                    'fecha_nacimiento' => $data['fecha_nacimiento'],
                    'sexo' => $data['sexo'] ?? null,
                ]);
            }

            // El título solo cambia si se sube un archivo nuevo (reemplaza al
            // anterior); si no llega archivo, se conservan los valores actuales.
            $titulo = [];
            $archivo = $data['titulo_archivo'] ?? null;
            if ($archivo instanceof UploadedFile) {
                if ($postulante->titulo_archivo) {
                    Storage::disk('local')->delete($postulante->titulo_archivo);
                }
                $titulo = [
                    'titulo_bachiller' => true,
                    'titulo_archivo' => $archivo->store('titulos', 'local'),
                ];
            }

            $postulante->update([
                'id_unidad' => $data['id_unidad'] ?? null,
                'procedencia' => $data['procedencia'] ?? null,
                'direccion' => $data['direccion'] ?? null,
                'anio_egreso' => $data['anio_egreso'] ?? null,
                'otros' => $data['otros'] ?? null,
                ...$titulo,
            ]);

            // La ficha tiene una sola inscripción activa: se actualiza turno y carrera.
            $inscripcion = $postulante->inscripciones()->latest('id_inscripcion')->first();
            if ($inscripcion) {
                $inscripcion->update(['turno_preferencia' => $data['turno_preferencia'] ?? null]);
                $inscripcion->carreras()->sync($this->mapaCarreras($data));
            }

            return $this->obtener($postulante->id_postulante);
        });
    }

    /**
     * CU07 — Mapa de carreras con su orden de preferencia (1ª y 2ª opcional)
     * para sincronizar el pivote carrera_inscripcion.
     *
     * @return array<int,array{orden:int}>
     */
    private function mapaCarreras(array $data): array
    {
        $primera = (int) $data['id_carrera'];
        $mapa = [$primera => ['orden' => 1]];

        $segunda = isset($data['id_carrera_2']) ? (int) $data['id_carrera_2'] : 0;
        if ($segunda > 0 && $segunda !== $primera) {
            $mapa[$segunda] = ['orden' => 2];
        }

        return $mapa;
    }

    /**
     * CU04 — Contraseña por defecto del postulante.
     *
     * Formato: inicial del 1er apellido (mayúscula) + inicial del 2º apellido
     * (minúscula) + "." + CI. Ej: "Verduguez Teran" + 16109930 → "Vt.16109930".
     * Si solo hay un apellido se usa su inicial en mayúscula.
     */
    public static function contrasenaPorDefecto(string $apellidos, string $ci): string
    {
        $partes = preg_split('/\s+/', trim($apellidos), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $iniciales = '';
        if (isset($partes[0])) {
            $iniciales .= mb_strtoupper(mb_substr($partes[0], 0, 1));
        }
        if (isset($partes[1])) {
            $iniciales .= mb_strtolower(mb_substr($partes[1], 0, 1));
        }

        return $iniciales.'.'.trim($ci);
    }

    /**
     * CU04 — Aprueba en lote a todos los postulantes PENDIENTE (los pasa a
     * ELEGIBLE) y notifica a cada uno. Devuelve cuántos se aprobaron.
     */
    public function aprobarTodos(): int
    {
        $pendientes = Inscripcion::where('estado_academico', Inscripcion::ESTADO_PENDIENTE)
            ->with('postulante.usuario')
            ->get();

        foreach ($pendientes as $inscripcion) {
            $inscripcion->update(['estado_academico' => Inscripcion::ESTADO_ELEGIBLE]);

            if ($inscripcion->postulante?->usuario) {
                $inscripcion->postulante->usuario->notify(
                    new \App\Notifications\PostulanteAprobadoNotification(
                        $inscripcion->postulante->usuario,
                        $inscripcion->postulante,
                    )
                );
            }
        }

        return $pendientes->count();
    }

    /**
     * CU04/CU09 — Cambia el estado académico de la inscripción del postulante
     * (p. ej. marcarlo ELEGIBLE para habilitar la asignación de grupos).
     *
     * Si se aprueba (ELEGIBLE), envía una notificación al correo del postulante
     * con las instrucciones para acceder a la pasarela de pago.
     */
    public function cambiarEstado(int $id, string $estado): Postulante
    {
        $postulante = $this->obtener($id);

        $inscripcion = $postulante->inscripciones()->latest('id_inscripcion')->first()
            ?? abort(422, 'El postulante no tiene una inscripción registrada.');

        $inscripcion->update(['estado_academico' => $estado]);

        // CU05 — Notificación: Si se aprueba (ELEGIBLE), envía email con instrucciones de pago.
        if ($estado === Inscripcion::ESTADO_ELEGIBLE && $postulante->usuario) {
            $postulante->usuario->notify(new \App\Notifications\PostulanteAprobadoNotification(
                $postulante->usuario,
                $postulante
            ));
        }

        return $this->obtener($id);
    }

    /**
     * CU04 (Funcionalidad de Testing) — Activar postulante sin pago.
     *
     * Permite que el administrador active un postulante manualmente sin requerir
     * que realice el pago en la pasarela. Útil para pruebas y simulaciones.
     *
     * Realiza:
     * 1. Cambiar estado de inscripción a ELEGIBLE
     * 2. Generar credenciales (contraseña por defecto)
     * 3. Activar la cuenta del usuario
     * 4. Asignar grupo automático (mejor esfuerzo)
     *
     * @return array{usuario:Usuario,inscripcion:Inscripcion,credenciales:array,grupo:?array,estado_academico:string}
     */
    public function activarSinPago(int $id): array
    {
        $postulante = $this->obtener($id);

        $usuario = $postulante->usuario
            ?? abort(422, 'El postulante no tiene usuario asociado.');

        $inscripcion = $postulante->inscripciones()->latest('id_inscripcion')->first()
            ?? abort(422, 'El postulante no tiene una inscripción registrada.');

        // Doble verificación: no reactivar si ya está pagado.
        $pagoaprobado = $inscripcion->pagos()->where('estado', 'APROBADO')->first();
        if ($pagoaprobado) {
            abort(409, 'Este postulante ya tiene un pago aprobado. No se puede activar manualmente.');
        }

        return DB::transaction(function () use ($usuario, $inscripcion, $postulante) {
            // 1. Cambiar estado a ELEGIBLE.
            $inscripcion->update(['estado_academico' => Inscripcion::ESTADO_ELEGIBLE]);

            // 2. Generar credenciales y activar cuenta.
            $contrasena = self::contrasenaPorDefecto($usuario->apellidos, $usuario->ci);
            $usuario->update([
                'contrasena' => Hash::make($contrasena),
                'EstaActivo' => true,
            ]);

            // 3. Asignación automática de grupo (mejor esfuerzo).
            $grupo = null;
            if ($inscripcion->id_grupo === null) {
                $grupo = $this->asignarGrupoAutomatico($inscripcion);
            }

            return [
                'usuario' => $usuario->fresh(),
                'inscripcion' => $inscripcion->fresh(),
                'credenciales' => [
                    'usuario' => $usuario->correo,
                    'contrasena' => $contrasena,
                    'esta_activo' => true,
                ],
                'grupo' => $grupo ? [
                    'id_grupo' => $grupo->id_grupo,
                    'sigla' => $grupo->sigla,
                    'nombre' => $grupo->nombre,
                    'turno' => $grupo->turno,
                ] : null,
                'estado_academico' => Inscripcion::ESTADO_ELEGIBLE,
            ];
        });
    }

    /**
     * CU02/CU04 — Habilitar el acceso del postulante desde la gestión de Usuarios.
     *
     * Flujo de ingreso SIN pago: una vez que el staff aprobó la postulación
     * (inscripción ELEGIBLE), el administrador activa la cuenta desde el módulo de
     * Usuarios. A diferencia de activarSinPago, EXIGE la aprobación previa: la
     * inscripción debe estar ya en ELEGIBLE (la aprobación es un paso aparte en
     * Postulantes). Genera credenciales por defecto, activa la cuenta y asigna
     * grupo automático (mejor esfuerzo).
     *
     * @return array{usuario:Usuario,inscripcion:Inscripcion,credenciales:array,grupo:?array,estado_academico:string}
     */
    public function habilitarDesdeUsuario(int $idUsuario): array
    {
        $usuario = Usuario::with('rol')->find($idUsuario)
            ?? abort(404, 'Usuario no encontrado.');

        if ($usuario->rol?->nombre !== 'Postulante') {
            abort(422, 'Solo se puede habilitar de esta forma a cuentas de postulante. Usa el botón estándar de activar/inhabilitar.');
        }

        $postulante = $this->obtener($usuario->id_usuario);

        $inscripcion = $postulante->inscripciones()->latest('id_inscripcion')->first()
            ?? abort(422, 'El postulante no tiene una inscripción registrada.');

        // Exige aprobación previa: la activación manual no aprueba, solo habilita.
        if ($inscripcion->estado_academico !== Inscripcion::ESTADO_ELEGIBLE) {
            abort(422, 'El postulante debe estar aprobado (ELEGIBLE) antes de activarlo. Apruébalo primero en Postulantes.');
        }

        if ($usuario->EstaActivo) {
            abort(409, 'La cuenta del postulante ya está activa.');
        }

        return DB::transaction(function () use ($usuario, $inscripcion) {
            // Credenciales por defecto + activación de la cuenta.
            $contrasena = self::contrasenaPorDefecto($usuario->apellidos, $usuario->ci);
            $usuario->update([
                'contrasena' => Hash::make($contrasena),
                'EstaActivo' => true,
            ]);

            // Asignación automática de grupo (mejor esfuerzo, respeta turno/cupo).
            $grupo = $inscripcion->id_grupo === null
                ? $this->asignarGrupoAutomatico($inscripcion)
                : $inscripcion->grupo;

            return [
                'usuario' => $usuario->fresh('rol'),
                'inscripcion' => $inscripcion->fresh(),
                'credenciales' => [
                    'usuario' => $usuario->correo,
                    'contrasena' => $contrasena,
                    'esta_activo' => true,
                ],
                'grupo' => $grupo ? [
                    'id_grupo' => $grupo->id_grupo,
                    'sigla' => $grupo->sigla,
                    'nombre' => $grupo->nombre,
                    'turno' => $grupo->turno,
                ] : null,
                'estado_academico' => $inscripcion->estado_academico,
            ];
        });
    }

    /**
     * CU04 — Elimina al postulante. Al borrar el usuario base, la cascada física
     * (ON DELETE CASCADE) arrastra el postulante, su inscripción y carreras.
     */
    public function eliminar(int $id): void
    {
        $postulante = $this->obtener($id);

        if ($postulante->usuario) {
            $postulante->usuario->delete();

            return;
        }

        $postulante->delete();
    }
}
