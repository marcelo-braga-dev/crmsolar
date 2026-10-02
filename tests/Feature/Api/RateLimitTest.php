<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_captura_de_lead_e_bloqueada_apos_5_envios_por_minuto(): void
    {
        $payload = ['nome' => 'Fulano', 'email' => 'fulano@teste.com', 'telefone' => '11999999999'];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('api.leads.store'), $payload)->assertCreated();
        }

        $this->postJson(route('api.leads.store'), $payload)->assertStatus(429);
    }

    public function test_busca_de_cep_e_bloqueada_apos_60_consultas_por_minuto(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->getJson(route('api.estados'));
        }

        $this->getJson(route('api.estados'))->assertStatus(429);
    }

    public function test_visualizacao_publica_de_orcamento_e_bloqueada_apos_30_consultas_por_minuto(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->getJson(route('api.orcamento.public', 'token-inexistente-'.$i));
        }

        $this->getJson(route('api.orcamento.public', 'token-inexistente-final'))->assertStatus(429);
    }
}
