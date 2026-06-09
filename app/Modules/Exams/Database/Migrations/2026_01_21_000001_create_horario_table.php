<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CU06/CU10 — Horario de clases por materia y turno.
 *
 * El horario varía según el turno (Mañana/Tarde): cada materia tiene sus días
 * fijos (LUN-MIE-VIE o MAR-JUE-SAB) y un rango de horas que depende del turno.
 * El turno y el aula concretos los aporta el grupo; de ahí el docente y el
 * postulante derivan su horario.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horario', function (Blueprint $table) {
            $table->id('id_horario');
            $table->foreignId('id_materia')
                ->constrained('materia', 'id_materia')->cascadeOnDelete();
            $table->string('turno', 20);
            $table->string('dias', 40);
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->unique(['id_materia', 'turno']);
        });

        DB::statement("ALTER TABLE horario ADD CONSTRAINT horario_turno_check CHECK (turno IN ('Mañana', 'Tarde'))");
        DB::statement('ALTER TABLE horario ADD CONSTRAINT horario_horas_check CHECK (hora_inicio < hora_fin)');
    }

    public function down(): void
    {
        Schema::dropIfExists('horario');
    }
};
