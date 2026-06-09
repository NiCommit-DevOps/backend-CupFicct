<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CU04 — El título de bachiller pasa de un sí/no a un documento adjunto
     * (PDF o imagen). La presencia del archivo determina el booleano
     * titulo_bachiller; aquí solo se guarda la ruta del archivo subido.
     */
    public function up(): void
    {
        Schema::table('postulante', function (Blueprint $table) {
            $table->string('titulo_archivo', 255)->nullable()->after('titulo_bachiller');
        });
    }

    public function down(): void
    {
        Schema::table('postulante', function (Blueprint $table) {
            $table->dropColumn('titulo_archivo');
        });
    }
};
