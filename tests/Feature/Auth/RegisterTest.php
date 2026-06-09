<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_creates_user_with_administrador_role(): void
    {
        $payload = [
            'ci' => '1234567',
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
            'correo' => 'ana@ejemplo.com',
            'telefono1' => '59178889900',
            'fecha_nacimiento' => '1998-04-10',
            'sexo' => 'F',
            'contrasena' => 'Secret123',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertCreated();
        $response->assertJsonPath('data.correo', 'ana@ejemplo.com');
        $response->assertJsonPath('data.rol.nombre', 'Administrador');

        $administrador = \App\Modules\Access\Models\Rol::where('nombre', 'Administrador')->first();
        $this->assertNotNull($administrador);
        $this->assertTrue($administrador->permisos()->where('modulo', 'usuarios.index')->exists());

        $this->assertDatabaseHas('usuario', [
            'correo' => 'ana@ejemplo.com',
            'ci' => '1234567',
        ]);
    }
}
