<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CU09 — Gestionar Asignación de Grupos.
 *
 * Grupo de examen (sigla, nombre, turno, capacidad_max). Se añade `id_aula`
 * directo al grupo (extensión sobre el esquema base, donde el aula llegaba vía
 * Materia_Grupo) para poder validar "capacidad de aula < capacidad de grupo".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupo', function (Blueprint $table) {
            $table->id('id_grupo');
            $table->string('sigla', 20)->unique();
            $table->string('nombre', 50);
            $table->string('turno', 20)->nullable();
            $table->integer('capacidad_max')->default(70);
            $table->foreignId('id_aula')->nullable()
                ->constrained('aula', 'id_aula')->nullOnDelete();
        });

        DB::statement("ALTER TABLE grupo ADD CONSTRAINT grupo_turno_check CHECK (turno IS NULL OR turno IN ('Mañana', 'Tarde', 'Noche'))");
        DB::statement('ALTER TABLE grupo ADD CONSTRAINT grupo_capacidad_check CHECK (capacidad_max > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('grupo');
    }
};
