<?php

namespace Tests\Feature\Admin;

use App\Models\Marca;
use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

/**
 * Catálogo como página única: produtos (com atalhos por categoria), categorias e marcas.
 * Substitui as antigas páginas Painéis, Inversores, Transformadores, Categorias e Marcas.
 */
class CatalogoUnificadoTest extends TestCase
{
    use CriaDados, RefreshDatabase;

    public function test_pagina_entrega_as_tres_abas_com_contagens(): void
    {
        $paineis = $this->categoria(['nome' => 'Painel Solar', 'slug' => 'painel-solar', 'ordem' => 1]);
        $inativa = $this->categoria(['nome' => 'Antiga', 'ativo' => false, 'ordem' => 2]);
        $marca = Marca::create(['nome' => 'Canadian', 'ativo' => true]);
        $this->produto(['categoria_id' => $paineis->id, 'marca_id' => $marca->id]);
        $this->produto(['categoria_id' => $paineis->id]);

        $this->actingAs($this->admin())->get(route('admin.produtos.catalogo.index'))
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Produtos/Catalogo/Index')
                ->where('aba', 'produtos')
                ->where('totalProdutos', 2)
                ->has('produtos.data', 2)
                // A aba Categorias gerencia todas, inclusive inativas
                ->has('categorias', 2)
                ->where('categorias.0.slug', 'painel-solar')
                ->where('categorias.0.produtos_count', 2)
                ->where('categorias.1.id', $inativa->id)
                ->has('marcas', 1)
                ->where('marcas.0.produtos_count', 1));
    }

    /** @return array<string, array{mixed, string}> */
    public static function abas(): array
    {
        return [
            'categorias' => ['categorias', 'categorias'],
            'marcas' => ['marcas', 'marcas'],
            'inválida cai em produtos' => ['xpto', 'produtos'],
            'sem aba' => [null, 'produtos'],
        ];
    }

    #[DataProvider('abas')]
    public function test_aba_escolhida_pela_url(?string $aba, string $esperada): void
    {
        $this->actingAs($this->admin())->get(route('admin.produtos.catalogo.index', array_filter(['aba' => $aba])))
            ->assertInertia(fn ($page) => $page->where('aba', $esperada));
    }

    public function test_atalho_de_categoria_filtra_produtos_pelo_slug(): void
    {
        $inversores = $this->categoria(['slug' => 'inversor-solar']);
        $this->produto(['categoria_id' => $inversores->id, 'nome' => 'Inversor 5kW']);
        $this->produto(['nome' => 'Cabo 6mm']);

        $this->actingAs($this->admin())->get(route('admin.produtos.catalogo.index', ['categoria' => 'inversor-solar']))
            ->assertInertia(fn ($page) => $page
                ->has('produtos.data', 1)
                ->where('produtos.data.0.nome', 'Inversor 5kW')
                ->where('totalProdutos', 2)
                ->where('filters.categoria', 'inversor-solar'));
    }

    public function test_novo_produto_a_partir_do_atalho_ja_vem_com_a_categoria(): void
    {
        $trafos = $this->categoria(['slug' => 'transformador']);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.produtos.catalogo.create', ['categoria' => 'transformador']))
            ->assertInertia(fn ($page) => $page->component('Admin/Produtos/Catalogo/Form')->where('categoriaInicial', $trafos->id));
        $this->actingAs($admin)->get(route('admin.produtos.catalogo.create'))
            ->assertInertia(fn ($page) => $page->where('categoriaInicial', null));
    }

    /** @return array<string, array{string, string}> */
    public static function enderecosAntigos(): array
    {
        return [
            'painéis' => ['/admin/produtos/paineis', '/admin/produtos/catalogo?categoria=painel-solar'],
            'inversores' => ['/admin/produtos/inversores', '/admin/produtos/catalogo?categoria=inversor-solar'],
            'transformadores' => ['/admin/produtos/trafos', '/admin/produtos/catalogo?categoria=transformador'],
            'novo painel' => ['/admin/produtos/paineis/create', '/admin/produtos/catalogo/create?categoria=painel-solar'],
            'novo inversor' => ['/admin/produtos/inversores/create', '/admin/produtos/catalogo/create?categoria=inversor-solar'],
            'novo trafo' => ['/admin/produtos/trafos/create', '/admin/produtos/catalogo/create?categoria=transformador'],
            'categorias' => ['/admin/produtos/categorias', '/admin/produtos/catalogo?aba=categorias'],
            'marcas' => ['/admin/produtos/marcas', '/admin/produtos/catalogo?aba=marcas'],
        ];
    }

    #[DataProvider('enderecosAntigos')]
    public function test_endereco_antigo_redireciona_para_o_catalogo(string $antigo, string $novo): void
    {
        $this->actingAs($this->admin())->get($antigo)->assertRedirect(url($novo));
    }

    public function test_enderecos_antigos_continuam_exclusivos_do_admin(): void
    {
        $this->actingAs($this->consultor())->get('/admin/produtos/paineis')->assertForbidden();
    }

    public function test_produto_exige_categoria_e_preco_de_custo(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.produtos.catalogo.store'), ['nome' => 'Sem categoria'])
            ->assertSessionHasErrors(['categoria_id', 'preco_custo']);

        $this->assertSame(0, Produto::count());
    }

    public function test_acoes_de_categoria_e_marca_voltam_para_a_aba_de_origem(): void
    {
        $origem = route('admin.produtos.catalogo.index', ['aba' => 'marcas']);

        $this->actingAs($this->admin())->from($origem)
            ->post(route('admin.produtos.marcas.store'), ['nome' => 'WEG', 'ativo' => true])
            ->assertRedirect($origem);
    }
}
