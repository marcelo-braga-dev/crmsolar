<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConcessionariasSeeder extends Seeder
{
    /**
     * Tarifas em R$/kWh conforme ANEEL (referência: 2024/2025).
     * tarifa_convencional: tarifa única (consumidor residencial B1)
     * tarifa_ponta: tarifa horário de ponta (horo-sazonal, grupos A/B)
     * tarifa_fora_ponta: tarifa fora de ponta
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('concessionarias')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $concessionarias = [
            // SP
            ['nome' => 'ENEL São Paulo (Eletropaulo)', 'estado' => 'SP', 'tarifa_convencional' => 0.82350, 'tarifa_ponta' => 1.45600, 'tarifa_intermediaria' => 1.05000, 'tarifa_fora_ponta' => 0.62100],
            ['nome' => 'CPFL Paulista', 'estado' => 'SP', 'tarifa_convencional' => 0.79200, 'tarifa_ponta' => 1.38900, 'tarifa_intermediaria' => 1.01200, 'tarifa_fora_ponta' => 0.59800],
            ['nome' => 'CPFL Piratininga', 'estado' => 'SP', 'tarifa_convencional' => 0.80100, 'tarifa_ponta' => 1.40200, 'tarifa_intermediaria' => 1.02100, 'tarifa_fora_ponta' => 0.60500],
            ['nome' => 'Elektro (Neoenergia SP)', 'estado' => 'SP', 'tarifa_convencional' => 0.78500, 'tarifa_ponta' => 1.37500, 'tarifa_intermediaria' => 1.00500, 'tarifa_fora_ponta' => 0.59100],
            ['nome' => 'EDP São Paulo', 'estado' => 'SP', 'tarifa_convencional' => 0.81200, 'tarifa_ponta' => 1.42500, 'tarifa_intermediaria' => 1.03500, 'tarifa_fora_ponta' => 0.61200],
            // RJ
            ['nome' => 'Enel Rio', 'estado' => 'RJ', 'tarifa_convencional' => 0.88500, 'tarifa_ponta' => 1.58200, 'tarifa_intermediaria' => 1.15000, 'tarifa_fora_ponta' => 0.67200],
            ['nome' => 'Light', 'estado' => 'RJ', 'tarifa_convencional' => 0.90200, 'tarifa_ponta' => 1.62100, 'tarifa_intermediaria' => 1.17500, 'tarifa_fora_ponta' => 0.68500],
            // MG
            ['nome' => 'CEMIG', 'estado' => 'MG', 'tarifa_convencional' => 0.76800, 'tarifa_ponta' => 1.35200, 'tarifa_intermediaria' => 0.98500, 'tarifa_fora_ponta' => 0.57900],
            // ES
            ['nome' => 'EDP Espírito Santo', 'estado' => 'ES', 'tarifa_convencional' => 0.79500, 'tarifa_ponta' => 1.39200, 'tarifa_intermediaria' => 1.01500, 'tarifa_fora_ponta' => 0.60100],
            // BA
            ['nome' => 'Neoenergia Coelba', 'estado' => 'BA', 'tarifa_convencional' => 0.71200, 'tarifa_ponta' => 1.25800, 'tarifa_intermediaria' => 0.92000, 'tarifa_fora_ponta' => 0.54100],
            // PE
            ['nome' => 'Neoenergia Pernambuco (CELPE)', 'estado' => 'PE', 'tarifa_convencional' => 0.73500, 'tarifa_ponta' => 1.29800, 'tarifa_intermediaria' => 0.94800, 'tarifa_fora_ponta' => 0.55900],
            // CE
            ['nome' => 'Enel Ceará (COELCE)', 'estado' => 'CE', 'tarifa_convencional' => 0.68200, 'tarifa_ponta' => 1.20500, 'tarifa_intermediaria' => 0.88000, 'tarifa_fora_ponta' => 0.51800],
            // RN
            ['nome' => 'Neoenergia Cosern', 'estado' => 'RN', 'tarifa_convencional' => 0.72100, 'tarifa_ponta' => 1.27500, 'tarifa_intermediaria' => 0.92900, 'tarifa_fora_ponta' => 0.54800],
            // PB
            ['nome' => 'Energisa Paraíba', 'estado' => 'PB', 'tarifa_convencional' => 0.74800, 'tarifa_ponta' => 1.32100, 'tarifa_intermediaria' => 0.96500, 'tarifa_fora_ponta' => 0.56900],
            // AL
            ['nome' => 'Equatorial Alagoas', 'estado' => 'AL', 'tarifa_convencional' => 0.75200, 'tarifa_ponta' => 1.32900, 'tarifa_intermediaria' => 0.97000, 'tarifa_fora_ponta' => 0.57200],
            // SE
            ['nome' => 'Energisa Sergipe', 'estado' => 'SE', 'tarifa_convencional' => 0.73900, 'tarifa_ponta' => 1.30500, 'tarifa_intermediaria' => 0.95200, 'tarifa_fora_ponta' => 0.56200],
            // PI
            ['nome' => 'Equatorial Piauí', 'estado' => 'PI', 'tarifa_convencional' => 0.69500, 'tarifa_ponta' => 1.22800, 'tarifa_intermediaria' => 0.89500, 'tarifa_fora_ponta' => 0.52800],
            // MA
            ['nome' => 'Equatorial Maranhão', 'estado' => 'MA', 'tarifa_convencional' => 0.70100, 'tarifa_ponta' => 1.23900, 'tarifa_intermediaria' => 0.90200, 'tarifa_fora_ponta' => 0.53200],
            // PA
            ['nome' => 'Equatorial Pará', 'estado' => 'PA', 'tarifa_convencional' => 0.68900, 'tarifa_ponta' => 1.21800, 'tarifa_intermediaria' => 0.88900, 'tarifa_fora_ponta' => 0.52400],
            // AM
            ['nome' => 'Amazonas Energia', 'estado' => 'AM', 'tarifa_convencional' => 0.72800, 'tarifa_ponta' => 1.28900, 'tarifa_intermediaria' => 0.93900, 'tarifa_fora_ponta' => 0.55400],
            // PR
            ['nome' => 'Copel', 'estado' => 'PR', 'tarifa_convencional' => 0.68500, 'tarifa_ponta' => 1.21200, 'tarifa_intermediaria' => 0.88200, 'tarifa_fora_ponta' => 0.52100],
            // SC
            ['nome' => 'Celesc', 'estado' => 'SC', 'tarifa_convencional' => 0.66800, 'tarifa_ponta' => 1.18200, 'tarifa_intermediaria' => 0.86100, 'tarifa_fora_ponta' => 0.50900],
            // RS
            ['nome' => 'RGE Sul (CPFL)', 'estado' => 'RS', 'tarifa_convencional' => 0.70500, 'tarifa_ponta' => 1.24600, 'tarifa_intermediaria' => 0.90800, 'tarifa_fora_ponta' => 0.53600],
            ['nome' => 'CEEE-D (Equatorial Sul)', 'estado' => 'RS', 'tarifa_convencional' => 0.69200, 'tarifa_ponta' => 1.22300, 'tarifa_intermediaria' => 0.89200, 'tarifa_fora_ponta' => 0.52600],
            // GO
            ['nome' => 'Enel Goiás', 'estado' => 'GO', 'tarifa_convencional' => 0.74200, 'tarifa_ponta' => 1.31200, 'tarifa_intermediaria' => 0.95600, 'tarifa_fora_ponta' => 0.56500],
            // MT
            ['nome' => 'Energisa Mato Grosso', 'estado' => 'MT', 'tarifa_convencional' => 0.76500, 'tarifa_ponta' => 1.35200, 'tarifa_intermediaria' => 0.98200, 'tarifa_fora_ponta' => 0.58200],
            // MS
            ['nome' => 'Energisa Mato Grosso do Sul', 'estado' => 'MS', 'tarifa_convencional' => 0.75800, 'tarifa_ponta' => 1.34000, 'tarifa_intermediaria' => 0.97400, 'tarifa_fora_ponta' => 0.57600],
            // DF
            ['nome' => 'CEB (Neoenergia Brasília)', 'estado' => 'DF', 'tarifa_convencional' => 0.77200, 'tarifa_ponta' => 1.36500, 'tarifa_intermediaria' => 0.99200, 'tarifa_fora_ponta' => 0.58800],
            // TO
            ['nome' => 'Energisa Tocantins', 'estado' => 'TO', 'tarifa_convencional' => 0.72500, 'tarifa_ponta' => 1.28200, 'tarifa_intermediaria' => 0.93200, 'tarifa_fora_ponta' => 0.55100],
            // AC
            ['nome' => 'Energisa Acre', 'estado' => 'AC', 'tarifa_convencional' => 0.71800, 'tarifa_ponta' => 1.26900, 'tarifa_intermediaria' => 0.92400, 'tarifa_fora_ponta' => 0.54600],
            // RO
            ['nome' => 'Energisa Rondônia', 'estado' => 'RO', 'tarifa_convencional' => 0.73200, 'tarifa_ponta' => 1.29300, 'tarifa_intermediaria' => 0.94200, 'tarifa_fora_ponta' => 0.55700],
            // RR
            ['nome' => 'Roraima Energia', 'estado' => 'RR', 'tarifa_convencional' => 0.70800, 'tarifa_ponta' => 1.25200, 'tarifa_intermediaria' => 0.91200, 'tarifa_fora_ponta' => 0.53900],
            // AP
            ['nome' => 'CEA (Equatorial Amapá)', 'estado' => 'AP', 'tarifa_convencional' => 0.69800, 'tarifa_ponta' => 1.23300, 'tarifa_intermediaria' => 0.89800, 'tarifa_fora_ponta' => 0.53100],
        ];

        foreach ($concessionarias as &$c) {
            $c['ativo'] = true;
            $c['created_at'] = now();
            $c['updated_at'] = now();
        }

        DB::table('concessionarias')->insert($concessionarias);
        $this->command->info('Concessionárias inseridas: ' . count($concessionarias));
    }
}
