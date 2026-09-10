<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstruturasSeeder extends Seeder
{
    public function run(): void
    {
        $estruturas = [
            ['nome' => 'Telha Colonial (Cerâmico)', 'descricao' => 'Fixação em telha colonial cerâmica'],
            ['nome' => 'Parafuso Estrutura Madeira', 'descricao' => 'Fixação em madeira'],
            ['nome' => 'Parafuso Estrutura Metálica', 'descricao' => 'Fixação em estrutura metálica'],
            ['nome' => 'Telha Metálica Perfil 55cm', 'descricao' => 'Fixação em telha trapezoidal'],
            ['nome' => 'Telha Ondulada', 'descricao' => 'Fixação em telha ondulada (Eternit)'],
            ['nome' => 'Laje', 'descricao' => 'Instalação sobre laje'],
            ['nome' => 'Solo', 'descricao' => 'Instalação no solo / ground mount'],
            ['nome' => 'Telha Metálica Zipada', 'descricao' => 'Fixação em telha zipada (standing seam)'],
            ['nome' => 'Sem Estrutura', 'descricao' => 'Produto sem estrutura inclusa'],
        ];

        foreach ($estruturas as $est) {
            DB::table('estruturas')->insertOrIgnore(array_merge($est, ['ativo' => true]));
        }
    }
}
