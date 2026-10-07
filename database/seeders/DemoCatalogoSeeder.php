<?php

namespace Database\Seeders;

use App\Models\Banco;
use App\Models\CategoriaProduto;
use App\Models\Config;
use App\Models\Estrutura;
use App\Models\Fornecedor;
use App\Models\Kit;
use App\Models\Marca;
use App\Models\MargemEstado;
use App\Models\MargemFornecedor;
use App\Models\MargemPrincipal;
use App\Models\Produto;
use Carbon\CarbonImmutable;
use Database\Seeders\Support\CatalogoDemo;
use Database\Seeders\Support\Ficticio;
use Database\Seeders\Support\Sorteio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo da base de demonstração: fornecedores, marcas, produtos, ~650 kits que cobrem
 * de 2 a 115 kWp nas estruturas e tensões usadas no dimensionamento, margens das 3
 * camadas de precificação, bancos e configurações da empresa. Chamado pelo
 * MarketingDemoSeeder (a semente aleatória é fixada lá).
 */
class DemoCatalogoSeeder extends Seeder
{
    /** [chave, nome, fator de preço, margem do fornecedor %, prefixo de SKU] */
    private const FORNECEDORES = [
        // Fornecedor da integração (coluna `integracao`). O nome real da distribuidora é confidencial na demonstração.
        ['parceira', 'Distribuidora Parceira', 1.00, 2.0, 'DPA'],
        ['solaris', 'Solaris Distribuidora Solar', 1.03, 1.5, 'SOL'],
        ['luminar', 'Luminar Energia Distribuidora', 0.98, 3.0, 'LUM'],
    ];

    /** Painel de cada fornecedor: [marca, modelo, watts, custo]. */
    private const PAINEIS = [
        'parceira' => ['Jinko Solar', 'Tiger Neo N-type 575W', 575, 545.00],
        'solaris' => ['Canadian Solar', 'HiKu7 Mono 550W', 550, 515.00],
        'luminar' => ['Trina Solar', 'Vertex N 610W', 610, 585.00],
    ];

    /** Inversores on-grid: [marca, modelo, kW nominal, custo, tensão]. */
    private const INVERSORES = [
        ['Growatt', 'MIN 3000TL-X', 3, 2650.00, 220], ['Growatt', 'MIN 5000TL-X', 5, 3480.00, 220], ['Growatt', 'MIN 6000TL-X', 6, 3890.00, 220],
        ['Fronius', 'Primo 8.2-1', 8.2, 9850.00, 220], ['Growatt', 'MIN 10000TL-X', 10, 6120.00, 220], ['Sungrow', 'SG12RS', 12, 7480.00, 220],
        ['Growatt', 'MID 15KTL3-X', 15, 8950.00, 380], ['Growatt', 'MID 20KTL3-X', 20, 10850.00, 380], ['Sungrow', 'SG33CX', 33, 15600.00, 380],
        ['Growatt', 'MAX 50KTL3 LV', 50, 21900.00, 380], ['Sungrow', 'SG75CX', 75, 31800.00, 380], ['WEG', 'SIW500H ST100', 100, 39500.00, 380],
    ];

    /** Quantidades de painéis dos kits (2,3 a ~115 kWp); acima disso o orçamento usa mais de um kit. */
    private const QTD_PAINEIS = [4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 16, 18, 20, 22, 24, 26, 28, 30, 33, 36, 40, 44, 48, 54, 60, 68, 76, 86, 96, 110, 124, 140, 160, 180, 200];

    /** Estruturas usadas no dimensionamento (nomes do EstruturasSeeder). */
    private const ESTRUTURAS = ['Telha Colonial (Cerâmico)', 'Telha Metálica Perfil 55cm', 'Telha Ondulada', 'Laje', 'Solo'];

    /** Abreviação da estrutura no SKU (único por fornecedor). */
    private const ABREVIACOES = ['Telha Colonial (Cerâmico)' => 'CER', 'Telha Metálica Perfil 55cm' => 'MET', 'Telha Ondulada' => 'OND', 'Laje' => 'LAJ', 'Solo' => 'SOL'];

    public function run(): void
    {
        $categorias = CategoriaProduto::pluck('id', 'slug');
        $marcas = $this->marcas();
        $fornecedores = $this->fornecedores();

        $produtos = $this->produtos($categorias, $marcas, $fornecedores);
        $this->kits($fornecedores, $produtos);
        $this->margens($fornecedores);
        $this->bancos();
        $this->configs();
    }

