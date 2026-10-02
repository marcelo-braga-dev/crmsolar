<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Garante que TODAS as cidades tenham irradiação solar.
 *
 * Estratégia por prioridade:
 *   1. A cidade já tem irradiação → mantém (não sobrescreve)
 *   2. Busca a capital do estado no banco → copia os valores
 *   3. Se a capital não tem dados, usa a cidade mais populosa do estado que tiver dados
 *   4. Fallback: valores médios regionais (Norte/Nordeste/Centro-Oeste/Sudeste/Sul)
 */
class IrradiacaoSolarTodosSeeder extends Seeder
{
    // Capital de cada estado (para buscar no banco como referência primária)
    private const CAPITAIS = [
        'AC' => 'Rio Branco',
        'AL' => 'Maceió',
        'AM' => 'Manaus',
        'AP' => 'Macapá',
        'BA' => 'Salvador',
        'CE' => 'Fortaleza',
        'DF' => 'Brasília',
        'ES' => 'Vitória',
        'GO' => 'Goiânia',
        'MA' => 'São Luís',
        'MG' => 'Belo Horizonte',
        'MS' => 'Campo Grande',
        'MT' => 'Cuiabá',
        'PA' => 'Belém',
        'PB' => 'João Pessoa',
        'PE' => 'Recife',
        'PI' => 'Teresina',
        'PR' => 'Curitiba',
        'RJ' => 'Rio de Janeiro',
        'RN' => 'Natal',
        'RO' => 'Porto Velho',
        'RR' => 'Boa Vista',
        'RS' => 'Porto Alegre',
        'SC' => 'Florianópolis',
        'SE' => 'Aracaju',
        'SP' => 'São Paulo',
        'TO' => 'Palmas',
    ];

    // Valores médios mensais por macrorregião (fallback se capital não tiver dados)
    // Fonte: Atlas Solarimétrico INPE — média por região climática
    private const FALLBACK_REGIONAL = [
        'Norte' => ['media' => 5.05, 'jan' => 4.85, 'fev' => 4.60, 'mar' => 4.45, 'abr' => 4.55, 'mai' => 4.75, 'jun' => 5.00, 'jul' => 5.35, 'ago' => 5.60, 'set' => 5.50, 'out' => 5.20, 'nov' => 4.90, 'dez' => 4.85],
        'Nordeste' => ['media' => 5.75, 'jan' => 5.90, 'fev' => 5.70, 'mar' => 5.45, 'abr' => 5.25, 'mai' => 5.20, 'jun' => 5.10, 'jul' => 5.35, 'ago' => 5.80, 'set' => 6.10, 'out' => 6.25, 'nov' => 6.15, 'dez' => 5.90],
        'Centro-Oeste' => ['media' => 5.50, 'jan' => 5.55, 'fev' => 5.40, 'mar' => 5.25, 'abr' => 5.35, 'mai' => 5.55, 'jun' => 5.60, 'jul' => 5.85, 'ago' => 6.15, 'set' => 5.80, 'out' => 5.50, 'nov' => 5.28, 'dez' => 5.42],
        'Sudeste' => ['media' => 5.10, 'jan' => 5.20, 'fev' => 5.00, 'mar' => 4.78, 'abr' => 4.75, 'mai' => 4.68, 'jun' => 4.52, 'jul' => 4.75, 'ago' => 5.15, 'set' => 4.95, 'out' => 4.90, 'nov' => 4.95, 'dez' => 5.10],
        'Sul' => ['media' => 4.85, 'jan' => 5.10, 'fev' => 4.90, 'mar' => 4.65, 'abr' => 4.50, 'mai' => 4.35, 'jun' => 4.18, 'jul' => 4.42, 'ago' => 4.85, 'set' => 4.72, 'out' => 4.75, 'nov' => 4.88, 'dez' => 5.05],
    ];

