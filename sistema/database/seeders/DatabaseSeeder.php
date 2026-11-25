<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = Role::firstOrCreate(
            ['name' => 'Administrador'],
            ['description' => 'Acceso total al sistema']
        );

        Role::firstOrCreate(
            ['name' => 'Vendedor'],
            ['description' => 'Registra ventas y atiende clientes']
        );

        // Usuario inicial (cambiar DNI y contraseña desde Usuarios).
        User::firstOrCreate(
            ['email' => 'admin@sanjuan.com'],
            [
                'role_id' => $admin->id,
                'name' => 'Administrador',
                'dni' => '00000000',
                'password' => 'admin12345',
            ]
        );
    }
}
