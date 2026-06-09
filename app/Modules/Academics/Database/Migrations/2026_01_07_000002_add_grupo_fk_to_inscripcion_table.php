<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CU09 — Ahora que existe la tabla `grupo`, se agrega la FK de
 * `inscripcion.id_grupo` (la columna ya existía nullable desde CU04).
 * ON DELETE SET NULL: al borrar un grupo, las inscripciones quedan sin grupo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscripcion', function (Blueprint $table) {
            $table->foreign('id_grupo')
                ->references('id_grupo')->on('grupo')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inscripcion', function (Blueprint $table) {
            $table->dropForeign(['id_grupo']);
        });
    }
};
