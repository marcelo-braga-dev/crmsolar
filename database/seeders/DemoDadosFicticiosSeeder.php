<?php

namespace Database\Seeders;

use App\Models\Config;
use App\Models\Fornecedor;
use App\Services\Integracoes\NomeDistribuidora;
use Database\Seeders\Support\CatalogoDemo;
use Database\Seeders\Support\Ficticio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Deixa explícito que nada na base é real (DEMO.md), e pode rodar de novo sem duplicar nada:
 * - nomes de pessoas e empresas com "(fictício)/(fictícia)" — inclusive a equipe;
 * - CPF/CNPJ/RG começando com zeros e derivados do id; telefones com DDD 20 e prefixo 0000;
 * - e-mails no domínio da demo; forma de pagamento com recebedor EMPRESA FICTICIA;
 * - textos livres que copiaram nomes (contratos, linha do tempo, anotações, Auditoria)
 *   atualizados pelo mapa nome antigo → nome marcado;
 * - aviso "documento de demonstração com dados fictícios" nos PDFs (configuração demo_aviso).
 *
 * Usa o query builder de propósito: a marcação não deve aparecer como edição na Auditoria.
 */
class DemoDadosFicticiosSeeder extends Seeder
{
    /** Nome genérico do fornecedor da integração (o nome real da distribuidora é confidencial). */
    public const DISTRIBUIDORA = 'Distribuidora Parceira';

    /** @var array<string, string> nome antigo => nome marcado */
    private array $mapa = [];

    /** Expressão montada uma vez por execução, depois que o mapa está completo. */
    private ?string $regex = null;

    public function run(): void
    {
        DB::transaction(function () {
            $this->usuarios();
            $this->clientes();
            $this->leads();
            $this->fornecedores();
            $this->contratos();
            $this->textosLivres();
            $this->configuracoes();
        });
    }

    private function usuarios(): void
    {
        $generos = collect(CatalogoDemo::EQUIPE)->mapWithKeys(fn ($m) => [$m['login'].'@'.CatalogoDemo::DOMINIO_EQUIPE => $m['genero'] === 'f']);
        foreach (DB::table('users')->get() as $u) {
            $nome = Ficticio::marcar($u->name, $generos[$u->email] ?? Ficticio::nomeFeminino($u->name));
            $this->lembrar($u->name, $nome);
            DB::table('users')->where('id', $u->id)->update([
                'name' => $nome,
                'cpf' => Ficticio::cpf(900 + $u->id),
                'celular' => Ficticio::telefone(900 + $u->id),
            ]);
        }
    }

    private function clientes(): void
    {
        foreach (DB::table('clientes')->get() as $c) {
            $pf = $c->tipo_pessoa === 'pf';
            $nome = $pf ? Ficticio::marcar($c->nome, Ficticio::nomeFeminino((string) $c->nome)) : $c->nome;
            $razao = $pf ? $c->razao_social : Ficticio::marcar($c->razao_social, true);
            $this->lembrar($c->nome, $nome);
            $this->lembrar($c->razao_social, $razao);

            DB::table('clientes')->where('id', $c->id)->update([
                'nome' => $nome,
                'razao_social' => $razao,
                'cpf' => $pf ? Ficticio::cpf($c->id) : null,
                'cnpj' => $pf ? null : Ficticio::cnpj($c->id),
                'rg' => $pf ? Ficticio::rg($c->id) : null,
                'email' => Ficticio::email((string) ($razao ?? $nome), $c->id, CatalogoDemo::DOMINIO_CLIENTES),
                'telefone' => $c->telefone ? Ficticio::telefone($c->id, 1) : null,
                'celular' => Ficticio::telefone($c->id),
            ]);
        }
    }

    private function leads(): void
    {
        foreach (DB::table('leads')->get() as $l) {
            $nome = Ficticio::marcar($l->nome, Ficticio::nomeFeminino((string) $l->nome));
            $this->lembrar($l->nome, $nome);
            DB::table('leads')->where('id', $l->id)->update([
                'nome' => $nome,
                'email' => Ficticio::email((string) $nome, 5000 + $l->id, CatalogoDemo::DOMINIO_CLIENTES),
                'telefone' => Ficticio::telefone(5000 + $l->id),
            ]);
        }
    }

    private function fornecedores(): void
    {
        // CNPJ é único: limpa antes de regravar, para um valor antigo nunca colidir com o novo de outra linha.
        $fornecedores = DB::table('fornecedores')->get();
        DB::table('fornecedores')->update(['cnpj' => null]);

        foreach ($fornecedores as $f) {
            // Nome da distribuidora integrada é confidencial: vira um nome genérico (a integração a acha pela coluna `integracao`).
            $integrada = $f->integracao === Fornecedor::INTEGRACAO_DISTRIBUIDORA || stripos($f->nome, NomeDistribuidora::REAL) !== false;
            $nome = Ficticio::marcar($integrada ? self::DISTRIBUIDORA : $f->nome, true);
            $this->lembrar($f->nome, $nome);
            DB::table('fornecedores')->where('id', $f->id)->update([
                'nome' => $nome,
                ...($integrada ? [
                    'integracao' => Fornecedor::INTEGRACAO_DISTRIBUIDORA,
                    'email' => 'comercial@parceira.fornecedor.demo',
                    'site' => 'https://parceira.fornecedor.demo',
                ] : []),
                'cnpj' => Ficticio::cnpjFornecedor($f->id),
                'telefone' => Ficticio::telefone(800 + $f->id),
                'celular' => null,
                'representante' => $f->representante ? Ficticio::marcar($f->representante, Ficticio::nomeFeminino($f->representante)) : null,
            ]);

            if ($integrada) {
                DB::table('kits')->where('fornecedor_id', $f->id)->where('sku', 'like', 'EDL-%')
                    ->update(['sku' => DB::raw("replace(sku, 'EDL-', 'DPA-')")]);
            }
        }

        DB::table('integracao_historicos')->where('alertas', 'like', '%edeltec%')
            ->update(['alertas' => DB::raw("replace(alertas, 'api.edeltec', 'API da distribuidora')")]);
    }

