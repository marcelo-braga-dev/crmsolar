<?php

namespace Tests\Feature\Admin;

use App\Models\CategoriaProduto;
use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

/**
 * Inversores, Painéis e Transformadores: CRUD do catálogo restrito à categoria.
 */
class ProdutosPorCategoriaTest extends TestCase
{
    use CriaDados, RefreshDatabase;

    /** @return array<string, array{string, string, string, string}> [rota, slug, componente, prop do form] */
    public static function telas(): array
    {
        return [
            'inversores' => ['inversores', 'inversor-solar', 'Admin/Produtos/Inversores', 'inversor'],
            'paineis' => ['paineis', 'painel-solar', 'Admin/Produtos/Paineis', 'painel'],
            'trafos' => ['trafos', 'transformador', 'Admin/Produtos/Trafos', 'trafo'],
        ];
    }

    #[DataProvider('telas')]
    public function test_crud_completo_na_categoria_existente(string $rota, string $slug, string $componente, string $prop): void
    {
        // Categoria já criada pelo CategoriasProdutosSeeder — não pode ser duplicada.
        $categoria = $this->categoria(['slug' => $slug]);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route("admin.produtos.{$rota}.store"), [
            'nome' => 'Produto Teste', 'potencia' => 5, 'tensao' => 220, 'preco_custo' => 1500, 'garantia' => 10, 'ativo' => true,
        ])->assertRedirect(route("admin.produtos.{$rota}.index"));

        $produto = Produto::where('nome', 'Produto Teste')->sole();
        $this->assertSame($categoria->id, $produto->categoria_id);
        $this->assertSame(1, CategoriaProduto::where('slug', $slug)->count());

        $this->actingAs($admin)->get(route("admin.produtos.{$rota}.index"))
            ->assertInertia(fn ($page) => $page->component("{$componente}/Index")->has("{$rota}.data", 1)->where('stats.total', 1));
        $this->actingAs($admin)->get(route("admin.produtos.{$rota}.edit", $produto))
            ->assertInertia(fn ($page) => $page->component("{$componente}/Form")->where("{$prop}.id", $produto->id));

        $this->actingAs($admin)->put(route("admin.produtos.{$rota}.update", $produto), [
            'nome' => 'Produto Editado', 'preco_custo' => 1400, 'ativo' => true,
        ])->assertSessionHasNoErrors();
        $this->assertSame('Produto Editado', $produto->fresh()->nome);

        $this->actingAs($admin)->delete(route("admin.produtos.{$rota}.destroy", $produto));
        $this->assertNull(Produto::find($produto->id));
    }

    #[DataProvider('telas')]
    public function test_listagem_mostra_so_a_propria_categoria(string $rota, string $slug): void
    {
        $this->produto(['categoria_id' => $this->categoria(['slug' => $slug])->id, 'nome' => 'Da categoria']);
        $this->produto(['nome' => 'De outra categoria']);

        $this->actingAs($this->admin())->get(route("admin.produtos.{$rota}.index"))
            ->assertInertia(fn ($page) => $page->has("{$rota}.data", 1)->where("{$rota}.data.0.nome", 'Da categoria'));
    }

    #[DataProvider('telas')]
    public function test_nao_edita_nem_exclui_produto_de_outra_categoria(string $rota, string $slug): void
    {
        $this->categoria(['slug' => $slug]);
        $outro = $this->produto();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route("admin.produtos.{$rota}.edit", $outro))->assertNotFound();
        $this->actingAs($admin)->put(route("admin.produtos.{$rota}.update", $outro), ['nome' => 'X'])->assertNotFound();
        $this->actingAs($admin)->delete(route("admin.produtos.{$rota}.destroy", $outro))->assertNotFound();

        $this->assertNotNull($outro->fresh());
    }

    #[DataProvider('telas')]
    public function test_tensao_deve_ser_numerica(string $rota): void
    {
        $this->actingAs($this->admin())
            ->post(route("admin.produtos.{$rota}.store"), ['nome' => 'X', 'tensao' => '220V / 380V'])
            ->assertSessionHasErrors('tensao');
    }
}
