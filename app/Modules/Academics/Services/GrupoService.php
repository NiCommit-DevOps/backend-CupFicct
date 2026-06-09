<?php

namespace App\Modules\Academics\Services;

use App\Modules\Academics\Models\Aula;
use App\Modules\Academics\Models\Grupo;
use App\Modules\Academics\Repositories\GrupoRepository;
use App\Modules\Administrative\Models\Convocatoria;
use App\Modules\Registration\Models\Inscripcion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CU09 — Gestionar Asignación de Grupos.
 */
class GrupoService
{
    private const ESTADO_ELEGIBLE = 'ELEGIBLE';

    public function __construct(private readonly GrupoRepository $grupos)
    {
    }

    public function listar(): Collection
    {
        $idConvocatoria = $this->convocatoriaActivaId();

        // Sin convocatoria activa no hay grupos del proceso vigente que mostrar.
        return $idConvocatoria ? $this->grupos->all($idConvocatoria) : new Collection();
    }

    public function obtener(int $id): Grupo
    {
        return $this->grupos->find($id) ?? abort(404, 'Grupo no encontrado.');
    }

    public function crear(array $data): Grupo
    {
        $this->validarCapacidadAula($data['capacidad_max'] ?? null, $data['id_aula'] ?? null);

        // El grupo pertenece a la convocatoria activa del proceso.
        $data['id_convocatoria'] = $this->convocatoriaActivaOExcepcion()->id_convocatoria;

        return $this->grupos->create($data);
    }

    /** Id de la convocatoria activa (no concluida) o null si no hay ninguna. */
    private function convocatoriaActivaId(): ?int
    {
        return Convocatoria::activa()?->id_convocatoria;
    }

    /** Convocatoria activa o falla con un mensaje claro si no hay ninguna abierta. */
    private function convocatoriaActivaOExcepcion(): Convocatoria
    {
        return Convocatoria::activa()
            ?? abort(422, 'No hay una convocatoria activa. Aperture una convocatoria antes de crear grupos.');
    }

    public function actualizar(int $id, array $data): Grupo
    {
        $grupo = $this->obtener($id);

        $capacidad = $data['capacidad_max'] ?? $grupo->capacidad_max;
        $idAula = array_key_exists('id_aula', $data) ? $data['id_aula'] : $grupo->id_aula;
        $this->validarCapacidadAula($capacidad, $idAula);

        return $this->grupos->update($grupo, $data);
    }

    public function eliminar(int $id): void
    {
        // Al borrar el grupo, la FK (ON DELETE SET NULL) libera las inscripciones.
        $this->grupos->delete($this->obtener($id));
    }

    /**
     * Excepción del CU: si la capacidad del aula es menor que la del grupo,
     * se rechaza la operación.
     */
    private function validarCapacidadAula(?int $capacidadMax, ?int $idAula): void
    {
        if ($idAula === null || $capacidadMax === null) {
            return;
        }

        $aula = Aula::find($idAula);
        if ($aula && $aula->capacidad < $capacidadMax) {
            abort(422, "La capacidad del aula ({$aula->capacidad}) es menor que la del grupo ({$capacidadMax}). Elige un aula más grande o reduce la capacidad del grupo.");
        }
    }

    /* ===================== Asignación de postulantes ===================== */

    /**
     * Inscripciones ELEGIBLE (postulantes habilitados) para el panel de asignación.
     */
    public function inscripcionesElegibles(): Collection
    {
        $idConvocatoria = $this->convocatoriaActivaId();
        if (! $idConvocatoria) {
            return new Collection();
        }

        return Inscripcion::query()
            ->where('estado_academico', self::ESTADO_ELEGIBLE)
            ->where('id_convocatoria', $idConvocatoria)
            ->with(['postulante.usuario', 'carreras', 'grupo'])
            ->orderBy('id_inscripcion')
            ->get();
    }

