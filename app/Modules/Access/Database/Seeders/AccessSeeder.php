<?php

namespace App\Modules\Access\Database\Seeders;

use App\Modules\Access\Models\Permiso;
use App\Modules\Access\Models\Rol;
use App\Modules\Access\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AccessSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Catálogo cerrado de permisos (campo "modulo" = código de la acción).
        $permisos = [];

        // Usuarios: no se eliminan, se inhabilitan (no existe 'usuarios.destroy').
        foreach (['index', 'store', 'update'] as $accion) {
            $permisos[] = ['modulo' => "usuarios.$accion", 'descripcion' => "Usuarios: $accion"];
        }

        // Roles: CRUD completo.
        foreach (['index', 'store', 'update', 'destroy'] as $accion) {
            $permisos[] = ['modulo' => "roles.$accion", 'descripcion' => "Roles: $accion"];
        }

        // Permisos adicionales de sistema (catálogo / sincronización)
        $permisos[] = ['modulo' => 'roles.permisos', 'descripcion' => 'Sincronizar permisos de un rol'];
        $permisos[] = ['modulo' => 'permisos.index', 'descripcion' => 'Ver matriz de permisos'];

        // CU05 — Pagos: ver el historial global de transacciones (conciliación).
        $permisos[] = ['modulo' => 'pagos.index', 'descripcion' => 'Ver historial global de pagos'];

        // CU15 — Reportes de pagos: conciliación de caja y recaudación (fiscalización).
        $permisos[] = ['modulo' => 'pagos.reportes', 'descripcion' => 'Ver reportes de pagos'];

        // CU11 — Bitácora: visor de auditoría (exclusivo del Administrador global).
        $permisos[] = ['modulo' => 'bitacora.index', 'descripcion' => 'Ver bitácora de auditoría'];

        foreach ($permisos as $permiso) {
            Permiso::firstOrCreate(['modulo' => $permiso['modulo']], $permiso);
        }

        // 2. Roles base del sistema de admisión.
        $roles = ['Administrador', 'Coordinador Académico', 'Docente', 'Postulante'];

        foreach ($roles as $nombre) {
            Rol::firstOrCreate(['nombre' => $nombre]);
        }

        // 3. El Administrador recibe el catálogo completo de permisos.
        $admin = Rol::where('nombre', 'Administrador')->first();
        $admin->permisos()->sync(Permiso::pluck('id_permiso')->all());

        // 4. Usuario administrador inicial. Credenciales personalizables por .env
        // (ADMIN_*) para cada despliegue; si no, usa los valores por defecto, de
        // modo que al clonar el proyecto SIEMPRE exista un admin para entrar.
        $correoAdmin = env('ADMIN_EMAIL', 'admin@ficct.uagrm.edu.bo');

        Usuario::firstOrCreate(
            ['correo' => $correoAdmin],
            [
                'id_rol' => $admin->id_rol,
                'ci' => env('ADMIN_CI', '0000000'),
                'nombres' => env('ADMIN_NOMBRES', 'Administrador'),
                'apellidos' => env('ADMIN_APELLIDOS', 'del Sistema'),
                'fecha_nacimiento' => '1990-01-01',
                'sexo' => 'Otro',
                'contrasena' => Hash::make(env('ADMIN_PASSWORD', 'Admin12345')),
                'EstaActivo' => true,
            ]
        );

        // 5. Usuario Coordinador Académico de prueba (acceso de solo lectura a la sección académica).
        $coordinador = Rol::where('nombre', 'Coordinador Académico')->first();

        Usuario::firstOrCreate(
            ['correo' => 'coordinador@ficct.uagrm.edu.bo'],
            [
                'id_rol' => $coordinador?->id_rol,
                'ci' => '0000001',
                'nombres' => 'Coordinador',
                'apellidos' => 'Académico',
                'fecha_nacimiento' => '1985-01-01',
                'sexo' => 'Otro',
                'contrasena' => Hash::make('Coord12345'),
                'EstaActivo' => true,
            ]
        );
    }
}
