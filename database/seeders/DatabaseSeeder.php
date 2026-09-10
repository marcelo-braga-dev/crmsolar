<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            UsersSeeder::class,
            EstruturasSeeder::class,
            CategoriasProdutosSeeder::class,
            ParamsDimensionamentoSeeder::class,
            CidadesEstadosSeeder::class,
            IrradiacaoSolarSeeder::class,
            IrradiacaoSolarComplementoSeeder::class,
            IrradiacaoSolarTodosSeeder::class,
            ConcessionariasSeeder::class,
            DadosTesteSeeder::class,
        ]);
    }
}
