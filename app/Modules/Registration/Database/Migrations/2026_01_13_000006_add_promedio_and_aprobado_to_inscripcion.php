<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CU06/CU07 — Resultado del examen en la inscripción:
 *  - promedio_final: promedio de los 3 exámenes.
 *  - estado_academico: se habilita 'APROBADO' (promedio >= 60) además de los existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscripcion', function (Blueprint $table) {
            $table->decimal('promedio_final', 5, 2)->nullable()->after('estado_academico');
        });

        DB::statement('ALTER TABLE inscripcion DROP CONSTRAINT IF EXISTS inscripcion_estado_check');
        DB::statement("ALTER TABLE inscripcion ADD CONSTRAINT inscripcion_estado_check CHECK (estado_academico IN ('PENDIENTE', 'ELEGIBLE', 'APROBADO', 'ADMITIDO', 'REPROBADO', 'APROBADO_SIN_CUPO'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE inscripcion DROP CONSTRAINT IF EXISTS inscripcion_estado_check');
        DB::statement("ALTER TABLE inscripcion ADD CONSTRAINT inscripcion_estado_check CHECK (estado_academico IN ('PENDIENTE', 'ELEGIBLE', 'ADMITIDO', 'REPROBADO', 'APROBADO_SIN_CUPO'))");

        Schema::table('inscripcion', function (Blueprint $table) {
            $table->dropColumn('promedio_final');
        });
    }
};
