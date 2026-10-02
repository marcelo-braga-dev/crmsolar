<?php

namespace Tests\Feature\Api;

use App\Models\CidadeEstado;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiPublicaTest extends TestCase
{
    use RefreshDatabase;

    // ── Captura de lead ──────────────────────────────────────────────────

    public function test_lead_externo_e_criado_como_novo_e_sem_consultor(): void
    {
        $this->postJson(route('api.leads.store'), [
            'nome' => 'Fulano', 'email' => 'fulano@site.com', 'telefone' => '85999990000',
            'cidade' => 'Fortaleza', 'estado' => 'CE', 'consumo_mensal' => 450, 'origem' => 'landing-page',
            'dados_extras' => ['utm_source' => 'google'],
        ])->assertCreated()->assertJsonStructure(['message', 'id']);

        $lead = Lead::sole();
        $this->assertSame('novo', $lead->status);
        $this->assertNull($lead->consultor_id);
        $this->assertSame('landing-page', $lead->origem);
    }

    public function test_lead_sem_origem_recebe_origem_api(): void
    {
        $this->postJson(route('api.leads.store'), ['nome' => 'X', 'email' => 'x@site.com', 'telefone' => '1'])->assertCreated();

        $this->assertSame('api', Lead::value('origem'));
    }

    public function test_lead_ignora_campos_internos_enviados_pelo_formulario(): void
    {
        $this->postJson(route('api.leads.store'), [
            'nome' => 'X', 'email' => 'x@site.com', 'telefone' => '1', 'status' => 'convertido', 'consultor_id' => 1,
        ])->assertCreated();

        $this->assertSame('novo', Lead::value('status'));
        $this->assertNull(Lead::value('consultor_id'));
    }

    public function test_lead_valida_campos_obrigatorios_e_formatos(): void
    {
        $this->postJson(route('api.leads.store'), ['email' => 'invalido', 'estado' => 'Ceará', 'consumo_mensal' => -1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nome', 'email', 'telefone', 'estado', 'consumo_mensal']);
    }

    // ── Geografia ────────────────────────────────────────────────────────

    private function cidades(): void
    {
        CidadeEstado::create(['cidade' => 'Fortaleza', 'estado' => 'Ceará', 'sigla' => 'CE']);
        CidadeEstado::create(['cidade' => 'Caucaia', 'estado' => 'Ceará', 'sigla' => 'CE']);
        CidadeEstado::create(['cidade' => 'São Paulo', 'estado' => 'São Paulo', 'sigla' => 'SP']);
    }

    public function test_lista_estados_distintos(): void
    {
        $this->cidades();

        $this->getJson(route('api.estados'))->assertOk()->assertJsonCount(2);
    }

    public function test_lista_cidades_por_sigla_ou_nome_do_estado(): void
    {
        $this->cidades();

        $this->getJson(route('api.cidades', 'ce'))->assertOk()->assertJsonCount(2)->assertJsonPath('0.cidade', 'Caucaia');
        $this->getJson(route('api.cidades', 'São Paulo'))->assertOk()->assertJsonCount(1);
    }

    public function test_cep_valido_retorna_endereco_e_cidade_do_banco(): void
    {
        $this->cidades();
        Http::fake(['viacep.com.br/*' => Http::response([
            'cep' => '60000-000', 'logradouro' => 'Rua A', 'bairro' => 'Centro', 'localidade' => 'Fortaleza', 'uf' => 'CE',
        ])]);

        $this->getJson(route('api.cep', '60000-000'))
            ->assertOk()
            ->assertJson(['cep' => '60000000', 'logradouro' => 'Rua A', 'bairro' => 'Centro', 'cidade' => 'Fortaleza', 'sigla' => 'CE', 'estado' => 'Ceará'])
            ->assertJsonPath('cidade_id', CidadeEstado::where('cidade', 'Fortaleza')->value('id'));
    }

    public function test_cep_e_cacheado(): void
    {
        Http::fake(['viacep.com.br/*' => Http::response(['localidade' => 'Fortaleza', 'uf' => 'CE'])]);

        $this->getJson(route('api.cep', '60000000'))->assertOk();
        $this->getJson(route('api.cep', '60000000'))->assertOk();

        Http::assertSentCount(1);
    }

    public function test_cep_com_formato_invalido_retorna_422_sem_consultar_viacep(): void
    {
        Http::fake();

        $this->getJson(route('api.cep', '123'))->assertUnprocessable();

        Http::assertNothingSent();
    }

    public function test_cep_inexistente_retorna_404(): void
    {
        Http::fake(['viacep.com.br/*' => Http::response(['erro' => true])]);

        $this->getJson(route('api.cep', '99999999'))->assertNotFound();
    }

    public function test_falha_no_viacep_retorna_404_em_vez_de_500(): void
    {
        Http::fake(['viacep.com.br/*' => fn () => throw new ConnectionException('timeout')]);

        $this->getJson(route('api.cep', '60000000'))->assertNotFound();
    }
}
