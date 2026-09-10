<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriasProdutosSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            // Componentes de kit solar
            [
                'nome'               => 'Painel Solar',
                'slug'               => 'painel-solar',
                'descricao'          => 'Módulos fotovoltaicos',
                'icone'              => 'SolarPowerRounded',
                'eh_componente_kit'  => true,
                'exige_potencia'     => true,
                'ordem'              => 1,
            ],
            [
                'nome'               => 'Inversor Solar',
                'slug'               => 'inversor-solar',
                'descricao'          => 'Inversores on-grid, off-grid e híbridos',
                'icone'              => 'SettingsInputComponentRounded',
                'eh_componente_kit'  => true,
                'exige_potencia'     => true,
                'ordem'              => 2,
            ],
            [
                'nome'               => 'Transformador',
                'slug'               => 'transformador',
                'descricao'          => 'Trafos para adequação de tensão',
                'icone'              => 'ElectricalServicesRounded',
                'eh_componente_kit'  => true,
                'exige_potencia'     => true,
                'ordem'              => 3,
            ],
            // Produtos avulsos adicionáveis a propostas
            [
                'nome'               => 'Bateria / Armazenamento',
                'slug'               => 'bateria',
                'descricao'          => 'Baterias de lítio, chumbo-ácido e outros sistemas de armazenamento',
                'icone'              => 'BatteryChargingFullRounded',
                'eh_componente_kit'  => false,
                'exige_potencia'     => false,
                'ordem'              => 4,
            ],
            [
                'nome'               => 'Controlador de Carga',
                'slug'               => 'controlador-carga',
                'descricao'          => 'Controladores MPPT e PWM para sistemas off-grid',
                'icone'              => 'TuneRounded',
                'eh_componente_kit'  => true,
                'exige_potencia'     => false,
                'ordem'              => 5,
            ],
            [
                'nome'               => 'Bomba Solar',
                'slug'               => 'bomba-solar',
                'descricao'          => 'Bombas de água acionadas por energia solar',
                'icone'              => 'WaterRounded',
                'eh_componente_kit'  => false,
                'exige_potencia'     => true,
                'ordem'              => 6,
            ],
            [
                'nome'               => 'Iluminação Solar',
                'slug'               => 'iluminacao-solar',
                'descricao'          => 'Luminárias e postes solares',
                'icone'              => 'LightbulbRounded',
                'eh_componente_kit'  => false,
                'exige_potencia'     => false,
                'ordem'              => 7,
            ],
            [
                'nome'               => 'Cabo e Conector',
                'slug'               => 'cabo-conector',
                'descricao'          => 'Cabos solares, MC4 e demais conectores',
                'icone'              => 'CableRounded',
                'eh_componente_kit'  => false,
                'exige_potencia'     => false,
                'ordem'              => 8,
            ],
            [
                'nome'               => 'Proteção Elétrica',
                'slug'               => 'protecao-eletrica',
                'descricao'          => 'DPS, disjuntores, string boxes',
                'icone'              => 'ShieldRounded',
                'eh_componente_kit'  => false,
                'exige_potencia'     => false,
                'ordem'              => 9,
            ],
            [
                'nome'               => 'Outros',
                'slug'               => 'outros',
                'descricao'          => 'Materiais e produtos diversos',
                'icone'              => 'InventoryRounded',
                'eh_componente_kit'  => false,
                'exige_potencia'     => false,
                'ordem'              => 99,
            ],
        ];

        foreach ($categorias as $cat) {
            DB::table('categorias_produtos')->insertOrIgnore(array_merge($cat, [
                'ativo'      => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