    /** @return array<string, int> */
    private function marcas(): array
    {
        $nomes = ['Jinko Solar', 'Canadian Solar', 'Trina Solar', 'Growatt', 'Fronius', 'Sungrow', 'WEG', 'Deye', 'BYD', 'Anauger', 'Clamper', 'Epever', 'Romagnole'];

        return collect($nomes)->mapWithKeys(fn ($n) => [$n => Marca::firstOrCreate(['nome' => $n], ['ativo' => true])->id])->all();
    }

    /** @return array<string, Fornecedor> */
    private function fornecedores(): array
    {
        $saida = [];
        foreach (self::FORNECEDORES as $i => [$chave, $nome, , , $prefixo]) {
            $saida[$chave] = Fornecedor::firstOrCreate(['nome' => $nome], [
                'email' => "comercial@{$chave}.fornecedor.demo",
                'telefone' => sprintf('(20) 0000-%04d', 200 + $i),
                'representante' => Sorteio::escolher(CatalogoDemo::NOMES_M).' '.Sorteio::escolher(CatalogoDemo::SOBRENOMES),
                'site' => "https://{$chave}.fornecedor.demo",
                'anotacoes' => $chave === 'parceira' ? 'Catálogo sincronizado diariamente pela integração.' : 'Pedido mínimo de 1 kit; frete CIF acima de R$ 15 mil.',
                'ativo' => true,
                'integracao' => $chave === 'parceira' ? Fornecedor::INTEGRACAO_DISTRIBUIDORA : null,
            ]);
            if (! $saida[$chave]->cnpj) {
                $saida[$chave]->update(['cnpj' => Ficticio::cnpjFornecedor($saida[$chave]->id)]);
            }
        }

        return $saida;
    }

