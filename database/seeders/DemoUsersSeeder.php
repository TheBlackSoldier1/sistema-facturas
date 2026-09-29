<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Las cuentas de demostración solo se crean en local o testing.');
        }
        $accounts = [
            ['Jefe de Informática', 'jefe@example.test', 'jefe', 'Informática', 'JefeDemo!2026'],
            ['Usuario de Informática', 'informatica@example.test', 'usuario', 'Informática', 'InformaticaDemo!2026'],
            ['Usuario de Finanzas', 'finanzas@example.test', 'usuario', 'Finanzas', 'FinanzasDemo!2026'],
            ['Usuario de Vivienda', 'vivienda@example.test', 'usuario', 'Vivienda', 'ViviendaDemo!2026'],
        ];
        foreach ($accounts as [$name, $email, $role, $area, $password]) {
            $area = Area::firstOrCreate(['nombre' => $area]);
            // No restablecer contraseñas ni permisos si la cuenta ya existe.
            if (! User::where('email', $email)->exists()) {
                $user = new User;
                $user->name = $name;
                $user->email = $email;
                $user->role = $role;
                $user->area_id = $area->id;
                $user->password = $password;
                $user->save();
            }
        }
    }
}
