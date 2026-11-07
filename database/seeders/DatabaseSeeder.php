<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SerieElectronicaSeeder::class);

        // Único usuario de la Fase 1. Correo y contraseña vienen del .env.
        User::updateOrCreate(
            ['email' => env('FINANZAS_ADMIN_EMAIL', 'admin@finanzas.test')],
            ['name' => 'Administrador', 'password' => Hash::make(env('FINANZAS_ADMIN_PASSWORD', 'password'))],
        );
    }
}