    /**
     * @param  Collection<string, int>  $categorias
     * @param  array<string, int>  $marcas
     * @param  array<string, Fornecedor>  $fornecedores
     * @return array{paineis: array<string, Produto>, inversores: array<int, Produto>}
     */
    private function produtos($categorias, array $marcas, array $fornecedores): array
    {
        $criar = fn (string $categoria, string $marca, string $nome, array $extra) => Produto::firstOrCreate(
            ['sku' => $extra['sku']],
            $extra + ['categoria_id' => $categorias[$categoria], 'marca_id' => $marcas[$marca] ?? null, 'nome' => $nome, 'ativo' => true, 'ativo_fornecedor' => true],
        );

        $paineis = [];
        foreach (self::PAINEIS as $chave => [$marca, $modelo, $watts, $custo]) {
            $paineis[$chave] = $criar('painel-solar', $marca, "Módulo {$marca} {$modelo}", [
                'sku' => 'PNL-'.strtoupper(substr($chave, 0, 3))."-{$watts}", 'modelo' => $modelo, 'fornecedor_id' => $fornecedores[$chave]->id,
                'potencia' => $watts, 'unidade_potencia' => 'W', 'preco_custo' => $custo, 'garantia' => '12 anos produto / 30 anos performance linear',
                'atributos' => ['eficiencia' => '22,5%', 'celulas' => 144, 'dimensoes_mm' => '2278 x 1134 x 30'],
            ]);
        }

        $inversores = [];
        foreach (self::INVERSORES as $i => [$marca, $modelo, $kw, $custo, $tensao]) {
            $inversores[$i] = $criar('inversor-solar', $marca, "Inversor {$marca} {$modelo}", [
                'sku' => 'INV-'.strtoupper(substr($marca, 0, 3)).'-'.str_replace('.', '', (string) $kw), 'modelo' => $modelo,
                'fornecedor_id' => $fornecedores['parceira']->id, 'potencia' => $kw, 'unidade_potencia' => 'kW', 'tensao' => $tensao,
                'preco_custo' => $custo, 'garantia' => $marca === 'Fronius' ? '7 anos (extensível a 12)' : '10 anos',
            ]);
        }

        // Demais categorias: o Catálogo e a busca de itens avulsos do orçamento não ficam vazios.
        $avulsos = [
            ['inversor-solar', 'Deye', 'Inversor Híbrido Deye SUN-5K-SG04LP1', 'HIB-DEY-5', 6900.00, 5, 'kW', '10 anos'],
            ['inversor-solar', 'Deye', 'Inversor Híbrido Deye SUN-8K-SG01LP1', 'HIB-DEY-8', 9800.00, 8, 'kW', '10 anos'],
            ['transformador', 'WEG', 'Transformador a seco 75 kVA 380/220V', 'TRF-WEG-75', 18900.00, 75, 'kVA', '2 anos'],
            ['transformador', 'WEG', 'Transformador a seco 150 kVA 380/220V', 'TRF-WEG-150', 31500.00, 150, 'kVA', '2 anos'],
            ['bateria', 'BYD', 'Bateria BYD Battery-Box Premium 5,1 kWh', 'BAT-BYD-51', 11800.00, 5.1, 'kWh', '10 anos'],
            ['bateria', 'Deye', 'Bateria Deye SE-G5.1 Pro 5,12 kWh', 'BAT-DEY-512', 9900.00, 5.12, 'kWh', '10 anos'],
            ['controlador-carga', 'Epever', 'Controlador de carga MPPT Epever 40A', 'CTR-EPV-40', 890.00, 40, 'A', '2 anos'],
            ['bomba-solar', 'Anauger', 'Bomba solar Anauger P100 (poço até 40 m)', 'BMB-ANA-P100', 2450.00, 0.3, 'kW', '2 anos'],
            ['bomba-solar', 'Anauger', 'Bomba solar Anauger R100 (superfície)', 'BMB-ANA-R100', 3150.00, 0.6, 'kW', '2 anos'],
            ['iluminacao-solar', 'Clamper', 'Refletor solar LED 100 W com painel', 'ILU-CLA-100', 320.00, 100, 'W', '1 ano'],
            ['cabo-conector', 'Clamper', 'Cabo solar 6 mm² preto (metro)', 'CAB-6MM-PT', 6.90, null, null, '—'],
            ['cabo-conector', 'Clamper', 'Par de conectores MC4', 'CON-MC4-PAR', 14.50, null, null, '—'],
            ['protecao-eletrica', 'Clamper', 'String box 2 entradas / 2 saídas 1000 Vcc', 'PRT-SBX-22', 680.00, null, null, '2 anos'],
            ['protecao-eletrica', 'Clamper', 'DPS CC 1000 V 40 kA', 'PRT-DPS-1000', 189.00, null, null, '2 anos'],
            ['outros', 'Romagnole', 'Estrutura para solo — mesa 8 módulos', 'EST-ROM-SOLO8', 1650.00, null, null, '12 anos'],
            ['outros', 'Romagnole', 'Kit fixação telha cerâmica (4 módulos)', 'EST-ROM-CER4', 395.00, null, null, '12 anos'],
        ];
        foreach ($avulsos as $i => [$cat, $marca, $nome, $sku, $custo, $pot, $unid, $garantia]) {
            if (! isset($categorias[$cat])) {
                continue;
            }
            $criar($cat, $marca, $nome, [
                'sku' => $sku, 'fornecedor_id' => $fornecedores[$i % 3 === 0 ? 'solaris' : 'parceira']->id, 'potencia' => $pot,
                'unidade_potencia' => $unid, 'preco_custo' => $custo, 'garantia' => $garantia,
                'unidade' => str_contains($nome, 'metro') ? 'm' : 'un',
                // Um item descontinuado pelo fornecedor: aparece desativado no Catálogo.
                'ativo_fornecedor' => $sku !== 'BAT-BYD-51',
            ]);
        }

        return ['paineis' => $paineis, 'inversores' => $inversores];
    }

