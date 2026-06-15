<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CU10 — Flujo de contratación del docente:
 *  - `docente_carrera_area`: por cada carrera (TEXTO LIBRE, no es la carrera de
 *    inscripción) las áreas (materias) en las que es profesional. La unión de
 *    áreas define qué puede enseñar.
 *  - `docente_materia`: las materias que el docente desea/va a enseñar; deben
 *    estar dentro de la unión de áreas (se valida en el FormRequest).
 *
 * Reemplaza `docente_carrera_materia` (que ligaba a la carrera de inscripción).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docente_carrera_area', function (Blueprint $table) {
            $table->id('id_docente_carrera_area');
            $table->unsignedBigInteger('id_docente');
            $table->foreign('id_docente')
                ->references('id_docente')->on('docente')->cascadeOnDelete();
            $table->string('carrera', 120);
            $table->foreignId('id_materia')
                ->constrained('materia', 'id_materia')->cascadeOnDelete();
            $table->unique(['id_docente', 'carrera', 'id_materia']);
        });

        if (! Schema::hasTable('docente_materia')) {
            Schema::create('docente_materia', function (Blueprint $table) {
                $table->id('id_docente_materia');
                $table->unsignedBigInteger('id_docente');
                $table->foreign('id_docente')
                    ->references('id_docente')->on('docente')->cascadeOnDelete();
                $table->foreignId('id_materia')
                    ->constrained('materia', 'id_materia')->cascadeOnDelete();
                $table->unique(['id_docente', 'id_materia']);
            });
        }

        Schema::dropIfExists('docente_carrera_materia');
    }

    public function down(): void
    {
        Schema::create('docente_carrera_materia', function (Blueprint $table) {
            $table->id('id_docente_carrera_materia');
            $table->unsignedBigInteger('id_docente');
            $table->foreign('id_docente')
                ->references('id_docente')->on('docente')->cascadeOnDelete();
            $table->foreignId('id_carrera')
                ->constrained('carrera', 'id_carrera')->cascadeOnDelete();
            $table->foreignId('id_materia')
                ->constrained('materia', 'id_materia')->cascadeOnDelete();
            $table->unique(['id_docente', 'id_carrera', 'id_materia']);
        });

        Schema::dropIfExists('docente_carrera_area');
        // docente_materia se conserva (lo usa el flujo actual).
    }
};
