<?php

namespace Tests\Feature\Admin;

use App\Models\CategoriaProduto;
use App\Models\Kit;
use App\Models\Marca;
use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

class ProdutosTest extends TestCase
{
    use CriaDados, RefreshDatabase;

    // ── Kits ─────────────────────────────────────────────────────────────

    public function test_crud_de_kit(): void
    {
        $admin = $this->admin();
        $fornecedor = $this->fornecedor();
        $estrutura = $this->estrutura();
        $dados = [
            'fornecedor_id' => $fornecedor->id, 'estrutura_id' => $estrutura->id, 'nome' => 'Kit 8kWp',
            'potencia_kwp' => 8, 'tensao' => '220', 'preco_custo' => 30000, 'ativo' => true,
        ];

        $this->actingAs($admin)->post(route('admin.produtos.kits.store'), $dados)
            ->assertRedirect(route('admin.produtos.kits.index'));
        $kit = Kit::where('nome', 'Kit 8kWp')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.produtos.kits.show', $kit))->assertOk();
        $this->actingAs($admin)->get(route('admin.produtos.kits.edit', $kit))->assertOk();

        $this->actingAs($admin)->put(route('admin.produtos.kits.update', $kit), ['preco_custo' => 28000] + $dados)
            ->assertSessionHasNoErrors();
        $this->assertEquals(28000, (float) $kit->fresh()->preco_custo);

        $this->actingAs($admin)->delete(route('admin.produtos.kits.destroy', $kit));
        $this->assertNull(Kit::find($kit->id));
    }

    public function test_kit_exige_fornecedor_e_potencia_minima(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.produtos.kits.store'), ['nome' => 'Kit sem fornecedor', 'potencia_kwp' => 0])
            ->assertSessionHasErrors(['fornecedor_id', 'potencia_kwp']);
    }

    public function test_listagem_de_kits_filtra_por_potencia(): void
    {
        $this->kit(['nome' => 'Pequeno', 'potencia_kwp' => 3]);
        $this->kit(['nome' => 'Grande', 'potencia_kwp' => 20]);

        $this->actingAs($this->admin())
            ->get(route('admin.produtos.kits.index', ['potencia_min' => 10]))
            ->assertInertia(fn ($page) => $page->has('kits.data', 1)->where('kits.data.0.nome', 'Grande'));
    }

    // ── Catálogo ─────────────────────────────────────────────────────────

    public function test_crud_de_produto_do_catalogo(): void
    {
        $admin = $this->admin();
        $categoria = $this->categoria();

        $this->actingAs($admin)->post(route('admin.produtos.catalogo.store'), [
            'categoria_id' => $categoria->id, 'nome' => 'Painel 550W', 'sku' => 'PN-550', 'preco_custo' => 700,
        ])->assertSessionHasNoErrors();
        $produto = Produto::where('sku', 'PN-550')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.produtos.catalogo.show', $produto))->assertOk();
        $this->actingAs($admin)->put(route('admin.produtos.catalogo.update', $produto), [
            'categoria_id' => $categoria->id, 'nome' => 'Painel 550W', 'sku' => 'PN-550', 'preco_custo' => 650,
        ])->assertSessionHasNoErrors();
        $this->assertEquals(650, (float) $produto->fresh()->preco_custo);

        $this->actingAs($admin)->delete(route('admin.produtos.catalogo.destroy', $produto));
        $this->assertNull(Produto::find($produto->id));
    }

    public function test_sku_do_catalogo_e_unico(): void
    {
        $categoria = $this->categoria();
        $this->produto(['categoria_id' => $categoria->id, 'sku' => 'DUP-1']);

        $this->actingAs($this->admin())->post(route('admin.produtos.catalogo.store'), [
            'categoria_id' => $categoria->id, 'nome' => 'Outro', 'sku' => 'DUP-1', 'preco_custo' => 1,
        ])->assertSessionHasErrors('sku');
    }

    public function test_busca_no_catalogo_respeita_filtro_de_categoria(): void
    {
        $paineis = $this->categoria(['nome' => 'Painéis']);
        $cabos = $this->categoria(['nome' => 'Cabos']);
        $this->produto(['categoria_id' => $paineis->id, 'nome' => 'Solar X']);
        $this->produto(['categoria_id' => $cabos->id, 'nome' => 'Cabo', 'modelo' => 'Solar 6mm']);

        // Antes, o orWhere('modelo') sem agrupamento anulava o filtro de categoria.
        $this->actingAs($this->admin())
            ->get(route('admin.produtos.catalogo.index', ['search' => 'Solar', 'categoria_id' => $paineis->id]))
            ->assertInertia(fn ($page) => $page->has('produtos.data', 1)->where('produtos.data.0.nome', 'Solar X'));
    }

    // ── Categorias e Marcas ──────────────────────────────────────────────

    public function test_categoria_com_produtos_nao_pode_ser_removida(): void
    {
        $admin = $this->admin();
        $usada = $this->categoria();
        $this->produto(['categoria_id' => $usada->id]);
        $livre = $this->categoria();

        $this->actingAs($admin)->delete(route('admin.produtos.categorias.destroy', $usada))->assertSessionHas('error');
        $this->actingAs($admin)->delete(route('admin.produtos.categorias.destroy', $livre))->assertSessionHas('success');

        $this->assertNotNull($usada->fresh());
        $this->assertNull(CategoriaProduto::find($livre->id));
    }

    public function test_categoria_exige_slug_unico(): void
    {
        $this->categoria(['slug' => 'inversor']);

        $this->actingAs($this->admin())
            ->post(route('admin.produtos.categorias.store'), ['nome' => 'Inversor', 'slug' => 'inversor'])
            ->assertSessionHasErrors('slug');
    }

    public function test_crud_de_marca_e_bloqueio_com_produtos(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.produtos.marcas.store'), ['nome' => 'Canadian', 'ativo' => true])
            ->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.produtos.marcas.store'), ['nome' => 'Canadian'])
            ->assertSessionHasErrors('nome');

        $marca = Marca::where('nome', 'Canadian')->firstOrFail();
        $this->produto(['marca_id' => $marca->id]);

        $this->actingAs($admin)->delete(route('admin.produtos.marcas.destroy', $marca))->assertSessionHas('error');
        $this->assertNotNull($marca->fresh());
    }
}
