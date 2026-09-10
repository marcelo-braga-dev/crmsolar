<?php

namespace App\Http\Controllers\Consultor;

use App\Http\Controllers\Controller;
use App\Models\Orcamento;
use App\Models\OrcamentoHistorico;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class OrcamentosController extends Controller
{
    public function index(Request $request): Response
    {
        $orcamentos = Orcamento::query()
            ->where('consultor_id', Auth::id())
            ->with([
                'cliente:id,tipo_pessoa,nome,razao_social',
                'cidade:id,cidade,estado',
            ])
            ->when($request->search, fn ($q, $s) =>
                $q->where(fn ($q) => $q
                    ->where('id', 'like', "%{$s}%")
                    ->orWhereHas('cliente', fn ($q) => $q
                        ->where('nome', 'like', "%{$s}%")
                        ->orWhere('razao_social', 'like', "%{$s}%"))))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Consultor/Orcamentos/Index', [
            'orcamentos' => $orcamentos,
            'filters'    => $request->only(['search', 'status']),
            'stats'      => [
                'total'       => Orcamento::where('consultor_id', Auth::id())->count(),
                'novos'       => Orcamento::where('consultor_id', Auth::id())->whereIn('status', ['novo', 'aprovando'])->count(),
                'aprovados'   => Orcamento::where('consultor_id', Auth::id())->where('status', 'aprovado')->count(),
                'valor_total' => (float) Orcamento::where('consultor_id', Auth::id())
                    ->whereIn('status', ['aprovado', 'instalando', 'finalizado'])
                    ->sum('preco_total'),
            ],
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('consultor.dimensionamento.convencional');
    }

    public function store(): RedirectResponse
    {
        return redirect()->route('consultor.dimensionamento.convencional');
    }

    public function show(Orcamento $orcamento): Response
    {
        abort_if($orcamento->consultor_id !== Auth::id(), 403);

        $orcamento->load([
            'cliente',
            'cidade:id,cidade,estado',
            'info.estrutura:id,nome',
            'itens',
            'historicos.usuario:id,name',
        ]);

        return Inertia::render('Consultor/Orcamentos/Show', [
            'orcamento' => $orcamento,
        ]);
    }

    public function edit(Orcamento $orcamento): Response
    {
        abort_if($orcamento->consultor_id !== Auth::id(), 403);
        abort_if($orcamento->info?->bloquear_edicao, 403, 'Orçamento bloqueado para edição.');

        $orcamento->load(['cliente', 'cidade', 'info.estrutura:id,nome', 'itens']);

        return Inertia::render('Consultor/Orcamentos/Edit', [
            'orcamento' => $orcamento,
        ]);
    }

    public function update(Request $request, Orcamento $orcamento): RedirectResponse
    {
        abort_if($orcamento->consultor_id !== Auth::id(), 403);

        $data = $request->validate([
            'status'             => 'nullable|in:aprovando',
            'anotacoes'          => 'nullable|string|max:3000',
            'anotacoes_tecnicas' => 'nullable|string|max:3000',
        ]);

        $statusAnterior = $orcamento->status;

        $orcamento->update([
            'status'    => $data['status'] ?? $orcamento->status,
            'anotacoes' => $data['anotacoes'] ?? null,
        ]);

        if (isset($data['anotacoes_tecnicas']) && $orcamento->info) {
            $orcamento->info->update(['anotacoes_tecnicas' => $data['anotacoes_tecnicas']]);
        }

        if (isset($data['status']) && $data['status'] !== $statusAnterior) {
            OrcamentoHistorico::create([
                'orcamento_id' => $orcamento->id,
                'usuario_id'   => Auth::id(),
                'status'       => $data['status'],
                'mensagem'     => 'Orçamento enviado para aprovação.',
            ]);
        }

        return redirect()
            ->route('consultor.orcamentos.show', $orcamento)
            ->with('success', 'Orçamento atualizado com sucesso.');
    }

    public function destroy(Orcamento $orcamento): RedirectResponse
    {
        abort_if($orcamento->consultor_id !== Auth::id(), 403);
        abort_if($orcamento->status !== 'novo', 403, 'Só é possível excluir orçamentos com status Novo.');

        $orcamento->delete();

        return redirect()->route('consultor.orcamentos.index')->with('success', 'Orçamento excluído.');
    }
}
