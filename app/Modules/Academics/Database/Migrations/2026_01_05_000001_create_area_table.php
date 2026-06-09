<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CU10 — Áreas de conocimiento de la facultad (bloques temáticos).
        Schema::create('area', function (Blueprint $table) {
            $table->id('id_area');
            $table->string('nombre', 100)->unique();
            $table->text('descripcion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area');
    }
};
