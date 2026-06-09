<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CU06 — Refactor del módulo de exámenes a CARGA MANUAL de notas por materia.
 *
 * Se elimina el examen en línea (banco de preguntas y ventanas de fecha/hora):
 * el postulante ya no rinde un examen autocorregido ni tiene acceso a fechas;
 * solo consulta sus notas. El staff (Admin/Coordinador) ingresa manualmente la
 * nota de cada materia (Computación, Matemáticas, Inglés, Física) en cada uno
 * de los 3 exámenes.
 *
 * Modelo nuevo:
 *  - evaluacion: cabecera de un examen (1..3) por inscripción; `nota` = promedio
 *    de sus materias.
 *  - nota_materia: la nota (0-100) de una materia dentro de un examen.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Elimina el examen en línea (orden por dependencias FK).
        Schema::dropIfExists('evaluacion_pregunta');
        Schema::dropIfExists('pregunta');
        Schema::dropIfExists('examen_convocatoria');

        // 2. Simplifica `evaluacion`: deja de ser un "intento" en línea.
        if (Schema::hasColumn('evaluacion', 'estado')) {
            // En PostgreSQL, al soltar la columna se elimina su CHECK asociado.
            Schema::table('evaluacion', function (Blueprint $table) {
                $table->dropColumn(['estado', 'iniciado_en', 'finalizado_en']);
            });
        }

        // 3. Nota de cada materia dentro de un examen.
        Schema::create('nota_materia', function (Blueprint $table) {
            $table->id('id_nota_materia');
            $table->foreignId('id_evaluacion')
                ->constrained('evaluacion', 'id_evaluacion')->cascadeOnDelete();
            $table->foreignId('id_materia')
                ->constrained('materia', 'id_materia')->cascadeOnDelete();
            $table->decimal('nota', 5, 2); // 0-100
            $table->unique(['id_evaluacion', 'id_materia']);
        });

        DB::statement('ALTER TABLE nota_materia ADD CONSTRAINT notamateria_nota_check CHECK (nota >= 0 AND nota <= 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_materia');

        Schema::table('evaluacion', function (Blueprint $table) {
            $table->string('estado', 12)->default('EN_CURSO');
            $table->timestamp('iniciado_en')->nullable();
            $table->timestamp('finalizado_en')->nullable();
        });

        // Recreación mínima de las tablas del examen en línea (sin datos).
        Schema::create('examen_convocatoria', function (Blueprint $table) {
            $table->id('id_examen_convocatoria');
            $table->foreignId('id_convocatoria')->constrained('convocatoria', 'id_convocatoria')->cascadeOnDelete();
            $table->integer('numero_examen');
            $table->timestamp('fecha_inicio');
            $table->timestamp('fecha_fin');
            $table->unique(['id_convocatoria', 'numero_examen']);
        });

        Schema::create('pregunta', function (Blueprint $table) {
            $table->id('id_pregunta');
            $table->foreignId('id_materia')->constrained('materia', 'id_materia')->cascadeOnDelete();
            $table->text('enunciado');
            $table->string('opcion_a', 255);
            $table->string('opcion_b', 255);
            $table->string('opcion_c', 255);
            $table->string('opcion_d', 255);
            $table->char('correcta', 1);
            $table->boolean('activa')->default(true);
        });

        Schema::create('evaluacion_pregunta', function (Blueprint $table) {
            $table->id('id_evaluacion_pregunta');
            $table->foreignId('id_evaluacion')->constrained('evaluacion', 'id_evaluacion')->cascadeOnDelete();
            $table->foreignId('id_pregunta')->constrained('pregunta', 'id_pregunta')->cascadeOnDelete();
            $table->char('opcion_elegida', 1)->nullable();
            $table->boolean('es_correcta')->nullable();
            $table->unique(['id_evaluacion', 'id_pregunta']);
        });
    }
};