    /**
     * Kits on-grid por fornecedor × quantidade de painéis × estrutura × tensão, mais híbridos,
     * off-grid e bombeamento. Inseridos em lote: kits só registram auditoria em updated/deleted.
     *
     * @param  array<string, Fornecedor>  $fornecedores
     * @param  array{paineis: array<string, Produto>, inversores: array<int, Produto>}  $produtos
     */
    private function kits(array $fornecedores, array $produtos): void
    {
        if (Kit::whereIn('fornecedor_id', collect($fornecedores)->pluck('id'))->count() > 100) {
            return;
        }

        $estruturas = Estrutura::whereIn('nome', self::ESTRUTURAS)->pluck('id', 'nome');
        $agora = CarbonImmutable::now(); // o MarketingDemoSeeder para o relógio antes da operação começar
        $linhas = [];
        $componentes = [];

        foreach (self::FORNECEDORES as [$chave, , $fatorPreco, , $prefixo]) {
            $painel = $produtos['paineis'][$chave];
            $watts = (int) $painel->potencia;

            foreach (self::QTD_PAINEIS as $qtd) {
                $kwp = round($qtd * $watts / 1000, 3);
                // Custo por Wp cai com a escala (R$ 2,70/Wp em 2 kWp → ~R$ 1,75/Wp em 100 kWp).
                $custoWp = max(1.70, 2.85 - 0.26 * log($kwp + 1));

                foreach ([220, 380] as $tensao) {
                    if (($tensao === 220 && $kwp > 24) || ($tensao === 380 && $kwp < 6)) {
                        continue;
                    }
                    $inversor = $this->inversorPara($kwp, $tensao, $produtos['inversores']);

                    foreach ($estruturas as $nomeEstrutura => $estruturaId) {
                        // Telhados residenciais não recebem usinas grandes; solo não recebe as muito pequenas.
                        if (($nomeEstrutura === 'Solo' && $kwp < 8) || ($nomeEstrutura !== 'Solo' && $nomeEstrutura !== 'Telha Metálica Perfil 55cm' && $kwp > 40)) {
                            continue;
                        }
                        $abrev = self::ABREVIACOES[$nomeEstrutura];
                        $custo = round($kwp * 1000 * $custoWp * $fatorPreco * Sorteio::decimal(0.97, 1.03, 4), 2);
                        $sku = sprintf('%s-K%03d-%d-%s', $prefixo, $qtd, $tensao, $abrev);

                        $linhas[] = [
                            'fornecedor_id' => $fornecedores[$chave]->id, 'estrutura_id' => $estruturaId,
                            'nome' => sprintf('Kit Solar %s kWp — %d× %s + %s — %s', number_format($kwp, 2, ',', '.'), $qtd, $painel->modelo, $inversor->modelo, $nomeEstrutura),
                            'modelo' => "{$qtd}x{$watts}W/{$inversor->modelo}", 'sku' => $sku, 'potencia_kwp' => $kwp, 'tensao' => $tensao,
                            'inclui_trafo' => false, 'preco_custo' => $custo, 'margem_padrao' => 0, 'categoria' => 'ongrid',
                            // Poucos kits retirados pelo fornecedor na sincronização.
                            'ativo' => true, 'ativo_fornecedor' => ! Sorteio::chance(0.03),
                            'observacoes' => $kwp > 60 ? 'Inversores em paralelo conforme projeto.' : null,
                            'created_at' => $agora, 'updated_at' => $agora,
                        ];
                        $componentes[$sku] = [[$painel->id, $qtd], [$inversor->id, max(1, (int) ceil($kwp / 1.25 / (float) $inversor->potencia))]];
                    }
                }
            }
        }

        // Híbridos, off-grid e bombeamento (categoria filtrada no dimensionamento rural).
        $hibrido = Produto::where('sku', 'HIB-DEY-5')->first();
        $bomba = Produto::where('sku', 'BMB-ANA-P100')->first();
        foreach ([[6, 'hibrido', 'Telha Colonial (Cerâmico)'], [10, 'hibrido', 'Laje'], [14, 'hibrido', 'Telha Metálica Perfil 55cm'],
            [4, 'offgrid', 'Solo'], [8, 'offgrid', 'Solo'], [4, 'bomba', 'Solo'], [8, 'bomba', 'Solo'], [14, 'bomba', 'Solo'], [24, 'bomba', 'Solo']] as [$qtd, $categoria, $estrutura]) {
            $painel = $produtos['paineis']['parceira'];
            $kwp = round($qtd * 0.575, 3);
            $sku = sprintf('EDL-%s-%03d', strtoupper(substr($categoria, 0, 3)), $qtd);
            $linhas[] = [
                'fornecedor_id' => $fornecedores['parceira']->id, 'estrutura_id' => $estruturas[$estrutura],
                'nome' => sprintf('Kit %s %s kWp — %d× %s', Kit::CATEGORIAS[$categoria], number_format($kwp, 2, ',', '.'), $qtd, $painel->modelo),
                'modelo' => "{$qtd}x575W", 'sku' => $sku, 'potencia_kwp' => $kwp, 'tensao' => 220, 'inclui_trafo' => false,
                'preco_custo' => round($kwp * 1000 * ($categoria === 'bomba' ? 2.4 : 3.6), 2), 'margem_padrao' => 0, 'categoria' => $categoria,
                'ativo' => true, 'ativo_fornecedor' => true, 'observacoes' => $categoria === 'bomba' ? 'Inclui bomba e controlador.' : 'Inclui banco de baterias.',
                'created_at' => $agora, 'updated_at' => $agora,
            ];
            $componentes[$sku] = [[$painel->id, $qtd], [($categoria === 'bomba' ? $bomba : $hibrido)?->id, 1]];
        }

        foreach (array_chunk($linhas, 200) as $lote) {
            Kit::insert($lote);
        }

        $ids = Kit::whereIn('sku', array_keys($componentes))->pluck('id', 'sku');
        $linhasComp = [];
        foreach ($componentes as $sku => $itens) {
            foreach ($itens as [$produtoId, $quantidade]) {
                if ($produtoId && isset($ids[$sku])) {
                    $linhasComp[] = ['kit_id' => $ids[$sku], 'produto_id' => $produtoId, 'quantidade' => $quantidade];
                }
            }
        }
        foreach (array_chunk($linhasComp, 300) as $lote) {
            DB::table('kit_componentes')->insert($lote);
        }
    }