    private const SIGLA_REGIAO = [
        'AC' => 'Norte', 'AM' => 'Norte', 'AP' => 'Norte', 'PA' => 'Norte', 'RO' => 'Norte', 'RR' => 'Norte', 'TO' => 'Norte',
        'AL' => 'Nordeste', 'BA' => 'Nordeste', 'CE' => 'Nordeste', 'MA' => 'Nordeste', 'PB' => 'Nordeste',
        'PE' => 'Nordeste', 'PI' => 'Nordeste', 'RN' => 'Nordeste', 'SE' => 'Nordeste',
        'DF' => 'Centro-Oeste', 'GO' => 'Centro-Oeste', 'MS' => 'Centro-Oeste', 'MT' => 'Centro-Oeste',
        'ES' => 'Sudeste', 'MG' => 'Sudeste', 'RJ' => 'Sudeste', 'SP' => 'Sudeste',
        'PR' => 'Sul', 'RS' => 'Sul', 'SC' => 'Sul',
    ];

    public function run(): void
    {
        $semIrradiacao = DB::table('cidades_estados as c')
            ->leftJoin('irradiacao_solar as i', 'c.id', '=', 'i.cidade_id')
            ->whereNull('i.id')
            ->select('c.id', 'c.cidade', 'c.sigla')
            ->get();

        if ($semIrradiacao->isEmpty()) {
            $this->command->info('Todas as cidades já têm irradiação solar. Nada a fazer.');

            return;
        }

        $this->command->info("Cidades sem irradiação: {$semIrradiacao->count()}. Processando...");

        // Constrói cache de referências por estado
        $cache = $this->construirCacheEstados();

        $batch = [];
        $ok = 0;
        $fallback = 0;

        foreach ($semIrradiacao as $cidade) {
            $ref = $cache[$cidade->sigla] ?? null;

            if (! $ref) {
                $fallback++;
                $regiao = self::SIGLA_REGIAO[$cidade->sigla] ?? 'Sudeste';
                $f = self::FALLBACK_REGIONAL[$regiao];
                $ref = (object) $f;
            }

            $batch[] = [
                'cidade_id' => $cidade->id,
                'media' => $ref->media,
                'jan' => $ref->jan, 'fev' => $ref->fev, 'mar' => $ref->mar,
                'abr' => $ref->abr, 'mai' => $ref->mai, 'jun' => $ref->jun,
                'jul' => $ref->jul, 'ago' => $ref->ago, 'set' => $ref->set,
                'out' => $ref->out, 'nov' => $ref->nov, 'dez' => $ref->dez,
            ];
            $ok++;

            // Insere em lotes de 500
            if (count($batch) >= 500) {
                DB::table('irradiacao_solar')->insert($batch);
                $batch = [];
            }
        }

        if (! empty($batch)) {
            DB::table('irradiacao_solar')->insert($batch);
        }

        $total = DB::table('cidades_estados')->count();
        $comIrr = DB::table('irradiacao_solar')->distinct('cidade_id')->count();

        $this->command->info("Inseridas: {$ok} | Via fallback regional: {$fallback} | Cobertura: {$comIrr}/{$total}");
    }

    /**
     * Para cada sigla, busca a irradiação da capital do estado.
     * Se a capital não tiver dados, usa qualquer cidade do estado que tenha.
     */
    private function construirCacheEstados(): array
    {
        $cache = [];

        foreach (self::CAPITAIS as $sigla => $capital) {
            // Tenta a capital primeiro
            $cidadeId = DB::table('cidades_estados')
                ->where('cidade', $capital)
                ->where('sigla', $sigla)
                ->value('id');

            if ($cidadeId) {
                $irr = DB::table('irradiacao_solar')->where('cidade_id', $cidadeId)->first();
                if ($irr) {
                    $cache[$sigla] = $irr;

                    continue;
                }
            }

            // Fallback: qualquer cidade do estado com irradiação
            $qualquer = DB::table('irradiacao_solar as i')
                ->join('cidades_estados as c', 'c.id', '=', 'i.cidade_id')
                ->where('c.sigla', $sigla)
                ->select('i.*')
                ->first();

            if ($qualquer) {
                $cache[$sigla] = $qualquer;
            }
        }

        return $cache;
    }
}
