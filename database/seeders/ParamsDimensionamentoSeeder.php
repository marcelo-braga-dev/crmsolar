<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ParamsDimensionamentoSeeder extends Seeder
{
    public function run(): void
    {
        $params = [
            [
                'chave' => 'fator_perda_sistema',
                'nome' => 'Fator de Perda do Sistema (PR)',
                'valor' => '20',
                'unidade' => '%',
                'descricao' => 'Perdas combinadas: cabeamento CC/CA (~2%), temperatura (~7% no BR), sujeira (~3%), mismatch entre painéis (~2%), eficiência do inversor (~3%), degradação (~3%). Total típico BR: 18–22%. Define PR = 1 - fator/100.',
            ],
            [
                'chave' => 'margem_seguranca',
                'nome' => 'Margem de Segurança',
                'valor' => '5',
                'unidade' => '%',
                'descricao' => 'Percentual adicionado sobre a potência calculada para garantir atendimento ao consumo mesmo em condições adversas (sombreamento eventual, degradação futura). Recomendado: 5–10%.',
            ],
            [
                'chave' => 'perda_norte',
                'nome' => 'Perda — Orientação Norte',
                'valor' => '0',
                'unidade' => '%',
                'descricao' => 'Perda para instalação orientada ao Norte (ótimo)',
            ],
            [
                'chave' => 'perda_nordeste_noroeste',
                'nome' => 'Perda — Nordeste / Noroeste',
                'valor' => '5',
                'unidade' => '%',
                'descricao' => 'Perda para instalação orientada ao Nordeste ou Noroeste',
            ],
            [
                'chave' => 'perda_leste_oeste',
                'nome' => 'Perda — Leste / Oeste',
                'valor' => '12',
                'unidade' => '%',
                'descricao' => 'Perda para instalação orientada ao Leste ou Oeste',
            ],
            [
                'chave' => 'perda_sudeste_sudoeste',
                'nome' => 'Perda — Sudeste / Sudoeste',
                'valor' => '18',
                'unidade' => '%',
                'descricao' => 'Perda para instalação orientada ao Sudeste ou Sudoeste',
            ],
            [
                'chave' => 'perda_sul',
                'nome' => 'Perda — Sul',
                'valor' => '25',
                'unidade' => '%',
                'descricao' => 'Perda para instalação orientada ao Sul',
            ],
        ];

        foreach ($params as $param) {
            DB::table('params_dimensionamento')->updateOrInsert(
                ['chave' => $param['chave']],
                array_merge($param, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