    /** @param  array<int, Produto>  $inversores */
    private function inversorPara(float $kwp, int $tensao, array $inversores): Produto
    {
        // Sobrecarga típica de 20–30%: inversor ~ kWp / 1,25; acima de 100 kW, vários em paralelo.
        $alvo = min($kwp / 1.25, 100);
        $candidatos = array_filter($inversores, fn (Produto $p) => $tensao === 380 ? true : (int) $p->tensao === 220);

        usort($candidatos, fn (Produto $a, Produto $b) => abs((float) $a->potencia - $alvo) <=> abs((float) $b->potencia - $alvo));

        return $candidatos[0];
    }

    /** @param  array<string, Fornecedor>  $fornecedores */
    private function margens(array $fornecedores): void
    {
        if (MargemPrincipal::count() === 0) {
            foreach ([['Residencial pequeno', 0, 5, 30], ['Residencial', 5, 10, 27], ['Comercial pequeno', 10, 20, 24],
                ['Comercial', 20, 50, 21], ['Usina média', 50, 100, 18], ['Usina grande', 100, null, 15]] as $i => [$nome, $min, $max, $margem]) {
                MargemPrincipal::create(['nome' => $nome, 'potencia_min' => $min, 'potencia_max' => $max, 'margem' => $margem, 'ordem' => $i + 1]);
            }
        }

        foreach (['SP' => ['São Paulo', 3.0], 'MG' => ['Minas Gerais', 2.5], 'GO' => ['Goiás', 3.5], 'PR' => ['Paraná', 2.0]] as $uf => [$nome, $margem]) {
            MargemEstado::firstOrCreate(['estado' => $uf], ['nome_estado' => $nome, 'margem' => $margem]);
        }

        foreach (self::FORNECEDORES as [$chave, , , $margem]) {
            MargemFornecedor::firstOrCreate(['fornecedor_id' => $fornecedores[$chave]->id], ['margem' => $margem]);
        }
    }

    private function bancos(): void
    {
        foreach ([['Banco Horizonte — Crédito Solar', 1.49, 72, 90], ['Cooperativa Vale Verde — Energia Rural', 1.19, 60, 180],
            ['Financeira Sol+ — CDC', 1.89, 48, 60], ['Banco Progresso — Capital de Giro PJ', 1.39, 60, 120]] as [$nome, $juros, $parcelas, $carencia]) {
            Banco::firstOrCreate(['nome' => $nome], ['juros_mensal' => $juros, 'qtd_parcelas' => $parcelas, 'carencia' => $carencia, 'ativo' => true]);
        }
    }

    private function configs(): void
    {
        foreach (CatalogoDemo::EMPRESA as $chave => $valor) {
            Config::set($chave, $valor, 'empresa');
        }
        Config::set('empresa_cnpj', '00.000.001/0001-00', 'empresa');
        Config::set('empresa_endereco', 'Avenida Brasil, 1500 — Campinas/SP', 'empresa');
        Config::set('empresa_site', 'https://www.'.CatalogoDemo::DOMINIO_EQUIPE, 'empresa');
        Config::set('proposta_validade_dias', '15', 'proposta');
        Config::set('proposta_garantia_instalacao', '2 anos de garantia de instalação e 1 visita de manutenção no 1º ano', 'proposta');
        Config::set('proposta_rodape', 'Valores sujeitos à vistoria técnica e à aprovação da concessionária.', 'proposta');
        Config::set('funil.dias_caixa_entrada', '5', 'funil');
        Config::set('funil.max_tentativas_reativacao', '3', 'funil');
    }
}