    /** Contrato e assinatura copiam nome e documento do cliente: realinha com o cliente marcado. */
    private function contratos(): void
    {
        $contratos = DB::table('contratos')
            ->join('orcamentos', 'orcamentos.id', '=', 'contratos.orcamento_id')
            ->join('clientes', 'clientes.id', '=', 'orcamentos.cliente_id')
            ->get(['contratos.id', 'contratos.orcamento_id', 'contratos.formas_pagamento', 'clientes.tipo_pessoa', 'clientes.nome', 'clientes.razao_social', 'clientes.cpf', 'clientes.cnpj']);

        foreach ($contratos as $c) {
            $nome = $c->tipo_pessoa === 'pj' ? $c->razao_social : $c->nome;
            $documento = $c->tipo_pessoa === 'pj' ? $c->cnpj : $c->cpf;
            $pagamento = str_contains((string) $c->formas_pagamento, Ficticio::RECEBEDOR) || str_starts_with((string) $c->formas_pagamento, 'Financiamento')
                ? $c->formas_pagamento
                : trim($c->formas_pagamento.' Recebedor: '.Ficticio::RECEBEDOR.'.');

            DB::table('contratos')->where('id', $c->id)->update(['nome_cliente' => $nome, 'documento_cliente' => $documento, 'formas_pagamento' => $pagamento]);
            DB::table('orcamento_aprovacoes')->where('orcamento_id', $c->orcamento_id)->update(['nome_assinante' => $nome, 'documento_assinante' => $documento]);
        }
    }

    /** Troca os nomes antigos pelos marcados em todo texto livre que os copiou. */
    private function textosLivres(): void
    {
        if ($this->mapa === []) {
            return;
        }

        $colunas = [
            'orcamentos' => ['anotacoes', 'perda_observacao'], 'orcamento_historicos' => ['mensagem'], 'leads' => ['anotacoes'],
            'clientes' => ['anotacoes'], 'visitas_tecnicas' => ['anotacoes'], 'proposta_servicos' => ['conteudo', 'descricao', 'observacoes'],
            'contratos' => ['clausulas_adicionais'], 'orcamento_infos' => ['anotacoes_tecnicas'],
        ];
        foreach ($colunas as $tabela => $campos) {
            foreach (DB::table($tabela)->get(array_merge(['id'], $campos)) as $linha) {
                $novos = [];
                foreach ($campos as $campo) {
                    if ($linha->{$campo} !== null && ($trocado = $this->substituir($linha->{$campo})) !== $linha->{$campo}) {
                        $novos[$campo] = $trocado;
                    }
                }
                if ($novos) {
                    DB::table($tabela)->where('id', $linha->id)->update($novos);
                }
            }
        }

        // Auditoria guarda os atributos em JSON (com acentos escapados): percorre a estrutura.
        foreach (DB::table('activity_log')->get(['id', 'properties', 'attribute_changes']) as $linha) {
            $novos = [];
            foreach (['properties', 'attribute_changes'] as $campo) {
                if ($linha->{$campo} === null || ($dados = json_decode($linha->{$campo}, true)) === null) {
                    continue;
                }
                $trocado = $this->substituirEmTudo($dados);
                if ($trocado !== $dados) {
                    $novos[$campo] = json_encode($trocado, JSON_UNESCAPED_UNICODE);
                }
            }
            if ($novos) {
                DB::table('activity_log')->where('id', $linha->id)->update($novos);
            }
        }
    }

    private function configuracoes(): void
    {
        $empresa = Config::get('empresa_nome');
        if ($empresa && ! str_contains($empresa, 'fictícia')) {
            Config::set('empresa_nome', "{$empresa} (empresa fictícia)", 'empresa');
        }
        Config::set('demo_aviso', 'Documento de demonstração com dados fictícios', 'geral');
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    private function lembrar(?string $antigo, ?string $novo): void
    {
        if ($antigo && $novo && $antigo !== $novo) {
            $this->mapa[$antigo] = $novo;
        }
    }

    /**
     * Uma passada por texto com todos os nomes (os mais longos primeiro). Não troca o que já
     * está marcado nem um nome que é só o começo de outro ("Lucas Ferreira" em "Lucas Ferreira Santos").
     */
    private function substituir(string $texto): string
    {
        $this->regex ??= '/(?<![\p{L}])('.implode('|', array_map(fn ($n) => preg_quote($n, '/'), $this->ordenarPorTamanho())).')(?![\p{L}]| \p{Lu}| \(fict)/u';

        return preg_replace_callback($this->regex, fn ($m) => $this->mapa[$m[1]], $texto) ?? $texto;
    }

    /** @return array<int, string> */
    private function ordenarPorTamanho(): array
    {
        $nomes = array_keys($this->mapa);
        usort($nomes, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        return $nomes;
    }

    private function substituirEmTudo(mixed $valor): mixed
    {
        if (is_array($valor)) {
            return array_map(fn ($v) => $this->substituirEmTudo($v), $valor);
        }

        return is_string($valor) ? $this->substituir($valor) : $valor;
    }
}
