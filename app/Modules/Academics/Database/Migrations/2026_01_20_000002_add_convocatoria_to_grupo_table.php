<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CU09/CU19 — Los grupos pasan a pertenecer a una convocatoria. Así cada
     * proceso de admisión tiene sus propios grupos: al concluir una convocatoria
     * y abrir otra, la nueva arranca limpia y la concluida conserva su historial
     * para los reportes.
     */
    public function up(): void
    {
        Schema::table('grupo', function (Blueprint $table) {
            $table->foreignId('id_convocatoria')->nullable()->after('id_grupo')
                ->constrained('convocatoria', 'id_convocatoria')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('grupo', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_convocatoria');
        });
    }
};
