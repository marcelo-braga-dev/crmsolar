<?php

namespace Tests\Feature\Admin;

use App\Models\Concessionaria;
use App\Models\Kit;
use App\Models\MargemPrincipal;
use App\Models\OrcamentoItem;
use App\Models\ParamDimensionamento;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

/**
 * Auditoria de preço, margem, status e comissão + tela de consulta.
 */
class AuditoriaTest extends TestCase
{
    use CriaDados, RefreshDatabase;

    private function ultimoLog(string $classe, int $id): ?Activity
    {
        return Activity::where('subject_type', $classe)->where('subject_id', $id)->latest('id')->first();
    }

    public function test_mudanca_de_margem_registra_quem_alterou_e_valores(): void
    {
        $admin = $this->admin();
        $faixa = MargemPrincipal::create(['nome' => 'Padrão', 'potencia_min' => 0, 'potencia_max' => null, 'margem' => 20, 'ordem' => 1]);

        $this->actingAs($admin)->put(route('admin.precificacao.faixas.update', $faixa), [
            'nome' => 'Padrão', 'potencia_min' => 0, 'potencia_max' => null, 'margem' => 25, 'ordem' => 1,
        ]);

        $log = $this->ultimoLog(MargemPrincipal::class, $faixa->id);
        $this->assertSame('updated', $log->event);
        $this->assertSame($admin->id, $log->causer_id);
        $this->assertEquals(20, $log->attribute_changes['old']['margem']);
        $this->assertEquals(25, $log->attribute_changes['attributes']['margem']);
        $this->assertArrayNotHasKey('nome', $log->attribute_changes['attributes']); // só o que mudou
    }

    public function test_preco_de_kit_e_produto_e_auditado_mas_criacao_nao(): void
    {
        $kit = $this->kit(['preco_custo' => 20000]);
        $produto = $this->produto(['preco_custo' => 100]);
        $this->assertNull($this->ultimoLog(Kit::class, $kit->id));
        $this->assertNull($this->ultimoLog(Produto::class, $produto->id));

        $kit->update(['preco_custo' => 21000]);
        $produto->update(['preco_custo' => 110]);

        $this->assertEquals(21000, $this->ultimoLog(Kit::class, $kit->id)->attribute_changes['attributes']['preco_custo']);
        $this->assertEquals(110, $this->ultimoLog(Produto::class, $produto->id)->attribute_changes['attributes']['preco_custo']);
    }

    public function test_campo_irrelevante_do_kit_nao_gera_log(): void
    {
        // Recarregado do banco, como numa requisição real (o model recém-criado não tem os defaults da coluna).
        $kit = $this->kit()->fresh();

        $kit->update(['observacoes' => 'só uma nota']);

        $this->assertNull($this->ultimoLog(Kit::class, $kit->id));
    }

    public function test_comissao_do_consultor_e_auditada_e_senha_nunca(): void
    {
        $consultor = $this->consultor(['comissao_percentual' => 5]);

        $this->actingAs($this->admin())->put(route('admin.usuarios.consultores.update', $consultor), [
            'name' => $consultor->name, 'email' => $consultor->email, 'comissao_percentual' => 8, 'status' => true,
            'password' => 'nova-senha-123', 'password_confirmation' => 'nova-senha-123',
        ])->assertSessionHasNoErrors();

        $log = $this->ultimoLog(User::class, $consultor->id);
        $this->assertEquals(8, $log->attribute_changes['attributes']['comissao_percentual']);
        $this->assertStringNotContainsString('password', json_encode($log->attribute_changes));

        $antes = Activity::count();
        $consultor->fresh()->update(['password' => 'outra-senha-456']);
        $this->assertSame($antes, Activity::count(), 'troca só de senha não deve gerar log');
    }

    public function test_parametro_de_dimensionamento_e_auditado(): void
    {
        $param = ParamDimensionamento::create(['chave' => 'fator_perda_sistema', 'nome' => 'Perda', 'valor' => '20']);

        $this->actingAs($this->admin())->put(route('admin.configuracoes.dimensionamento.update'), [
            'params' => [['id' => $param->id, 'valor' => '18']],
        ]);

        $log = $this->ultimoLog(ParamDimensionamento::class, $param->id);
        $this->assertSame('updated', $log?->event, 'update via query builder não dispararia o log');
        $this->assertSame('18', $log->attribute_changes['attributes']['valor']);
    }

    public function test_tarifa_de_concessionaria_e_itens_de_orcamento_sao_auditados(): void
    {
        $conc = Concessionaria::create(['nome' => 'Enel', 'estado' => 'CE', 'tarifa_convencional' => 0.9, 'tarifa_ponta' => 2, 'tarifa_fora_ponta' => 0.6, 'ativo' => true]);
        $conc->update(['tarifa_convencional' => 0.95]);
        $this->assertSame('updated', $this->ultimoLog(Concessionaria::class, $conc->id)->event);

        $item = $this->itemKit($this->orcamento($this->consultor()));
        $this->assertSame('created', $this->ultimoLog(OrcamentoItem::class, $item->id)->event);
    }

    public function test_tela_lista_e_filtra_registros(): void
    {
        $admin = $this->admin(['name' => 'Ana Admin']);
        $this->actingAs($admin);
        $faixa = MargemPrincipal::create(['nome' => 'Padrão', 'potencia_min' => 0, 'margem' => 20, 'ordem' => 1]);
        $faixa->update(['margem' => 22]);
        $this->kit()->update(['preco_custo' => 1]);

        $this->get(route('admin.configuracoes.auditoria', ['tipo' => 'margem_principal']))
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Configuracoes/Auditoria/Index')
                ->has('atividades.data', 2)
                ->where('atividades.data.0.evento', 'updated')
                ->where('atividades.data.0.tipo', 'Margem por potência')
                ->where('atividades.data.0.usuario', 'Ana Admin')
                ->where('atividades.data.0.alteracoes.0.campo', 'margem')
                ->etc());

        $this->get(route('admin.configuracoes.auditoria', ['evento' => 'created']))
            ->assertInertia(fn ($page) => $page->where('atividades.data', fn ($d) => collect($d)->every(fn ($a) => $a['evento'] === 'created'))->etc());

        $this->get(route('admin.configuracoes.auditoria', ['usuario_id' => $this->consultor()->id]))
            ->assertInertia(fn ($page) => $page->has('atividades.data', 0)->etc());
    }
}
