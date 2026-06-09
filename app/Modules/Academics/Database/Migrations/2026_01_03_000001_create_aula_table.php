<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aula', function (Blueprint $table) {
            $table->id('id_aula');
            $table->string('nombre', 50)->unique();
            $table->integer('capacidad');
            $table->string('ubicacion', 100)->nullable();
        });

        // CHECK (capacidad > 0) — paridad con el esquema físico CUPBD.sql.
        DB::statement('ALTER TABLE aula ADD CONSTRAINT aula_capacidad_check CHECK (capacidad > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('aula');
    }
};