    /**
     * Cálculo automático del módulo de asignación: total de inscritos habilitados
     * (ELEGIBLE), cantidad de grupos necesarios con capacidad 70 (= techo(total/70))
     * y la distribución pareja de estudiantes por grupo.
     *
     * @return array{total_inscritos:int, capacidad_grupo:int, grupos_necesarios:int, estudiantes_por_grupo:int[], grupos_creados:int, asignados:int, sin_asignar:int}
     */
    public function resumen(): array
    {
        $cap = Grupo::CAPACIDAD_ESTANDAR;
        $idConvocatoria = $this->convocatoriaActivaId();

        $baseElegibles = Inscripcion::where('estado_academico', self::ESTADO_ELEGIBLE)
            ->when($idConvocatoria, fn ($q, $id) => $q->where('id_convocatoria', $id));

        // Sin convocatoria activa: no hay nada del proceso vigente que contar.
        $total = $idConvocatoria ? (int) (clone $baseElegibles)->count() : 0;
        $gruposNecesarios = (int) ceil($total / $cap);

        // Reparto parejo: 'resto' grupos llevan uno más que el resto.
        $distribucion = [];
        if ($gruposNecesarios > 0) {
            $base = intdiv($total, $gruposNecesarios);
            $resto = $total % $gruposNecesarios;
            for ($i = 0; $i < $gruposNecesarios; $i++) {
                $distribucion[] = $base + ($i < $resto ? 1 : 0);
            }
        }

        $gruposCreados = $idConvocatoria
            ? (int) Grupo::where('id_convocatoria', $idConvocatoria)->count()
            : 0;
        $asignados = $idConvocatoria
            ? (int) (clone $baseElegibles)->whereNotNull('id_grupo')->count()
            : 0;

        return [
            'total_inscritos' => $total,
            'capacidad_grupo' => $cap,
            'grupos_necesarios' => $gruposNecesarios,
            'estudiantes_por_grupo' => $distribucion,
            'grupos_creados' => $gruposCreados,
            'asignados' => $asignados,
            'sin_asignar' => $total - $asignados,
        ];
    }

    /**
     * CU09 — Creación automática de grupos para el curso de nivelación.
     *
     * Con los postulantes ya habilitados (ELEGIBLE), calcula los grupos
     * necesarios de forma GLOBAL: techo(total / 70). Reparte esa cantidad entre
     * los turnos Mañana y Tarde de forma proporcional a la demanda (preferencia
     * de cada postulante), crea los grupos que falten —con sigla, nombre y aula
     * automáticos— y distribuye a los elegibles (mejor esfuerzo por turno).
     *
     * @return array{total_inscritos:int, grupos_necesarios:int, grupos_creados:int, grupos_total:int, asignados:int, sin_cupo:int}
     */
    public function crearGruposAutomatico(): array
    {
        $idConvocatoria = $this->convocatoriaActivaOExcepcion()->id_convocatoria;

        return DB::transaction(function () use ($idConvocatoria) {
            $cap = Grupo::CAPACIDAD_ESTANDAR; // 70
            $elegibles = Inscripcion::where('estado_academico', self::ESTADO_ELEGIBLE)
                ->where('id_convocatoria', $idConvocatoria)->get();
            $total = $elegibles->count();

            if ($total === 0) {
                abort(422, 'No hay postulantes habilitados (ELEGIBLE) en la convocatoria activa para formar grupos. Activa postulantes antes de crear los grupos.');
            }

            // Cantidad de grupos GLOBAL, según el enunciado: techo(total / 70).
            $n = (int) ceil($total / $cap);

            // Demanda por turno: 'TARDE' explícito; el resto (mañana/noche/sin dato) cuenta como Mañana.
            $countT = $elegibles->filter(fn ($i) => $i->turno_preferencia === 'TARDE')->count();
            $countM = $total - $countT;

            // Reparto proporcional de los n grupos entre Mañana y Tarde,
            // garantizando al menos un grupo por turno cuando hay demanda.
            $gruposT = (int) round($n * $countT / $total);
            $gruposT = max($countT > 0 ? 1 : 0, $gruposT);
            $gruposT = min($gruposT, $countM > 0 ? $n - 1 : $n);
            $gruposM = $n - $gruposT;

            // Lo que ya existe por turno EN ESTA convocatoria (creación incremental).
            $existentes = Grupo::where('id_convocatoria', $idConvocatoria)->get();
            $existM = $existentes->filter(fn ($g) => Grupo::turnoNormalizado($g->turno) === 'MAÑANA')->count();
            $existT = $existentes->filter(fn ($g) => Grupo::turnoNormalizado($g->turno) === 'TARDE')->count();

            $aulasLibres = $this->aulasLibres($cap, $idConvocatoria);
            $creados = 0;

            foreach (['Mañana' => max(0, $gruposM - $existM), 'Tarde' => max(0, $gruposT - $existT)] as $turno => $cantidad) {
                for ($k = 0; $k < $cantidad; $k++) {
                    [$sigla, $nombre] = $this->siguienteSiglaNombre($turno);
                    $this->grupos->create([
                        'sigla' => $sigla,
                        'nombre' => $nombre,
                        'turno' => $turno,
                        'capacidad_max' => $cap,
                        'id_aula' => $this->siguienteAula($aulasLibres, $cap),
                        'id_convocatoria' => $idConvocatoria,
                    ]);
                    $creados++;
                }
            }

            $reparto = $this->distribuirElegibles($idConvocatoria);

            return [
                'total_inscritos' => $total,
                'grupos_necesarios' => $n,
                'grupos_creados' => $creados,
                'grupos_total' => (int) Grupo::where('id_convocatoria', $idConvocatoria)->count(),
                'asignados' => $reparto['asignados'],
                'sin_cupo' => $reparto['sin_cupo'],
            ];
        });
    }

