<?php

namespace Tests\Feature\Consultor;

use App\Models\Concessionaria;
use App\Models\Kit;
use App\Models\MargemPrincipal;
use App\Models\Orcamento;
use App\Models\User;
use App\Services\PrecificacaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

/**
 * Fluxo completo de cada tipo de orçamento: calcular/buscar kits → escolher kit → salvar.
 * Verifica o que é persistido (orçamento, info, item kit, histórico) e que preço e
 * geração vêm do servidor.
 */
class FluxoOrcamentoPorGrupoTest extends TestCase
{
    use CriaDados, RefreshDatabase;

    private User $consultor;

    private array $base;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consultor = $this->consultor();
        $estrutura = $this->estrutura();
        $fornecedor = $this->fornecedor();
        MargemPrincipal::create(['nome' => 'Padrão', 'potencia_min' => 0, 'potencia_max' => null, 'margem' => 20, 'ordem' => 1]);

        // Grade de kits de 1 a ~130 kWp em passos de 4% (abaixo da menor tolerância de busca),
        // para qualquer potência calculada encontrar kit, nas tensões 220 e 380.
        for ($kwp = 1.0; $kwp < 130; $kwp *= 1.04) {
            foreach ([220, 380] as $tensao) {
                Kit::create([
                    'fornecedor_id' => $fornecedor->id, 'estrutura_id' => $estrutura->id,
                    'nome' => sprintf('Kit %.2f kWp %dV', $kwp, $tensao), 'potencia_kwp' => round($kwp, 3),
                    'tensao' => $tensao, 'preco_custo' => round($kwp * 3000, 2), 'ativo' => true, 'ativo_fornecedor' => true,
                ]);
            }
        }

