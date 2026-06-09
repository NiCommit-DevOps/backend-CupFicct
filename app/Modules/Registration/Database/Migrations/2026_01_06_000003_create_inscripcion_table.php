<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CU04 — Solicitud de admisión del postulante a una convocatoria.
        Schema::create('inscripcion', function (Blueprint $table) {
            $table->id('id_inscripcion');
            $table->unsignedBigInteger('id_postulante');
            $table->foreign('id_postulante')
                ->references('id_postulante')->on('postulante')
                ->cascadeOnDelete();
            // id_grupo: la FK a 'grupo' se agregará con CU09 (la tabla aún no existe).
            $table->unsignedBigInteger('id_grupo')->nullable();
            $table->foreignId('id_convocatoria')
                ->constrained('convocatoria', 'id_convocatoria')->cascadeOnDelete();
            $table->string('turno_preferencia', 10)->nullable();
            $table->date('fecha_inscripcion')->default(DB::raw('CURRENT_DATE'));
            $table->string('estado_academico', 30)->default('PENDIENTE');
        });

        DB::statement("ALTER TABLE inscripcion ADD CONSTRAINT inscripcion_turno_check CHECK (turno_preferencia IS NULL OR turno_preferencia IN ('MAÑANA', 'TARDE', 'NOCHE'))");
        DB::statement("ALTER TABLE inscripcion ADD CONSTRAINT inscripcion_estado_check CHECK (estado_academico IN ('PENDIENTE', 'ELEGIBLE', 'ADMITIDO', 'REPROBADO', 'APROBADO_SIN_CUPO'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripcion');
    }
};
