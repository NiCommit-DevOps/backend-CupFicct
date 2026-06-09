<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * CU06 (rediseño) — Elimina las tablas de la primera versión (materias+pesos y
 * calificación por docente), reemplazadas por el examen en línea autocorregido.
 * Las tablas estaban vacías; se eliminan en orden seguro de FKs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('subcalificacion');
        Schema::dropIfExists('materia_grupo');
        Schema::dropIfExists('evaluacion');
        Schema::dropIfExists('regla_evaluacion');
    }

    public function down(): void
    {
        // No se recrean: el esquema anterior quedó obsoleto.
    }
};
