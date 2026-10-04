<?php

namespace Tests\Concerns;

use App\Models\Contrato;
use App\Models\FunilEtapa;
use App\Models\MotivoPerda;
use App\Models\Orcamento;
use App\Services\Funil\FunilService;

/**
 * Apoio dos testes do funil. As etapas e motivos padrão vêm da própria migration
 * (2026_10_04_000000_create_funil_de_vendas), então existem em todo teste com RefreshDatabase.
 */
trait CriaFunil
{
    protected function etapaAberta(int $posicao = 1): FunilEtapa
    {
        return FunilEtapa::where('tipo', FunilEtapa::ABERTA)->orderBy('ordem')->skip($posicao - 1)->firstOrFail();
    }

    protected function etapaSistema(string $tipo): FunilEtapa
    {
        return FunilEtapa::where('tipo', $tipo)->firstOrFail();
    }

    protected function motivo(bool $reativavel = true): MotivoPerda
    {
        return MotivoPerda::where('reativavel', $reativavel)->orderBy('ordem')->firstOrFail();
    }

    /** Coluna em que o card aparece para a requisição ("caixa" ou id da etapa). */
    protected function origemDe(Orcamento $orcamento): string
    {
        return (string) (app(FunilService::class)->etapaDe($orcamento->fresh())->id ?? 'caixa');
    }

    protected function contratoPara(Orcamento $orcamento, string $status = 'gerado'): Contrato
    {
        return Contrato::create([
            'orcamento_id' => $orcamento->id, 'consultor_id' => $orcamento->consultor_id,
            'nome_cliente' => 'Cliente', 'documento_cliente' => '000.000.000-00', 'endereco_instalacao' => 'Rua A, 1',
            'potencia_kwp' => 5, 'qtd_paineis' => 10, 'qtd_inversores' => 1, 'modelo_inversor' => 'Inversor X',
            'consumo_mensal' => 500, 'geracao_estimada' => 650, 'garantia_paineis' => '25 anos',
            'garantia_inversores' => '10 anos', 'valor_total' => 10000, 'formas_pagamento' => 'À vista',
            'produtos_snapshot' => [], 'status' => $status,
        ]);
    }
}
