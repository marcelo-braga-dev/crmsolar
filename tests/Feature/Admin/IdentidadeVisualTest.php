<?php

namespace Tests\Feature\Admin;

use App\Providers\AppServiceProvider;
use App\Services\IdentidadeVisual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

/** Admin → Configurações → Identidade visual: nome, textos, logos, favicon e cores. */
class IdentidadeVisualTest extends TestCase
{
    use CriaDados, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function dados(array $extra = []): array
    {
        return $extra + [
            'nome' => 'Sol & Cia Energia', 'rodape' => 'Feito para integradores',
            'cor_primaria' => '#0f766e', 'cor_secundaria' => '#EA580C', 'menu_fundo' => '#FFFFFF', 'menu_fonte' => '#1E293B',
        ];
    }

    public function test_sem_configuracao_todas_as_paginas_recebem_o_padrao(): void
    {
        $this->get(route('login'))->assertInertia(fn ($page) => $page
            ->where('identidade.nome', IdentidadeVisual::PADRAO['nome'])
            ->where('identidade.cor_primaria', IdentidadeVisual::PADRAO['cor_primaria'])
            ->where('identidade.logo_url', null)
            ->missing('identidade.slogan')); // removido: o menu mostra a função do usuário
    }

    public function test_sem_a_tabela_de_configuracoes_usa_o_padrao_sem_quebrar(): void
    {
        Schema::drop('configs');

        $this->assertSame(IdentidadeVisual::PADRAO['nome'], IdentidadeVisual::atual()['nome']);
        $this->assertNull(IdentidadeVisual::atual()['favicon_url']);
        $this->get(route('login'))->assertOk();
    }

    public function test_admin_altera_nome_textos_e_cores_e_tudo_passa_a_valer(): void
    {
        $this->actingAs($this->admin())->post(route('admin.configuracoes.identidade.update'), $this->dados())
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        // Login (visitante), páginas internas (outro perfil), título da view raiz e e-mails.
        auth()->logout();
        $this->get(route('login'))->assertInertia(fn ($page) => $page
            ->where('identidade.nome', 'Sol & Cia Energia')
            ->where('identidade.rodape', 'Feito para integradores')
            ->where('identidade.cor_primaria', '#0F766E') // normalizada em maiúsculas
            ->where('identidade.menu_fundo', '#FFFFFF'));
        $this->get(route('login'))->assertSee('<title inertia>Sol &amp; Cia Energia</title>', false)
            ->assertSee('<meta name="theme-color" content="#FFFFFF">', false);
        $this->actingAs($this->consultor())->get(route('consultor.dashboard'))
            ->assertInertia(fn ($page) => $page->where('identidade.menu_fonte', '#1E293B'));

        // A cada requisição o AppServiceProvider aplica o nome a config('app.name') (e-mails).
        app()->getProvider(AppServiceProvider::class)->boot();
        $this->assertSame('Sol & Cia Energia', config('app.name'));
    }

    public function test_alteracao_vai_para_a_auditoria_so_com_o_que_mudou(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.configuracoes.identidade.update'), $this->dados(['menu_fundo' => '#0F172A', 'menu_fonte' => '#CBD5E1']));

        $registro = Activity::where('description', 'Identidade visual alterada')->sole();
        $this->assertSame($admin->id, $registro->causer_id);
        $this->assertSame('Sol & Cia Energia', $registro->attribute_changes['attributes']['nome']);
        $this->assertSame('CRM Solar', $registro->attribute_changes['old']['nome']);
        $this->assertArrayNotHasKey('menu_fundo', $registro->attribute_changes['attributes']); // não mudou

        $this->actingAs($admin)->get(route('admin.configuracoes.auditoria'))
            ->assertInertia(fn ($page) => $page->where('atividades.data.0.tipo', 'Identidade visual alterada'));
    }

