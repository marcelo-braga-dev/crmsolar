<?php

namespace Tests\Concerns;

use App\Models\CategoriaProduto;
use App\Models\CidadeEstado;
use App\Models\Cliente;
use App\Models\Estrutura;
use App\Models\Fornecedor;
use App\Models\Kit;
use App\Models\Lead;
use App\Models\Orcamento;
use App\Models\OrcamentoItem;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Construtores de dados para testes de feature (o projeto só tem UserFactory).
 * Todos aceitam overrides; o resto vem com valores válidos mínimos.
 */
trait CriaDados
{
    protected function admin(array $attrs = []): User
    {
        return User::create($attrs + [
            'name' => 'Admin '.Str::random(5), 'email' => Str::random(8).'@admin.teste',
            'password' => 'senha-forte-123', 'tipo' => 'admin', 'status' => true, 'comissao_percentual' => 0,
        ]);
    }

    protected function consultor(array $attrs = []): User
    {
        return User::create($attrs + [
            'name' => 'Consultor '.Str::random(5), 'email' => Str::random(8).'@consultor.teste',
            'password' => 'senha-forte-123', 'tipo' => 'consultor', 'status' => true, 'comissao_percentual' => 5,
        ]);
    }

    protected function cidade(array $attrs = []): CidadeEstado
    {
        return CidadeEstado::firstOrCreate(
            ['cidade' => $attrs['cidade'] ?? 'Fortaleza', 'sigla' => $attrs['sigla'] ?? 'CE'],
            ['estado' => $attrs['estado'] ?? 'Ceará'],
        );
    }

    protected function cliente(User $consultor, array $attrs = []): Cliente
    {
        return Cliente::create($attrs + [
            'consultor_id' => $consultor->id, 'cidade_id' => $this->cidade()->id,
            'tipo_pessoa' => 'pf', 'nome' => 'Cliente '.Str::random(5), 'status' => 'novo',
        ]);
    }

    protected function orcamento(User $consultor, array $attrs = []): Orcamento
    {
        $cliente = isset($attrs['cliente_id']) ? Cliente::find($attrs['cliente_id']) : $this->cliente($consultor);

        return Orcamento::create($attrs + [
            'consultor_id' => $consultor->id, 'cliente_id' => $cliente->id, 'cidade_id' => $cliente->cidade_id,
            'status' => 'novo', 'grupo_tarifario' => 'B1', 'preco_total' => 10000, 'geracao_estimada' => 500,
        ]);
    }

    protected function itemKit(Orcamento $orcamento, array $attrs = []): OrcamentoItem
    {
        return OrcamentoItem::create($attrs + [
            'orcamento_id' => $orcamento->id, 'tipo' => 'kit', 'descricao' => 'Kit 5kWp', 'quantidade' => 1,
            'preco_custo_unitario' => 8000, 'preco_venda_unitario' => 10000, 'preco_venda_total' => 10000,
            'comissao_percentual' => 5, 'metadados' => ['potencia_kwp' => 5.0], 'ordem' => 1,
        ]);
    }

    protected function lead(?User $consultor = null, array $attrs = []): Lead
    {
        return Lead::create($attrs + [
            'consultor_id' => $consultor?->id, 'nome' => 'Lead '.Str::random(5),
            'email' => Str::random(6).'@lead.teste', 'telefone' => '11999990000', 'status' => 'novo',
        ]);
    }

    protected function fornecedor(array $attrs = []): Fornecedor
    {
        return Fornecedor::create($attrs + ['nome' => 'Fornecedor '.Str::random(5), 'ativo' => true]);
    }

    protected function estrutura(array $attrs = []): Estrutura
    {
        return Estrutura::create($attrs + ['nome' => 'Estrutura '.Str::random(5), 'ativo' => true]);
    }

    protected function kit(array $attrs = []): Kit
    {
        return Kit::create($attrs + [
            'fornecedor_id' => $attrs['fornecedor_id'] ?? $this->fornecedor()->id,
            'estrutura_id' => $attrs['estrutura_id'] ?? $this->estrutura()->id,
            'nome' => 'Kit '.Str::random(5), 'potencia_kwp' => 5, 'tensao' => 220,
            'preco_custo' => 20000, 'ativo' => true, 'ativo_fornecedor' => true,
        ]);
    }

    protected function categoria(array $attrs = []): CategoriaProduto
    {
        return CategoriaProduto::create($attrs + ['nome' => 'Categoria', 'slug' => 'cat-'.Str::lower(Str::random(6)), 'ativo' => true]);
    }

    protected function produto(array $attrs = []): Produto
    {
        return Produto::create($attrs + [
            'categoria_id' => $attrs['categoria_id'] ?? $this->categoria()->id,
            'nome' => 'Produto '.Str::random(5), 'preco_custo' => 100, 'ativo' => true, 'ativo_fornecedor' => true,
        ]);
    }
}