    /**
     * Reparte a TODOS los elegibles en los grupos existentes (limpia y reasigna
     * desde cero para un reparto limpio): primero en un grupo de su turno con
     * cupo y, si no hay, en cualquiera con cupo (turno forzado).
     *
     * @return array{asignados:int, sin_cupo:int}
     */
    private function distribuirElegibles(int $idConvocatoria): array
    {
        $grupos = $this->grupos->all($idConvocatoria);

        $restante = [];   // id_grupo => cupo disponible
        $porTurno = [];   // turno normalizado => [id_grupo, ...]
        foreach ($grupos as $g) {
            $restante[$g->id_grupo] = $g->capacidad_max;
            $porTurno[Grupo::turnoNormalizado($g->turno)][] = $g->id_grupo;
        }
        $todos = $grupos->pluck('id_grupo')->all();

        // Limpia asignaciones previas (de esta convocatoria) para repartir desde cero.
        Inscripcion::where('estado_academico', self::ESTADO_ELEGIBLE)
            ->where('id_convocatoria', $idConvocatoria)->update(['id_grupo' => null]);

        $elegibles = Inscripcion::where('estado_academico', self::ESTADO_ELEGIBLE)
            ->where('id_convocatoria', $idConvocatoria)->get();
        $asignados = 0;
        $sinCupo = 0;

        foreach ($elegibles as $insc) {
            $idGrupo = $this->grupoConCupo($porTurno[$insc->turno_preferencia] ?? [], $restante)
                ?? $this->grupoConCupo($todos, $restante);

            if ($idGrupo === null) {
                $sinCupo++;

                continue;
            }

            $insc->id_grupo = $idGrupo;
            $insc->save();
            $restante[$idGrupo]--;
            $asignados++;
        }

        return ['asignados' => $asignados, 'sin_cupo' => $sinCupo];
    }

    /** Primer grupo de la lista que aún tenga cupo disponible. */
    private function grupoConCupo(array $ids, array $restante): ?int
    {
        foreach ($ids as $id) {
            if (($restante[$id] ?? 0) > 0) {
                return $id;
            }
        }

        return null;
    }

    /**
     * Aulas con capacidad suficiente que no estén ya ocupadas por otro grupo.
     *
     * @return array<int,int> ids de aula
     */
    private function aulasLibres(int $capMin, int $idConvocatoria): array
    {
        // Solo se consideran ocupadas las aulas en uso por la convocatoria activa;
        // las de procesos concluidos quedan libres para reutilizarse.
        $ocupadas = Grupo::where('id_convocatoria', $idConvocatoria)
            ->whereNotNull('id_aula')->pluck('id_aula')->all();

        return Aula::where('capacidad', '>=', $capMin)
            ->whereNotIn('id_aula', $ocupadas)
            ->orderBy('nombre')
            ->pluck('id_aula')
            ->all();
    }

