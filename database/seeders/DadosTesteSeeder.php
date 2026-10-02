<?php

namespace Database\Seeders;

use App\Models\CategoriaProduto;
use App\Models\Cliente;
use App\Models\Estrutura;
use App\Models\Fornecedor;
use App\Models\Kit;
use App\Models\Lead;
use App\Models\Marca;
use App\Models\MargemEstado;
use App\Models\MargemFornecedor;
use App\Models\MargemPrincipal;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DadosTesteSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Marcas ───────────────────────────────────────────────────
        $marcas = [
            ['nome' => 'Canadian Solar', 'ativo' => true],
            ['nome' => 'Risen Energy', 'ativo' => true],
            ['nome' => 'JA Solar', 'ativo' => true],
            ['nome' => 'Deye', 'ativo' => true],
            ['nome' => 'Growatt', 'ativo' => true],
            ['nome' => 'ABB / Fimer', 'ativo' => true],
            ['nome' => 'Fronius', 'ativo' => true],
            ['nome' => 'WEG', 'ativo' => true],
            ['nome' => 'Sunergy', 'ativo' => true],
            ['nome' => 'BYD', 'ativo' => true],
        ];
        foreach ($marcas as $m) {
            Marca::firstOrCreate(['nome' => $m['nome']], $m);
        }
        $this->command->info('Marcas: OK');

        // ─── Fornecedores ─────────────────────────────────────────────
        $fornecedoresData = [
            ['nome' => 'Aldo Solar', 'cnpj' => '07.032.722/0001-77', 'email' => 'comercial@aldo.com.br', 'ativo' => true],
            ['nome' => 'Edeltec', 'cnpj' => '10.543.185/0001-95', 'email' => 'vendas@edeltec.com.br', 'ativo' => true],
            ['nome' => 'Solar Conecta', 'cnpj' => '28.182.545/0001-01', 'email' => 'contato@solarconecta.com.br', 'ativo' => true],
            ['nome' => 'Renovigi', 'cnpj' => '24.879.032/0001-44', 'email' => 'comercial@renovigi.com.br', 'ativo' => true],
        ];
        foreach ($fornecedoresData as $f) {
            Fornecedor::firstOrCreate(['nome' => $f['nome']], $f);
        }
        $this->command->info('Fornecedores: OK');

        $aldoId = Fornecedor::where('nome', 'like', '%Aldo%')->value('id');
        $edeltecId = Fornecedor::where('nome', 'like', '%Edeltec%')->value('id');
        $canadianId = Marca::where('nome', 'like', '%Canadian%')->value('id');
        $jaId = Marca::where('nome', 'like', '%JA%')->value('id');
        $deyeId = Marca::where('nome', 'like', '%Deye%')->value('id');
        $growattId = Marca::where('nome', 'like', '%Growatt%')->value('id');
        $wegId = Marca::where('nome', 'like', '%WEG%')->value('id');
        $bydId = Marca::where('nome', 'like', '%BYD%')->value('id');

        $painelCat = CategoriaProduto::where('slug', 'painel-solar')->value('id');
        $inversorCat = CategoriaProduto::where('slug', 'inversor-solar')->value('id');
        $bateriasCat = CategoriaProduto::where('slug', 'bateria-armazenamento')->value('id');
        $bombaCat = CategoriaProduto::where('slug', 'bomba-solar')->value('id');

        // ─── Produtos ─────────────────────────────────────────────────
        $produtos = [
            // Painéis
            ['nome' => 'Canadian Solar CS6R-410MS', 'categoria_id' => $painelCat, 'marca_id' => $canadianId, 'fornecedor_id' => $aldoId,
                'modelo' => 'CS6R-410MS', 'sku' => 'PNL-CS-410', 'potencia' => 410, 'unidade_potencia' => 'Wp',
                'preco_custo' => 580.00, 'unidade' => 'un', 'garantia' => '12 anos produto / 25 anos desempenho', 'ativo' => true, 'ativo_fornecedor' => true],
            ['nome' => 'Canadian Solar CS6R-460MS', 'categoria_id' => $painelCat, 'marca_id' => $canadianId, 'fornecedor_id' => $aldoId,
                'modelo' => 'CS6R-460MS', 'sku' => 'PNL-CS-460', 'potencia' => 460, 'unidade_potencia' => 'Wp',
                'preco_custo' => 650.00, 'unidade' => 'un', 'garantia' => '12 anos produto / 25 anos desempenho', 'ativo' => true, 'ativo_fornecedor' => true],
            ['nome' => 'JA Solar JAM54S30-415', 'categoria_id' => $painelCat, 'marca_id' => $jaId, 'fornecedor_id' => $edeltecId,
                'modelo' => 'JAM54S30-415', 'sku' => 'PNL-JA-415', 'potencia' => 415, 'unidade_potencia' => 'Wp',
                'preco_custo' => 590.00, 'unidade' => 'un', 'garantia' => '12 anos produto / 25 anos desempenho', 'ativo' => true, 'ativo_fornecedor' => true],
            ['nome' => 'JA Solar JAM72S30-540', 'categoria_id' => $painelCat, 'marca_id' => $jaId, 'fornecedor_id' => $edeltecId,
                'modelo' => 'JAM72S30-540', 'sku' => 'PNL-JA-540', 'potencia' => 540, 'unidade_potencia' => 'Wp',
                'preco_custo' => 780.00, 'unidade' => 'un', 'garantia' => '12 anos produto / 25 anos desempenho', 'ativo' => true, 'ativo_fornecedor' => true],
            // Inversores
            ['nome' => 'Deye SUN-5K-G05', 'categoria_id' => $inversorCat, 'marca_id' => $deyeId, 'fornecedor_id' => $aldoId,
                'modelo' => 'SUN-5K-G05', 'sku' => 'INV-DY-5K', 'potencia' => 5, 'unidade_potencia' => 'kW',
                'tensao' => 220, 'preco_custo' => 2850.00, 'unidade' => 'un', 'garantia' => '5 anos', 'ativo' => true, 'ativo_fornecedor' => true],
            ['nome' => 'Deye SUN-8K-SG03LP1', 'categoria_id' => $inversorCat, 'marca_id' => $deyeId, 'fornecedor_id' => $aldoId,
                'modelo' => 'SUN-8K-SG03LP1', 'sku' => 'INV-DY-8K', 'potencia' => 8, 'unidade_potencia' => 'kW',
                'tensao' => 220, 'preco_custo' => 4200.00, 'unidade' => 'un', 'garantia' => '5 anos', 'ativo' => true, 'ativo_fornecedor' => true],
            ['nome' => 'Growatt MIN 4200TL-XH', 'categoria_id' => $inversorCat, 'marca_id' => $growattId, 'fornecedor_id' => $edeltecId,
                'modelo' => 'MIN 4200TL-XH', 'sku' => 'INV-GR-4K2', 'potencia' => 4.2, 'unidade_potencia' => 'kW',
                'tensao' => 220, 'preco_custo' => 2400.00, 'unidade' => 'un', 'garantia' => '5 anos', 'ativo' => true, 'ativo_fornecedor' => true],
            ['nome' => 'WEG SIW300H010', 'categoria_id' => $inversorCat, 'marca_id' => $wegId, 'fornecedor_id' => $edeltecId,
                'modelo' => 'SIW300H010', 'sku' => 'INV-WEG-10K', 'potencia' => 10, 'unidade_potencia' => 'kW',
                'tensao' => 220, 'preco_custo' => 6800.00, 'unidade' => 'un', 'garantia' => '5 anos', 'ativo' => true, 'ativo_fornecedor' => true],
            // Baterias
            ['nome' => 'BYD Battery-Box Premium HVS 5.1', 'categoria_id' => $bateriasCat, 'marca_id' => $bydId, 'fornecedor_id' => $aldoId,
                'modelo' => 'HVS 5.1', 'sku' => 'BAT-BYD-5K', 'potencia' => 5.1, 'unidade_potencia' => 'kWh',
                'preco_custo' => 14500.00, 'unidade' => 'un', 'garantia' => '10 anos', 'ativo' => true, 'ativo_fornecedor' => true],
            ['nome' => 'BYD Battery-Box Premium HVS 10.2', 'categoria_id' => $bateriasCat, 'marca_id' => $bydId, 'fornecedor_id' => $aldoId,
                'modelo' => 'HVS 10.2', 'sku' => 'BAT-BYD-10K', 'potencia' => 10.2, 'unidade_potencia' => 'kWh',
                'preco_custo' => 28000.00, 'unidade' => 'un', 'garantia' => '10 anos', 'ativo' => true, 'ativo_fornecedor' => true],
        ];

        foreach ($produtos as $p) {
            if ($p['categoria_id']) {
                Produto::firstOrCreate(['sku' => $p['sku']], $p);
            }
        }
        $this->command->info('Produtos: OK');

        // ─── Margens por fornecedor ───────────────────────────────────
        foreach (Fornecedor::all() as $f) {
            MargemFornecedor::firstOrCreate(['fornecedor_id' => $f->id], ['margem' => 2.0]);
        }

        // ─── Margens por estado ───────────────────────────────────────
        $margemEstados = [
            'SP' => ['nome' => 'São Paulo',             'margem' => 3.0],
            'RJ' => ['nome' => 'Rio de Janeiro',        'margem' => 2.5],
            'MG' => ['nome' => 'Minas Gerais',          'margem' => 2.0],
            'ES' => ['nome' => 'Espírito Santo',        'margem' => 2.0],
            'PR' => ['nome' => 'Paraná',                'margem' => 1.5],
            'SC' => ['nome' => 'Santa Catarina',        'margem' => 1.5],
            'RS' => ['nome' => 'Rio Grande do Sul',     'margem' => 1.5],
            'GO' => ['nome' => 'Goiás',                 'margem' => 2.0],
            'MT' => ['nome' => 'Mato Grosso',           'margem' => 2.5],
            'MS' => ['nome' => 'Mato Grosso do Sul',    'margem' => 2.5],
            'DF' => ['nome' => 'Distrito Federal',      'margem' => 2.0],
            'BA' => ['nome' => 'Bahia',                 'margem' => 2.0],
            'CE' => ['nome' => 'Ceará',                 'margem' => 1.5],
            'PE' => ['nome' => 'Pernambuco',            'margem' => 1.5],
            'RN' => ['nome' => 'Rio Grande do Norte',   'margem' => 1.5],
            'PB' => ['nome' => 'Paraíba',               'margem' => 1.5],
            'AL' => ['nome' => 'Alagoas',               'margem' => 1.5],
            'SE' => ['nome' => 'Sergipe',               'margem' => 1.5],
            'PI' => ['nome' => 'Piauí',                 'margem' => 1.5],
            'MA' => ['nome' => 'Maranhão',              'margem' => 2.0],
            'PA' => ['nome' => 'Pará',                  'margem' => 2.0],
            'AM' => ['nome' => 'Amazonas',              'margem' => 3.0],
            'TO' => ['nome' => 'Tocantins',             'margem' => 2.5],
            'AC' => ['nome' => 'Acre',                  'margem' => 3.0],
            'RO' => ['nome' => 'Rondônia',              'margem' => 3.0],
            'RR' => ['nome' => 'Roraima',               'margem' => 3.5],
            'AP' => ['nome' => 'Amapá',                 'margem' => 3.0],
        ];
        foreach ($margemEstados as $uf => $info) {
            MargemEstado::firstOrCreate(
                ['estado' => $uf],
                ['nome_estado' => $info['nome'], 'margem' => $info['margem']]
            );
        }

        // ─── Margens principal (por faixa de potência) ────────────────
        $faixas = [
            ['nome' => 'Micro (até 3 kWp)',    'potencia_min' => 0,  'potencia_max' => 3,   'margem' => 35.0, 'ordem' => 1],
            ['nome' => 'Mini-micro (3–10 kWp)', 'potencia_min' => 3,  'potencia_max' => 10,  'margem' => 30.0, 'ordem' => 2],
            ['nome' => 'Pequeno (10–20 kWp)', 'potencia_min' => 10, 'potencia_max' => 20,  'margem' => 25.0, 'ordem' => 3],
            ['nome' => 'Médio (20–50 kWp)',   'potencia_min' => 20, 'potencia_max' => 50,  'margem' => 22.0, 'ordem' => 4],
            ['nome' => 'Grande (50–100 kWp)', 'potencia_min' => 50, 'potencia_max' => 100, 'margem' => 18.0, 'ordem' => 5],
            ['nome' => 'Industrial (100+ kWp)', 'potencia_min' => 100, 'potencia_max' => null, 'margem' => 15.0, 'ordem' => 6],
        ];
        foreach ($faixas as $f) {
            MargemPrincipal::firstOrCreate(['nome' => $f['nome']], $f);
        }
        $this->command->info('Precificação: OK');

        // ─── Kits ─────────────────────────────────────────────────────
        $aldo = Fornecedor::where('nome', 'like', '%Aldo%')->first();
        $edeltec = Fornecedor::where('nome', 'like', '%Edeltec%')->first();
        $eixo_laje = Estrutura::where('nome', 'like', '%Laje%')->orWhere('nome', 'like', '%Cerâmico%')->first();
        $eixo_fibro = Estrutura::where('nome', 'like', '%Fibro%')->first();
        $eixo_solo = Estrutura::where('nome', 'like', '%Solo%')->first();

        if (! $eixo_laje) {
            $eixo_laje = Estrutura::first();
        }
        if (! $eixo_fibro) {
            $eixo_fibro = $eixo_laje;
        }
        if (! $eixo_solo) {
            $eixo_solo = $eixo_laje;
        }

        $kitsData = [
            ['nome' => 'Kit 2,05 kWp - Canadian 410W + Growatt 4,2k', 'potencia_kwp' => 2.050, 'estrutura_id' => $eixo_laje?->id, 'fornecedor_id' => $aldo?->id, 'tensao' => 220, 'preco_custo' => 5400.00],
            ['nome' => 'Kit 2,46 kWp - Canadian 410W + Growatt 4,2k', 'potencia_kwp' => 2.460, 'estrutura_id' => $eixo_laje?->id, 'fornecedor_id' => $aldo?->id, 'tensao' => 220, 'preco_custo' => 6200.00],
            ['nome' => 'Kit 3,28 kWp - Canadian 410W + Deye 5k', 'potencia_kwp' => 3.280, 'estrutura_id' => $eixo_laje?->id, 'fornecedor_id' => $aldo?->id, 'tensao' => 220, 'preco_custo' => 7800.00],
            ['nome' => 'Kit 4,10 kWp - Canadian 410W + Deye 5k', 'potencia_kwp' => 4.100, 'estrutura_id' => $eixo_laje?->id, 'fornecedor_id' => $aldo?->id, 'tensao' => 220, 'preco_custo' => 9200.00],
            ['nome' => 'Kit 5,12 kWp - JA 415W + Deye 5k', 'potencia_kwp' => 5.120, 'estrutura_id' => $eixo_fibro?->id, 'fornecedor_id' => $edeltec?->id, 'tensao' => 220, 'preco_custo' => 11500.00],
            ['nome' => 'Kit 6,21 kWp - JA 415W + Deye 8k', 'potencia_kwp' => 6.210, 'estrutura_id' => $eixo_fibro?->id, 'fornecedor_id' => $edeltec?->id, 'tensao' => 220, 'preco_custo' => 14200.00],
            ['nome' => 'Kit 8,28 kWp - JA 415W + Deye 8k', 'potencia_kwp' => 8.280, 'estrutura_id' => $eixo_fibro?->id, 'fornecedor_id' => $edeltec?->id, 'tensao' => 220, 'preco_custo' => 18500.00],
            ['nome' => 'Kit 10,35 kWp - Canadian 460W + WEG 10k', 'potencia_kwp' => 10.350, 'estrutura_id' => $eixo_laje?->id, 'fornecedor_id' => $aldo?->id, 'tensao' => 220, 'preco_custo' => 23800.00],
            ['nome' => 'Kit 13,80 kWp - Canadian 460W + WEG 10k', 'potencia_kwp' => 13.800, 'estrutura_id' => $eixo_laje?->id, 'fornecedor_id' => $aldo?->id, 'tensao' => 220, 'preco_custo' => 31500.00],
            ['nome' => 'Kit 18,40 kWp - Canadian 460W + WEG 20k', 'potencia_kwp' => 18.400, 'estrutura_id' => $eixo_laje?->id, 'fornecedor_id' => $aldo?->id, 'tensao' => 220, 'preco_custo' => 42000.00],
            ['nome' => 'Kit 24,84 kWp - JA 540W + WEG 25k', 'potencia_kwp' => 24.840, 'estrutura_id' => $eixo_solo?->id, 'fornecedor_id' => $edeltec?->id, 'tensao' => 380, 'preco_custo' => 58000.00],
            ['nome' => 'Kit 37,80 kWp - JA 540W + WEG 40k', 'potencia_kwp' => 37.800, 'estrutura_id' => $eixo_solo?->id, 'fornecedor_id' => $edeltec?->id, 'tensao' => 380, 'preco_custo' => 88000.00],
            ['nome' => 'Kit 54,00 kWp - JA 540W + WEG 60k', 'potencia_kwp' => 54.000, 'estrutura_id' => $eixo_solo?->id, 'fornecedor_id' => $edeltec?->id, 'tensao' => 380, 'preco_custo' => 128000.00],
        ];

        foreach ($kitsData as $k) {
            if (! $k['estrutura_id'] || ! $k['fornecedor_id']) {
                continue;
            }
            Kit::firstOrCreate(
                ['nome' => $k['nome']],
                array_merge($k, ['ativo' => true, 'ativo_fornecedor' => true])
            );
        }
        $this->command->info('Kits: OK');

        // ─── Clientes de teste ────────────────────────────────────────
        $consultorId = User::where('tipo', 'consultor')->value('id');
        $spId = DB::table('cidades_estados')->where('cidade', 'São Paulo')->where('sigla', 'SP')->value('id');
        $camposId = DB::table('cidades_estados')->where('cidade', 'Campinas')->where('sigla', 'SP')->value('id');
        $bhId = DB::table('cidades_estados')->where('cidade', 'Belo Horizonte')->where('sigla', 'MG')->value('id');

        $clientesBase = [
            ['nome' => 'João Carlos Ferreira', 'tipo_pessoa' => 'pf', 'cpf' => '123.456.789-00', 'email' => 'joao.ferreira@gmail.com', 'celular' => '(11) 98765-4321', 'cidade_id' => $spId, 'status' => 'novo'],
            ['nome' => 'Maria Aparecida Santos', 'tipo_pessoa' => 'pf', 'cpf' => '987.654.321-00', 'email' => 'maria.santos@gmail.com', 'celular' => '(11) 97654-3210', 'cidade_id' => $spId, 'status' => 'orcamento_gerado'],
            ['razao_social' => 'Comercial Pereira Ltda', 'nome' => null, 'tipo_pessoa' => 'pj', 'cnpj' => '12.345.678/0001-90', 'email' => 'compras@pereira.com.br', 'celular' => '(19) 3845-1200', 'cidade_id' => $camposId, 'status' => 'visita_agendada'],
            ['razao_social' => 'Indústria Lopes S.A.', 'nome' => null, 'tipo_pessoa' => 'pj', 'cnpj' => '98.765.432/0001-10', 'email' => 'energia@lopessa.com.br', 'celular' => '(11) 3654-8900', 'cidade_id' => $spId, 'status' => 'orcamento_gerado'],
            ['nome' => 'Carlos Eduardo Mendes', 'tipo_pessoa' => 'pf', 'cpf' => '321.654.987-00', 'email' => 'carlos.mendes@hotmail.com', 'celular' => '(31) 98512-3456', 'cidade_id' => $bhId, 'status' => 'novo'],
            ['nome' => 'Ana Lucia Oliveira', 'tipo_pessoa' => 'pf', 'cpf' => '456.789.123-00', 'email' => 'ana.oliveira@gmail.com', 'celular' => '(19) 99234-5678', 'cidade_id' => $camposId, 'status' => 'finalizado'],
        ];

        foreach ($clientesBase as $c) {
            Cliente::firstOrCreate(
                ['email' => $c['email']],
                array_merge($c, ['consultor_id' => $consultorId, 'anotacoes' => 'Cliente de teste'])
            );
        }
        $this->command->info('Clientes: OK');

        // ─── Leads de teste ───────────────────────────────────────────
        $leadsData = [
            ['nome' => 'Roberto Nunes Silva',    'email' => 'roberto.nunes@gmail.com',    'telefone' => '(11) 98765-0001', 'cidade' => 'São Paulo', 'estado' => 'SP', 'consumo_mensal' => 420,  'status' => 'novo'],
            ['nome' => 'Fabiana Costa Almeida',  'email' => 'fabiana.costa@hotmail.com',  'telefone' => '(19) 97654-0002', 'cidade' => 'Campinas',  'estado' => 'SP', 'consumo_mensal' => 680,  'status' => 'contatado'],
            ['nome' => 'Paulo Roberto Dias',     'email' => 'paulo.dias@empresa.com.br',  'telefone' => '(31) 98523-0003', 'cidade' => 'Belo Horizonte', 'estado' => 'MG', 'consumo_mensal' => 1200, 'status' => 'contatado'],
            ['nome' => 'Lúcia Fernandes',        'email' => 'lucia.fernandes@gmail.com',  'telefone' => '(11) 95432-0004', 'cidade' => 'São Paulo', 'estado' => 'SP', 'consumo_mensal' => 350,  'status' => 'novo'],
            ['nome' => 'Marcos Antônio Souza',   'email' => 'marcos.souza@outlook.com',   'telefone' => '(19) 98761-0005', 'cidade' => 'Campinas',  'estado' => 'SP', 'consumo_mensal' => 850,  'status' => 'contatado'],
        ];

        foreach ($leadsData as $l) {
            Lead::firstOrCreate(
                ['email' => $l['email']],
                array_merge($l, ['consultor_id' => $consultorId, 'origem' => 'manual'])
            );
        }
        $this->command->info('Leads: OK');

        // ─── Comissão do consultor ────────────────────────────────────
        User::where('tipo', 'consultor')->update(['comissao_percentual' => 5.0]);
        $this->command->info('Comissão consultor: 5% OK');
    }
}
