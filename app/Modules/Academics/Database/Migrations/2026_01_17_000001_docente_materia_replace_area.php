<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CU10 — El docente se relaciona con MATERIAS (Computación, Matemáticas, Inglés,
 * Física), no con un catálogo de "áreas". Un docente puede dictar de 1 a muchas
 * materias.
 *
 * Reemplaza el concepto redundante de "Área": crea `docente_materia` y elimina
 * `area_docente` y `area`. Agrega `descripcion` a `materia` para su gestión.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('materia', 'descripcion')) {
            Schema::table('materia', function (Blueprint $table) {
                $table->string('descripcion', 255)->nullable()->after('nombre');
            });
        }

        Schema::create('docente_materia', function (Blueprint $table) {
            $table->id('id_docente_materia');
            $table->unsignedBigInteger('id_docente');
            $table->foreign('id_docente')
                ->references('id_docente')->on('docente')->cascadeOnDelete();
            $table->foreignId('id_materia')
                ->constrained('materia', 'id_materia')->cascadeOnDelete();
            $table->unique(['id_docente', 'id_materia']);
        });

        // Elimina el catálogo de áreas (reemplazado por materias).
        Schema::dropIfExists('area_docente');
        Schema::dropIfExists('area');
    }

    public function down(): void
    {
        Schema::dropIfExists('docente_materia');

        if (Schema::hasColumn('materia', 'descripcion')) {
            Schema::table('materia', function (Blueprint $table) {
                $table->dropColumn('descripcion');
            });
        }

        Schema::create('area', function (Blueprint $table) {
            $table->id('id_area');
            $table->string('nombre', 100)->unique();
            $table->string('descripcion', 255)->nullable();
        });

        Schema::create('area_docente', function (Blueprint $table) {
            $table->id('id_area_docente');
            $table->foreignId('id_area')->constrained('area', 'id_area')->cascadeOnDelete();
            $table->unsignedBigInteger('id_docente');
            $table->foreign('id_docente')->references('id_docente')->on('docente')->cascadeOnDelete();
            $table->unique(['id_area', 'id_docente']);
        });
    }
};
