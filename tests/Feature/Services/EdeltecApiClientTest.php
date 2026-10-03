<?php

namespace Tests\Feature\Services;

use App\Services\Integracoes\Edeltec\EdeltecApiClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Cliente da API Edeltec com a API simulada (Http::fake) — nenhuma chamada real.
 */
class EdeltecApiClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.edeltec.url' => 'https://api.edeltec.teste',
            'services.edeltec.api_key' => 'chave',
            'services.edeltec.secret' => 'segredo',
        ]);
        Cache::flush();
    }

    public function test_instancia_com_credenciais_da_configuracao(): void
    {
        // Antes: propriedades readonly promovidas eram reatribuídas no construtor
        // ("Cannot modify readonly property") e a integração sempre falhava.
        Http::fake(['api.edeltec.teste/api-access/token' => Http::response(['token' => 'tok-123'])]);

        $this->assertSame('tok-123', (new EdeltecApiClient)->token());

        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.edeltec.teste/api-access/token'
            && $r['apiKey'] === 'chave' && $r['secret'] === 'segredo');
    }

    public function test_credenciais_passadas_no_construtor_tem_prioridade(): void
    {
        Http::fake(['outra.api/api-access/token' => Http::response(['access_token' => 'tok-x'])]);

        $this->assertSame('tok-x', (new EdeltecApiClient('https://outra.api', 'k2', 's2'))->token());

        Http::assertSent(fn (Request $r) => $r['apiKey'] === 'k2');
    }

    public function test_token_fica_em_cache(): void
    {
        Http::fake(['*/api-access/token' => Http::response(['token' => 'tok-1'])]);
        $cliente = new EdeltecApiClient;

        $cliente->token();
        $cliente->token();

        Http::assertSentCount(1);
    }

    public function test_falha_de_autenticacao_lanca_erro_claro(): void
    {
        Http::fake(['*/api-access/token' => Http::response([], 403)]);

        $this->expectExceptionMessage('acesso negado');
        (new EdeltecApiClient)->token();
    }

    public function test_produtos_percorre_todas_as_paginas_e_ignora_itens_sem_codigo(): void
    {
        Http::fake([
            '*/api-access/token' => Http::response(['token' => 'tok']),
            '*/produtos/integration*' => Http::sequence()
                ->push(['items' => [['codProd' => 'A1'], ['codProd' => '']], 'meta' => ['totalPages' => 2, 'totalItems' => 3]])
                ->push(['items' => [['codProd' => 'B2']], 'meta' => ['totalPages' => 2, 'totalItems' => 3]]),
        ]);

        $codigos = collect((new EdeltecApiClient)->produtos())->pluck('codProd')->all();

        $this->assertSame(['A1', 'B2'], $codigos);
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'page=2') && $r->hasHeader('Authorization', 'Bearer tok'));
    }

    public function test_token_expirado_no_meio_da_paginacao_e_renovado(): void
    {
        Http::fake([
            '*/api-access/token' => Http::sequence()->push(['token' => 'velho'])->push(['token' => 'novo']),
            '*/produtos/integration*' => Http::sequence()
                ->push([], 401)
                ->push(['items' => [['codProd' => 'C3']], 'meta' => ['totalPages' => 1]]),
        ]);

        $codigos = collect((new EdeltecApiClient)->produtos())->pluck('codProd')->all();

        $this->assertSame(['C3'], $codigos);
        Http::assertSent(fn (Request $r) => $r->hasHeader('Authorization', 'Bearer novo'));
    }
}
