<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuario', function (Blueprint $table) {
            $table->id('id_usuario');
            $table->foreignId('id_rol')->nullable()
                ->constrained('rol', 'id_rol')->nullOnDelete();
            $table->string('ci', 20)->unique();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('correo', 100)->unique();
            $table->string('telefono1', 20)->nullable();
            $table->string('telefono2', 20)->nullable();
            $table->date('fecha_nacimiento');
            $table->string('sexo', 10)->nullable();
            $table->string('contrasena', 255);
            $table->boolean('EstaActivo')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario');
    }
};
