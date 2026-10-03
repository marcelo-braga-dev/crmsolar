<?php

namespace Tests\Feature\Admin;

use App\Models\IntegracaoHistorico;
use App\Models\Kit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

/**
 * Sincronização Edeltec de ponta a ponta (botão "Integrar" → API simulada → kits no banco).
 */
class IntegracaoEdeltecTest extends TestCase
{
    use CriaDados, RefreshDatabase;

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

    /** @param  array<int, array<string, mixed>>  $itens */
    private function apiComProdutos(array $itens): void
    {
        // Stubs do Http::fake se acumulam (o primeiro que casa vence): recomeça do zero a cada cenário.
        Http::swap(new Factory);
        Http::fake([
            '*/api-access/token' => Http::response(['token' => 'tok']),
            '*/produtos/integration*' => Http::response(['items' => $itens, 'meta' => ['totalPages' => 1, 'totalItems' => count($itens)]]),
        ]);
    }

    private function produtoApi(string $sku, string $potencia = '5.5', string $preco = '12000.00'): array
    {
        return [
            'codProd' => $sku, 'titulo' => "Kit {$sku} edeltec", 'potenciaGerador' => $potencia,
            'precoDoIntegrador' => $preco, 'tensaoSaida' => '220V', 'tipo' => 'GERADOR FOTOVOLTAICO',
        ];
    }

    private function integrar()
    {
        return $this->actingAs($this->admin())->post(route('admin.integracoes.edeltec.integrar'));
    }

    public function test_importa_kits_e_registra_historico(): void
    {
        $edeltec = $this->fornecedor(['nome' => 'Edeltec']);
        $this->apiComProdutos([$this->produtoApi('ED-1'), $this->produtoApi('ED-2', '8000', 'R$ 18.500,00')]);

        $this->integrar()->assertSessionHas('success');

        $kits = Kit::where('fornecedor_id', $edeltec->id)->orderBy('sku')->get();
        $this->assertCount(2, $kits);
        $this->assertSame('Kit ED-1', $kits[0]->nome); // sufixo " edeltec" removido
        $this->assertEquals(8.0, (float) $kits[1]->potencia_kwp); // 8000 W → 8 kWp
        $this->assertEquals(18500, (float) $kits[1]->preco_custo);
        $this->assertNotNull($kits[0]->created_at);

        $historico = IntegracaoHistorico::sole();
        $this->assertSame('concluido', $historico->status);
        $this->assertSame(2, $historico->itens_importados);
    }

    public function test_segunda_sincronizacao_atualiza_em_vez_de_duplicar(): void
    {
        $edeltec = $this->fornecedor(['nome' => 'Edeltec']);
        $this->apiComProdutos([$this->produtoApi('ED-1', '5.5', '12000')]);
        $this->integrar();

        $this->apiComProdutos([$this->produtoApi('ED-1', '5.5', '11000')]);
        $this->integrar()->assertSessionHas('success');

        $this->assertSame(1, Kit::where('fornecedor_id', $edeltec->id)->count());
        $this->assertEquals(11000, (float) Kit::sole()->preco_custo);
        $this->assertSame(1, IntegracaoHistorico::latest('id')->first()->itens_atualizados);
    }

    public function test_kit_que_sumiu_do_catalogo_e_desativado(): void
    {
        $edeltec = $this->fornecedor(['nome' => 'Edeltec']);
        $this->apiComProdutos([$this->produtoApi('ED-1'), $this->produtoApi('ED-2')]);
        $this->integrar();

        $this->apiComProdutos([$this->produtoApi('ED-1')]);
        $this->integrar();

        $this->assertFalse((bool) Kit::where('sku', 'ED-2')->value('ativo_fornecedor'));
        $this->assertTrue((bool) Kit::where('sku', 'ED-1')->value('ativo_fornecedor'));
        $this->assertSame(1, IntegracaoHistorico::latest('id')->first()->itens_desativados);
    }

    public function test_mesmo_sku_de_outro_fornecedor_nao_e_sobrescrito(): void
    {
        $outro = $this->fornecedor(['nome' => 'Outro Distribuidor']);
        $this->kit(['fornecedor_id' => $outro->id, 'sku' => 'ED-1', 'preco_custo' => 999]);
        $this->fornecedor(['nome' => 'Edeltec']);
        $this->apiComProdutos([$this->produtoApi('ED-1', '5.5', '12000')]);

        $this->integrar()->assertSessionHas('success');

        $this->assertEquals(999, (float) Kit::where('fornecedor_id', $outro->id)->value('preco_custo'));
        $this->assertSame(2, Kit::where('sku', 'ED-1')->count());
    }

    public function test_produto_com_potencia_invalida_e_ignorado_com_alerta(): void
    {
        $this->fornecedor(['nome' => 'Edeltec']);
        $this->apiComProdutos([$this->produtoApi('ED-1'), $this->produtoApi('ED-X', '0')]);

        $this->integrar()->assertSessionHas('warning');

        $this->assertSame(['ED-1'], Kit::pluck('sku')->all());
        $this->assertStringContainsString('ED-X', IntegracaoHistorico::sole()->alertas);
    }

    public function test_falha_na_api_registra_erro_no_historico(): void
    {
        $this->fornecedor(['nome' => 'Edeltec']);
        Http::fake(['*/api-access/token' => Http::response([], 403)]);

        $this->integrar()->assertSessionHas('error');

        $this->assertSame('erro', IntegracaoHistorico::sole()->status);
    }
}
