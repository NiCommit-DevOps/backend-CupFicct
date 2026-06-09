<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CU06 — Catálogo fijo de materias evaluadas (Computación, Matemáticas, Inglés, Física).
        Schema::create('materia', function (Blueprint $table) {
            $table->id('id_materia');
            $table->string('nombre', 60)->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materia');
    }
};
