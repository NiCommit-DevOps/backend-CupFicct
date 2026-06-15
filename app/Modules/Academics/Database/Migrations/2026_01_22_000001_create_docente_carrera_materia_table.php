<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CU10 — El docente se contrata por (Carrera + Áreas). El "área" del docente
 * son las materias que dicta dentro de una carrera (ej. Ing. Informática:
 * Computación, Física, Matemáticas). Un docente puede tener varias carreras y,
 * dentro de cada una, varias materias.
 *
 * Reemplaza `docente_materia` (lista plana) por `docente_carrera_materia`, que
 * guarda la tripleta docente–carrera–materia.
 */
return new class extends Migration
{
    public function up(): void
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

        Schema::dropIfExists('docente_materia');
    }

    public function down(): void
    {
        Schema::create('docente_materia', function (Blueprint $table) {
            $table->id('id_docente_materia');
            $table->unsignedBigInteger('id_docente');
            $table->foreign('id_docente')
                ->references('id_docente')->on('docente')->cascadeOnDelete();
            $table->foreignId('id_materia')
                ->constrained('materia', 'id_materia')->cascadeOnDelete();
            $table->unique(['id_docente', 'id_materia']);
        });

        Schema::dropIfExists('docente_carrera_materia');
    }
};
