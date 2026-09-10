<?php

namespace App\Http\Controllers\Consultor;

use App\Http\Controllers\Controller;
use App\Models\Orcamento;
use App\Models\OrcamentoItem;
use App\Models\Produto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrcamentoItensController extends Controller
{
    public function store(Request $request, Orcamento $orcamento): RedirectResponse
    {
        abort_if($orcamento->consultor_id !== Auth::id(), 403);
        abort_if(!in_array($orcamento->status, ['novo']), 403, 'Orçamento não pode ser editado neste status.');

        $data = $request->validate([
            'tipo'        => 'required|in:produto,servico,personalizado',
            'produto_id'  => 'nullable|exists:produtos,id',
            'descricao'   => 'nullable|string|max:255',
            'quantidade'  => 'required|integer|min:1',
            'preco_venda_unitario' => 'required|numeric|min:0',
        ]);

        $produto = $data['produto_id'] ? Produto::find($data['produto_id']) : null;

        $precoCusto    = $produto ? (float) $produto->preco_custo * $data['quantidade'] : 0;
        $precoVendaUni = (float) $data['preco_venda_unitario'];
        $precoVendaTot = $precoVendaUni * $data['quantidade'];
        $margem        = $precoCusto > 0 ? (($precoVendaTot - $precoCusto) / $precoCusto) * 100 : 0;
        $proximaOrdem  = $orcamento->itens()->max('ordem') + 1;

        OrcamentoItem::create([
            'orcamento_id'         => $orcamento->id,
            'tipo'                 => $data['tipo'],
            'produto_id'           => $data['produto_id'],
            'descricao'            => $data['descricao'] ?? $produto?->nome,
            'quantidade'           => $data['quantidade'],
            'preco_custo_unitario' => $produto ? (float) $produto->preco_custo : 0,
            'preco_venda_unitario' => $precoVendaUni,
            'preco_venda_total'    => $precoVendaTot,
            'margem_percentual'    => round($margem, 3),
            'comissao_percentual'  => 0,
            'ordem'                => $proximaOrdem,
        ]);

        // Recalculate orcamento total
        $orcamento->preco_total = $orcamento->itens()->sum('preco_venda_total');
        $orcamento->save();

        return back()->with('success', 'Item adicionado ao orçamento.');
    }

    public function destroy(Orcamento $orcamento, OrcamentoItem $item): RedirectResponse
    {
        abort_if($orcamento->consultor_id !== Auth::id(), 403);
        abort_if($item->orcamento_id !== $orcamento->id, 403);
        abort_if($item->tipo === 'kit' && $orcamento->itens()->where('tipo', 'kit')->count() === 1, 422, 'Não é possível remover o kit principal do orçamento.');

        $item->delete();

        $orcamento->preco_total = $orcamento->itens()->sum('preco_venda_total');
        $orcamento->save();

        return back()->with('success', 'Item removido.');
    }

    // AJAX: busca produtos do catálogo para adicionar ao orçamento
    public function buscarProdutos(Request $request): JsonResponse
    {
        $q = $request->input('q', '');

        $produtos = Produto::with(['categoria:id,nome,slug', 'marca:id,nome'])
            ->where('ativo', true)
            ->where('ativo_fornecedor', true)
            ->where(fn ($query) => $query
                ->where('nome', 'like', "%{$q}%")
                ->orWhere('modelo', 'like', "%{$q}%")
                ->orWhere('sku', 'like', "%{$q}%"))
            ->when($request->categoria_slug, fn ($query, $slug) =>
                $query->whereHas('categoria', fn ($q) => $q->where('slug', $slug)))
            ->orderBy('nome')
            ->limit(20)
            ->get(['id', 'nome', 'modelo', 'sku', 'preco_custo', 'unidade', 'categoria_id', 'marca_id']);

        return response()->json($produtos);
    }
}
