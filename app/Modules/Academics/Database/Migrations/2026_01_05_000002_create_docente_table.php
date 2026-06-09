<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CU10 — Especialización 1:1 de Usuario como Docente.
        // La PK es a la vez FK a usuario(id_usuario): un docente no existe sin su usuario base.
        Schema::create('docente', function (Blueprint $table) {
            $table->unsignedBigInteger('id_docente')->primary();
            $table->foreign('id_docente')
                ->references('id_usuario')->on('usuario')
                ->cascadeOnDelete();
            $table->string('profesion', 100)->nullable();
            $table->integer('carga_horaria')->nullable();
            $table->string('especialidad', 100)->nullable();
            $table->boolean('tiene_maestria')->default(false);
            $table->boolean('tiene_diplomado')->default(false);
        });

        // CHECK (carga_horaria >= 0): la carga horaria no puede ser negativa.
        DB::statement('ALTER TABLE docente ADD CONSTRAINT docente_carga_horaria_check CHECK (carga_horaria IS NULL OR carga_horaria >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('docente');
    }
};