    /** Toma la siguiente aula libre; si no hay, crea una nueva con la convención del CU17. */
    private function siguienteAula(array &$libres, int $cap): int
    {
        if ($libres !== []) {
            return (int) array_shift($libres);
        }

        for ($piso = 1; $piso <= 50; $piso++) {
            for ($numero = 1; $numero <= Aula::MAX_AULA; $numero++) {
                $nombre = Aula::componerNombre($piso, $numero);
                if (! Aula::where('nombre', $nombre)->exists()) {
                    return (int) Aula::create([
                        'nombre' => $nombre,
                        'ubicacion' => Aula::componerUbicacion($piso),
                        'capacidad' => $cap,
                    ])->id_aula;
                }
            }
        }

        abort(422, 'No se pudo asignar ni crear un aula disponible para el grupo.');
    }

    /**
     * Siguiente sigla/nombre para un turno, continuando la numeración existente.
     * Mañana → ["M1","M001"], Tarde → ["T1","T001"], etc.
     *
     * @return array{0:string,1:string}
     */
    private function siguienteSiglaNombre(string $turno): array
    {
        $letra = $turno === 'Tarde' ? 'T' : 'M';

        $max = 0;
        foreach (Grupo::where('sigla', 'LIKE', $letra.'%')->pluck('sigla') as $sigla) {
            if (preg_match('/^'.$letra.'(\d+)$/', $sigla, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        $n = $max + 1;
        while (Grupo::where('sigla', $letra.$n)->exists()) {
            $n++;
        }

        return [$letra.$n, sprintf('%s%03d', $letra, $n)];
    }

    /** Cupo usado actual de un grupo (inscripciones asignadas). */
    private function cupoUsado(int $idGrupo): int
    {
        return Inscripcion::where('id_grupo', $idGrupo)->count();
    }

    private function obtenerInscripcion(int $idInscripcion): Inscripcion
    {
        return Inscripcion::find($idInscripcion) ?? abort(404, 'Inscripción no encontrada.');
    }

    /**
     * Asignación manual de un postulante (inscripción) a un grupo.
     * Excepciones: el postulante debe estar ELEGIBLE y el grupo no debe estar lleno.
     */
    public function asignar(int $idInscripcion, int $idGrupo): Inscripcion
    {
        $inscripcion = $this->obtenerInscripcion($idInscripcion);
        $grupo = $this->obtener($idGrupo);

        if ($inscripcion->estado_academico !== self::ESTADO_ELEGIBLE) {
            abort(422, 'Solo se pueden asignar postulantes con estado ELEGIBLE.');
        }

        // Si ya estaba en ese grupo no cuenta doble; si es otro grupo, valida cupo.
        if ($inscripcion->id_grupo !== $idGrupo && $this->cupoUsado($idGrupo) >= $grupo->capacidad_max) {
            abort(422, "El grupo {$grupo->sigla} está lleno (capacidad {$grupo->capacidad_max}).");
        }

        $inscripcion->id_grupo = $idGrupo;
        $inscripcion->save();

        return $inscripcion->load(['postulante.usuario', 'carreras', 'grupo']);
    }

    public function desasignar(int $idInscripcion): Inscripcion
    {
        $inscripcion = $this->obtenerInscripcion($idInscripcion);
        $inscripcion->id_grupo = null;
        $inscripcion->save();

        return $inscripcion->load(['postulante.usuario', 'carreras', 'grupo']);
    }

    /**
     * Funcionalidad extra: asignación automática en lote.
     * Reparte los ELEGIBLE sin grupo en grupos con cupo, respetando el turno.
     *
     * @return array{asignados:int, sin_cupo:int, total:int}
     */
    public function asignarLote(): array
    {
        $idConvocatoria = $this->convocatoriaActivaId();
        if (! $idConvocatoria) {
            return ['asignados' => 0, 'sin_cupo' => 0, 'total' => 0];
        }

        return DB::transaction(function () use ($idConvocatoria) {
            $grupos = $this->grupos->all($idConvocatoria);
            $restante = [];          // id_grupo => cupo disponible
            $porTurno = [];          // turno normalizado => [id_grupo, ...]
            foreach ($grupos as $g) {
                $restante[$g->id_grupo] = $g->capacidad_max - $this->cupoUsado($g->id_grupo);
                $porTurno[Grupo::turnoNormalizado($g->turno)][] = $g->id_grupo;
            }

            $pendientes = Inscripcion::where('estado_academico', self::ESTADO_ELEGIBLE)
                ->where('id_convocatoria', $idConvocatoria)
                ->whereNull('id_grupo')->get();

            $asignados = 0;
            $sinCupo = 0;
            foreach ($pendientes as $insc) {
                $idGrupo = $this->primerGrupoConCupo($insc->turno_preferencia, $porTurno, $restante);
                if ($idGrupo === null) {
                    $sinCupo++;
                    continue;
                }
                $insc->id_grupo = $idGrupo;
                $insc->save();
                $restante[$idGrupo]--;
                $asignados++;
            }

            return ['asignados' => $asignados, 'sin_cupo' => $sinCupo, 'total' => $pendientes->count()];
        });
    }

    /**
     * Funcionalidad extra: rebalanceo. Limpia las asignaciones de los ELEGIBLE y
     * los redistribuye de forma pareja (round-robin) por turno, respetando la
     * capacidad vigente de cada grupo. Útil cuando cambia la capacidad.
     *
     * @return array{reasignados:int, sin_cupo:int}
     */
    public function rebalancear(): array
    {
        $idConvocatoria = $this->convocatoriaActivaId();
        if (! $idConvocatoria) {
            return ['reasignados' => 0, 'sin_cupo' => 0];
        }

        return DB::transaction(function () use ($idConvocatoria) {
            $grupos = $this->grupos->all($idConvocatoria);
            $porTurno = [];   // turno normalizado => [Grupo, ...]
            foreach ($grupos as $g) {
                $porTurno[Grupo::turnoNormalizado($g->turno)][] = $g;
            }

            // Libera las asignaciones ELEGIBLE (de esta convocatoria) para repartir desde cero.
            $base = Inscripcion::where('estado_academico', self::ESTADO_ELEGIBLE)
                ->where('id_convocatoria', $idConvocatoria);
            $elegibles = (clone $base)->get();
            (clone $base)->update(['id_grupo' => null]);

            $contador = [];   // id_grupo => asignados en este reparto
            $reasignados = 0;
            $sinCupo = 0;

            foreach ($elegibles as $insc) {
                $candidatos = $porTurno[$insc->turno_preferencia] ?? [];
                // Round-robin: elige el grupo con menos asignados que aún tenga cupo.
                $mejor = null;
                foreach ($candidatos as $g) {
                    $usados = $contador[$g->id_grupo] ?? 0;
                    if ($usados < $g->capacidad_max && ($mejor === null || $usados < ($contador[$mejor->id_grupo] ?? 0))) {
                        $mejor = $g;
                    }
                }
                if ($mejor === null) {
                    $sinCupo++;
                    continue;
                }
                $insc->id_grupo = $mejor->id_grupo;
                $insc->save();
                $contador[$mejor->id_grupo] = ($contador[$mejor->id_grupo] ?? 0) + 1;
                $reasignados++;
            }

            return ['reasignados' => $reasignados, 'sin_cupo' => $sinCupo];
        });
    }

    /** Primer grupo con cupo para el turno dado (o cualquiera si no tiene preferencia). */
    private function primerGrupoConCupo(?string $turnoPreferencia, array $porTurno, array $restante): ?int
    {
        $candidatos = $turnoPreferencia !== null
            ? ($porTurno[$turnoPreferencia] ?? [])
            : array_merge(...array_values($porTurno) ?: [[]]);

        foreach ($candidatos as $idGrupo) {
            if (($restante[$idGrupo] ?? 0) > 0) {
                return $idGrupo;
            }
        }

        return null;
    }
}
