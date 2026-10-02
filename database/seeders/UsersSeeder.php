<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@teste.com'],
            [
                'name' => 'Admin Sistema',
                'password' => Hash::make('1020'),
                'tipo' => 'admin',
                'status' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'consultor@teste.com'],
            [
                'name' => 'Consultor Teste',
                'password' => Hash::make('1020'),
                'tipo' => 'consultor',
                'status' => true,
                'comissao_percentual' => 5.00,
                'celular' => '(11) 99999-0001',
            ]
        );
    }
}
