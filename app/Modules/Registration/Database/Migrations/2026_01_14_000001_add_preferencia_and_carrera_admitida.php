<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CU07 — Corte de admisión:
 *  - carrera_inscripcion.orden: 1 = primera opción, 2 = segunda opción.
 *  - inscripcion.id_carrera_admitida: carrera en la que finalmente fue admitido.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carrera_inscripcion', function (Blueprint $table) {
            $table->unsignedSmallInteger('orden')->default(1)->after('id_inscripcion');
        });

        Schema::table('inscripcion', function (Blueprint $table) {
            $table->foreignId('id_carrera_admitida')->nullable()->after('promedio_final')
                ->constrained('carrera', 'id_carrera')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inscripcion', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_carrera_admitida');
        });

        Schema::table('carrera_inscripcion', function (Blueprint $table) {
            $table->dropColumn('orden');
        });
    }
};