        $this->base = [
            'cliente_id' => $this->cliente($this->consultor)->id, 'estrutura_id' => $estrutura->id,
            'qtd_kits' => 1, 'orientacao' => 'norte',
        ];
    }

    /** @return array<string, array{string, string, array<int, string>, array<string, mixed>, string}> */
    public static function grupos(): array
    {
        $b = ['tensao' => 220, 'fases' => 'bifasico', 'consumo' => 600, 'tarifa_kwh' => 0.95, 'objetivo_percentual' => 100];
        $a = [
            'tensao' => 380, 'modalidade_tarifaria' => 'THS_VERDE', 'consumo_ponta' => 300, 'consumo_fora_ponta' => 3000,
            'tarifa_kwh_ponta' => 2.2, 'tarifa_kwh_fp' => 0.55, 'demanda_ponta_kw' => 80, 'tarifa_demanda_ponta' => 35,
            'percentual_autoconsumo' => 70,
        ];

        return [
            'B1' => ['consultor.grupo.b1.calcular', 'consultor.grupo.b1.store', [], $b, 'B1'],
            'B2' => ['consultor.grupo.b2.calcular', 'consultor.grupo.b2.store', [],
                $b + ['tipo_instalacao' => 'irrigacao', 'possui_bombeamento' => true, 'consumo_bombeamento' => 300], 'B2'],
            'B3' => ['consultor.grupo.b3.calcular', 'consultor.grupo.b3.store', [],
                ['tensao' => 380] + $b + ['horario_funcionamento' => 10, 'percentual_autoconsumo' => 60], 'B3'],
            'A4' => ['consultor.grupo.a.calcular', 'consultor.grupo.a.store', ['A4'], $a + ['grupo_tarifario' => 'A4'], 'A4'],
            'Convencional' => ['consultor.dimensionamento.buscar_kits', 'consultor.dimensionamento.convencional.store', [],
                ['tensao' => 220, 'consumo' => 600, 'grupo_tarifario' => 'B2'], 'B2'],
        ];
    }

    #[DataProvider('grupos')]
    public function test_calcula_escolhe_kit_e_salva_orcamento(string $rotaCalculo, string $rotaStore, array $params, array $dados, string $grupoEsperado): void
    {
        $payload = $this->base + $dados;

        $calculo = $this->actingAs($this->consultor)
            ->postJson(route($rotaCalculo, $params), $payload)
            ->assertOk();
        $kits = $calculo->json('kits');
        $this->assertNotEmpty($kits, 'Nenhum kit retornado pelo cálculo');

        $kit = Kit::findOrFail($kits[0]['id']);
        $this->actingAs($this->consultor)
            ->post(route($rotaStore, $params), $payload + [
                'kit_id' => $kit->id, 'geracao_estimada' => 999999, 'preco_venda' => 1, // devem ser ignorados
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $orcamento = Orcamento::with(['info', 'itens', 'historicos'])->sole();
        $precoServidor = app(PrecificacaoService::class)->calcular($kit, 1, 'CE')['preco_venda'];

        $this->assertSame($grupoEsperado, $orcamento->grupo_tarifario);
        $this->assertSame('novo', $orcamento->status);
        $this->assertSame($this->consultor->id, $orcamento->consultor_id);
        $this->assertEquals($precoServidor, (float) $orcamento->preco_total);
        $this->assertNotEquals(999999, $orcamento->geracao_estimada);
        $this->assertSame($kits[0]['geracao'], $orcamento->geracao_estimada);
        $this->assertNotNull($orcamento->info);
        $this->assertCount(1, $orcamento->itens);
        $this->assertSame('kit', $orcamento->itens[0]->tipo);
        $this->assertCount(1, $orcamento->historicos);
    }

    public function test_fluxo_por_demanda(): void
    {
        $concessionaria = Concessionaria::create([
            'nome' => 'Enel CE', 'estado' => 'CE', 'tarifa_convencional' => 0.9,
            'tarifa_ponta' => 2.0, 'tarifa_fora_ponta' => 0.6, 'ativo' => true,
        ]);
        $payload = $this->base + ['tensao' => 220, 'consumo_ponta' => 100, 'consumo_fora_ponta' => 500, 'concessionaria_id' => $concessionaria->id, 'grupo_tarifario' => 'A3a'];

        $kits = $this->actingAs($this->consultor)
            ->postJson(route('consultor.dimensionamento.demanda.buscar_kits'), $payload)
            ->assertOk()
            ->json('kits');
        $this->assertNotEmpty($kits);

        $this->actingAs($this->consultor)
            ->post(route('consultor.dimensionamento.demanda.store'), $payload + ['kit_id' => $kits[0]['id'], 'geracao_estimada' => 999999])
            ->assertSessionHasNoErrors();

        $orcamento = Orcamento::with('info')->sole();
        $this->assertSame($kits[0]['geracao'], $orcamento->geracao_estimada);
        $this->assertSame('demanda', $orcamento->info->tipo_dimensionamento);
        $this->assertSame('A3a', $orcamento->grupo_tarifario);
        $this->assertSame($concessionaria->id, $orcamento->info->concessionaria_id);
        $this->assertEquals(600, (float) $orcamento->info->consumo);
        $this->assertSame('Enel CE', $orcamento->info->metadados['concessionaria']);
        $this->assertArrayHasKey('pr', $orcamento->info->metadados);
        $this->assertArrayHasKey('hsp', $orcamento->info->metadados);
    }

    /** @return array<string, array{string}> */
    public static function etapasGrupoA(): array
    {
        return ['calcular' => ['consultor.grupo.a.calcular'], 'salvar' => ['consultor.grupo.a.store']];
    }

    #[DataProvider('etapasGrupoA')]
    public function test_ths_azul_exige_demanda_e_tarifa_fora_de_ponta_em_todas_as_etapas(string $rota): void
    {
        $payload = $this->base + [
            'tensao' => 380, 'grupo_tarifario' => 'A4', 'modalidade_tarifaria' => 'THS_AZUL',
            'consumo_ponta' => 300, 'consumo_fora_ponta' => 3000, 'tarifa_kwh_ponta' => 2.2, 'tarifa_kwh_fp' => 0.55,
            'demanda_ponta_kw' => 80, 'tarifa_demanda_ponta' => 35, 'percentual_autoconsumo' => 70,
            'kit_id' => Kit::first()->id,
        ];

        $this->actingAs($this->consultor)
            ->postJson(route($rota, ['A4']), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['demanda_fora_ponta_kw', 'tarifa_demanda_fp'])
            ->assertJsonPath('errors.demanda_fora_ponta_kw.0', 'THS Azul exige a demanda fora de ponta.');

        $this->assertSame(0, Orcamento::count());
    }

    public function test_calculo_nao_exige_kit_e_salvar_nao_aceita_sem_kit(): void
    {
        $payload = $this->base + ['tensao' => 220, 'fases' => 'bifasico', 'consumo' => 600, 'tarifa_kwh' => 0.95, 'objetivo_percentual' => 100];

        $this->actingAs($this->consultor)->postJson(route('consultor.grupo.b1.calcular'), $payload)
            ->assertOk();
        $this->actingAs($this->consultor)->postJson(route('consultor.grupo.b1.store'), $payload)
            ->assertJsonValidationErrors('kit_id');
    }

    public function test_fluxos_legados_exigem_grupo_tarifario_compativel(): void
    {
        $kit = Kit::first();

        $this->actingAs($this->consultor)
            ->post(route('consultor.dimensionamento.convencional.store'), $this->base + [
                'tensao' => 220, 'consumo' => 600, 'kit_id' => $kit->id, 'grupo_tarifario' => 'A4',
            ])->assertSessionHasErrors('grupo_tarifario');

        $this->actingAs($this->consultor)
            ->post(route('consultor.dimensionamento.demanda.store'), $this->base + [
                'tensao' => 220, 'consumo_ponta' => 100, 'consumo_fora_ponta' => 500, 'kit_id' => $kit->id,
            ])->assertSessionHasErrors('grupo_tarifario');

        $this->assertSame(0, Orcamento::count());
    }

    public function test_calculo_sem_kit_compativel_retorna_404_com_mensagem(): void
    {
        Kit::query()->delete();

        $this->actingAs($this->consultor)
            ->postJson(route('consultor.grupo.b1.calcular'), $this->base + [
                'tensao' => 220, 'fases' => 'bifasico', 'consumo' => 600, 'tarifa_kwh' => 0.95, 'objetivo_percentual' => 100,
            ])
            ->assertNotFound()
            ->assertJsonStructure(['error']);
    }

    public function test_cliente_sem_cidade_nao_pode_ser_dimensionado(): void
    {
        $semCidade = $this->cliente($this->consultor, ['cidade_id' => null]);

        $this->actingAs($this->consultor)
            ->postJson(route('consultor.grupo.b1.calcular'), ['cliente_id' => $semCidade->id] + $this->base + [
                'tensao' => 220, 'fases' => 'bifasico', 'consumo' => 600, 'tarifa_kwh' => 0.95, 'objetivo_percentual' => 100,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('error', 'Cliente sem cidade cadastrada.');
    }

    /** @return array<string, array{array<string, bool>}> */
    public static function kitsIndisponiveis(): array
    {
        return [
            'desativado' => [['ativo' => false]],
            'indisponível no fornecedor' => [['ativo_fornecedor' => false]],
        ];
    }

    #[DataProvider('kitsIndisponiveis')]
    public function test_salvar_recusa_kit_que_a_busca_nao_oferece(array $estado): void
    {
        $kit = Kit::first();
        $kit->update($estado);

        $this->actingAs($this->consultor)
            ->post(route('consultor.grupo.b1.store'), $this->base + [
                'tensao' => 220, 'fases' => 'bifasico', 'consumo' => 600, 'tarifa_kwh' => 0.95,
                'objetivo_percentual' => 100, 'kit_id' => $kit->id,
            ])
            ->assertSessionHasErrors('kit_id');

        $this->assertSame(0, Orcamento::count());
    }
}