    public function test_envio_troca_e_remocao_de_logo_logo_clara_e_favicon(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.configuracoes.identidade.update'), $this->dados([
            'logo' => UploadedFile::fake()->image('logo.png', 400, 120),
            'logo_clara' => UploadedFile::fake()->image('logo-clara.webp', 400, 120),
            'favicon' => UploadedFile::fake()->image('favicon.png', 64, 64),
        ]))->assertSessionHasNoErrors();

        $logo = IdentidadeVisual::arquivo('logo');
        Storage::disk('public')->assertExists([$logo, IdentidadeVisual::arquivo('logo_clara'), IdentidadeVisual::arquivo('favicon')]);
        $this->assertStringStartsWith(IdentidadeVisual::PASTA.'/', $logo);
        auth()->logout();
        $this->get(route('login'))
            ->assertInertia(fn ($page) => $page->where('identidade.logo_url', fn ($url) => str_contains($url, $logo)))
            ->assertSee('rel="icon" href="'.IdentidadeVisual::atual()['favicon_url'].'"', false);
        $this->assertSame(Storage::disk('public')->path(IdentidadeVisual::arquivo('logo_clara')), IdentidadeVisual::logoParaPdf());

        // Salvar de novo sem arquivos mantém as imagens.
        $this->actingAs($admin)->post(route('admin.configuracoes.identidade.update'), $this->dados());
        $this->assertSame($logo, IdentidadeVisual::arquivo('logo'));

        // Trocar apaga a anterior; remover apaga e limpa.
        $this->actingAs($admin)->post(route('admin.configuracoes.identidade.update'), $this->dados(['logo' => UploadedFile::fake()->image('nova.png', 300, 90)]));
        Storage::disk('public')->assertMissing($logo);
        $this->actingAs($admin)->post(route('admin.configuracoes.identidade.update'), $this->dados(['remover_logo' => true, 'remover_favicon' => true]));
        $this->assertNull(IdentidadeVisual::arquivo('logo'));
        $this->assertNull(IdentidadeVisual::atual()['favicon_url']);
        $this->assertCount(1, Storage::disk('public')->allFiles(IdentidadeVisual::PASTA)); // só a logo clara
    }

    public function test_validacao_de_cores_textos_e_arquivos(): void
    {
        $admin = $this->admin();
        $enviar = fn (array $extra) => $this->actingAs($admin)->post(route('admin.configuracoes.identidade.update'), $this->dados($extra));

        $enviar(['cor_primaria' => 'azul', 'cor_secundaria' => '#FFF', 'nome' => ''])->assertSessionHasErrors(['cor_primaria', 'cor_secundaria', 'nome']);
        $enviar(['nome' => str_repeat('x', 41)])->assertSessionHasErrors('nome');
        // SVG pode conter script: recusado. PDF e imagem grande demais também.
        $enviar(['logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml')])->assertSessionHasErrors('logo');
        $enviar(['logo_clara' => UploadedFile::fake()->create('logo.pdf', 10, 'application/pdf')])->assertSessionHasErrors('logo_clara');
        $enviar(['logo' => UploadedFile::fake()->image('enorme.png', 2500, 300)])->assertSessionHasErrors('logo');
        $enviar(['favicon' => UploadedFile::fake()->image('favicon.png', 64, 64)->size(600)])->assertSessionHasErrors('favicon');

        $this->assertSame(IdentidadeVisual::PADRAO['nome'], IdentidadeVisual::nome());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_menu_com_fonte_ilegivel_sobre_o_fundo_e_recusado(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.configuracoes.identidade.update'), $this->dados(['menu_fundo' => '#1E293B', 'menu_fonte' => '#334155']))
            ->assertSessionHasErrors('menu_fonte');

        $this->assertSame(IdentidadeVisual::PADRAO['menu_fundo'], IdentidadeVisual::atual()['menu_fundo']);
        $this->assertSame(21.0, IdentidadeVisual::contraste('#000000', '#FFFFFF'));
        $this->assertSame(1.0, IdentidadeVisual::contraste('#2563EB', '#2563EB'));
    }

    public function test_restaurar_padrao_apaga_imagens_e_volta_cores(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.configuracoes.identidade.update'), $this->dados(['logo' => UploadedFile::fake()->image('logo.png', 300, 90)]));

        $this->actingAs($admin)->delete(route('admin.configuracoes.identidade.restaurar'))->assertSessionHas('success');

        $this->assertSame(array_merge(IdentidadeVisual::PADRAO, ['logo_url' => null, 'logo_clara_url' => null, 'favicon_url' => null]), IdentidadeVisual::atual());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_so_admin_altera(): void
    {
        $this->post(route('admin.configuracoes.identidade.update'), $this->dados())->assertRedirect(route('login'));
        $this->actingAs($this->consultor())->post(route('admin.configuracoes.identidade.update'), $this->dados())->assertForbidden();
        $this->actingAs($this->consultor())->delete(route('admin.configuracoes.identidade.restaurar'))->assertForbidden();

        $this->assertSame(IdentidadeVisual::PADRAO['nome'], IdentidadeVisual::nome());
    }

    public function test_pdf_usa_a_logo_da_identidade(): void
    {
        $this->actingAs($this->admin())->post(route('admin.configuracoes.identidade.update'), $this->dados(['logo_clara' => UploadedFile::fake()->image('logo.png', 300, 90)]));

        $html = view('pdf.orcamento', ['orcamento' => $this->orcamentoParaPdf()])->render();

        $this->assertStringContainsString('<img src="'.IdentidadeVisual::logoParaPdf().'"', $html);
    }

    private function orcamentoParaPdf()
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamento($consultor);
        $this->itemKit($orcamento);

        return $orcamento->load(['cliente.cidade', 'consultor', 'itens', 'info', 'cidade']);
    }
}
