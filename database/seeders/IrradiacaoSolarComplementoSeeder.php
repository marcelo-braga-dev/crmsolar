<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Complementa irradiação para cidades não cobertas pelo seeder principal,
 * usando a irradiação de uma cidade próxima / mesma região climática.
 */
class IrradiacaoSolarComplementoSeeder extends Seeder
{
    public function run(): void
    {
        // Mapeamento: [cidade sem irradiação => cidade de referência (mesma sigla/próxima)]
        $referencias = [
            // AL
            'Palmeira dos Índios' => ['ref' => 'Maceió',            'sigla' => 'AL'],
            // AM
            'Parintins' => ['ref' => 'Manaus',            'sigla' => 'AM'],
            'Itacoatiara' => ['ref' => 'Manaus',            'sigla' => 'AM'],
            // AP
            'Santana' => ['ref' => 'Macapá',            'sigla' => 'AP'],
            // BA
            'Ilhéus' => ['ref' => 'Vitória da Conquista', 'sigla' => 'BA'],
            'Itabuna' => ['ref' => 'Vitória da Conquista', 'sigla' => 'BA'],
            'Lauro de Freitas' => ['ref' => 'Salvador',          'sigla' => 'BA'],
            // CE
            'Maracanaú' => ['ref' => 'Fortaleza',         'sigla' => 'CE'],
            'Crato' => ['ref' => 'Juazeiro do Norte', 'sigla' => 'CE'],
            // ES
            'Cariacica' => ['ref' => 'Vitória',           'sigla' => 'ES'],
            'Linhares' => ['ref' => 'Cachoeiro de Itapemirim', 'sigla' => 'ES'],
            // GO
            'Luziânia' => ['ref' => 'Goiânia',           'sigla' => 'GO'],
            'Águas Lindas de Goiás' => ['ref' => 'Goiânia',           'sigla' => 'GO'],
            // MA
            'Caxias' => ['ref' => 'Teresina',          'sigla' => 'PI'], // clima similar
            'Timon' => ['ref' => 'Teresina',          'sigla' => 'PI'],
            // MG
            'Ribeirão das Neves' => ['ref' => 'Belo Horizonte',    'sigla' => 'MG'],
            'Sete Lagoas' => ['ref' => 'Belo Horizonte',    'sigla' => 'MG'],
            'Divinópolis' => ['ref' => 'Belo Horizonte',    'sigla' => 'MG'],
            'Santa Luzia' => ['ref' => 'Belo Horizonte',    'sigla' => 'MG'],
            // MS
            'Corumbá' => ['ref' => 'Campo Grande',      'sigla' => 'MS'],
            // PA
            'Parauapebas' => ['ref' => 'Marabá',            'sigla' => 'PA'],
            // PB
            'Santa Rita' => ['ref' => 'João Pessoa',       'sigla' => 'PB'],
            // PE
            'Olinda' => ['ref' => 'Recife',            'sigla' => 'PE'],
            'Paulista' => ['ref' => 'Recife',            'sigla' => 'PE'],
            // PI
            'Parnaíba' => ['ref' => 'Teresina',          'sigla' => 'PI'],
            'Picos' => ['ref' => 'Teresina',          'sigla' => 'PI'],
            // PR
            'São José dos Pinhais' => ['ref' => 'Curitiba',          'sigla' => 'PR'],
            'Colombo' => ['ref' => 'Curitiba',          'sigla' => 'PR'],
            'Guarapuava' => ['ref' => 'Ponta Grossa',      'sigla' => 'PR'],
            'Paranaguá' => ['ref' => 'Curitiba',          'sigla' => 'PR'],
            // RJ
            'Belford Roxo' => ['ref' => 'Rio de Janeiro',    'sigla' => 'RJ'],
            'Volta Redonda' => ['ref' => 'Petrópolis',        'sigla' => 'RJ'],
            'Macaé' => ['ref' => 'Campos dos Goytacazes', 'sigla' => 'RJ'],
            // RN
            'Parnamirim' => ['ref' => 'Natal',             'sigla' => 'RN'],
            // RO
            'Ji-Paraná' => ['ref' => 'Porto Velho',       'sigla' => 'RO'],
            'Ariquemes' => ['ref' => 'Porto Velho',       'sigla' => 'RO'],
            // RS
            'Gravataí' => ['ref' => 'Porto Alegre',      'sigla' => 'RS'],
            'Viamão' => ['ref' => 'Porto Alegre',      'sigla' => 'RS'],
            'São Leopoldo' => ['ref' => 'Novo Hamburgo',     'sigla' => 'RS'],
            'Rio Grande' => ['ref' => 'Pelotas',           'sigla' => 'RS'],
            'Alvorada' => ['ref' => 'Porto Alegre',      'sigla' => 'RS'],
            'Sapucaia do Sul' => ['ref' => 'Canoas',            'sigla' => 'RS'],
            // SC
            'São José' => ['ref' => 'Florianópolis',     'sigla' => 'SC'],
            'Itajaí' => ['ref' => 'Joinville',         'sigla' => 'SC'],
            'Lages' => ['ref' => 'Chapecó',           'sigla' => 'SC'],
            // SE
            'Nossa Senhora do Socorro' => ['ref' => 'Aracaju',           'sigla' => 'SE'],
            'Lagarto' => ['ref' => 'Aracaju',           'sigla' => 'SE'],
            // SP — região metropolitana e interior
            'Santo André' => ['ref' => 'São Paulo',         'sigla' => 'SP'],
            'Osasco' => ['ref' => 'São Paulo',         'sigla' => 'SP'],
            'Mauá' => ['ref' => 'São Paulo',         'sigla' => 'SP'],
            'Mogi das Cruzes' => ['ref' => 'São Paulo',         'sigla' => 'SP'],
            'Diadema' => ['ref' => 'São Paulo',         'sigla' => 'SP'],
            'Carapicuíba' => ['ref' => 'São Paulo',         'sigla' => 'SP'],
            'Itaquaquecetuba' => ['ref' => 'São Paulo',         'sigla' => 'SP'],
            'São Vicente' => ['ref' => 'Santos',            'sigla' => 'SP'],
            'Suzano' => ['ref' => 'Mogi das Cruzes',   'sigla' => 'SP'], // usa SP
            'Limeira' => ['ref' => 'Campinas',          'sigla' => 'SP'],
            'Praia Grande' => ['ref' => 'Santos',            'sigla' => 'SP'],
            'Marília' => ['ref' => 'Presidente Prudente', 'sigla' => 'SP'],
            'Americana' => ['ref' => 'Campinas',          'sigla' => 'SP'],
            'Araraquara' => ['ref' => 'Ribeirão Preto',    'sigla' => 'SP'],
            'Araçatuba' => ['ref' => 'São José do Rio Preto', 'sigla' => 'SP'],
            'São Carlos' => ['ref' => 'Ribeirão Preto',    'sigla' => 'SP'],
            'Hortolândia' => ['ref' => 'Campinas',          'sigla' => 'SP'],
            'Catanduva' => ['ref' => 'São José do Rio Preto', 'sigla' => 'SP'],
            'Indaiatuba' => ['ref' => 'Campinas',          'sigla' => 'SP'],
            'Cotia' => ['ref' => 'São Paulo',         'sigla' => 'SP'],
            // TO
            'Gurupi' => ['ref' => 'Palmas',            'sigla' => 'TO'],
        ];

        $inserted = 0;
        $skipped = 0;

        foreach ($referencias as $cidade => $info) {
            // Busca a cidade sem irradiação
            $cidadeId = DB::table('cidades_estados')
                ->where('cidade', $cidade)
                ->value('id');

            if (! $cidadeId) {
                $skipped++;

                continue;
            }

            // Já tem irradiação? Pula
            if (DB::table('irradiacao_solar')->where('cidade_id', $cidadeId)->exists()) {
                continue;
            }

            // Busca irradiação da cidade de referência
            $refId = DB::table('cidades_estados')
                ->where('cidade', $info['ref'])
                ->where('sigla', $info['sigla'])
                ->value('id');

            // Fallback: procura pelo nome em qualquer estado se não encontrar com sigla
            if (! $refId) {
                $refId = DB::table('cidades_estados')->where('cidade', $info['ref'])->value('id');
            }

            if (! $refId) {
                $skipped++;

                continue;
            }

            $irr = DB::table('irradiacao_solar')->where('cidade_id', $refId)->first();

            if (! $irr) {
                $skipped++;

                continue;
            }

            DB::table('irradiacao_solar')->insert([
                'cidade_id' => $cidadeId,
                'media' => $irr->media,
                'jan' => $irr->jan, 'fev' => $irr->fev, 'mar' => $irr->mar,
                'abr' => $irr->abr, 'mai' => $irr->mai, 'jun' => $irr->jun,
                'jul' => $irr->jul, 'ago' => $irr->ago, 'set' => $irr->set,
                'out' => $irr->out, 'nov' => $irr->nov, 'dez' => $irr->dez,
            ]);
            $inserted++;
        }

        $this->command->info("Irradiação complementada: {$inserted} cidades. Puladas: {$skipped}.");
    }
}
