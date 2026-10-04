<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Orcamento;
use App\Models\OrcamentoHistorico;
use App\Models\User;
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
            ->with([
                'consultor:id,name',
                'cliente:id,nome,razao_social,tipo_pessoa',
                'cidade:id,cidade,estado',
            ])
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('id', 'like', "%{$s}%")
                ->orWhereHas('cliente', fn ($q) => $q
                    ->where('nome', 'like', "%{$s}%")
                    ->orWhere('razao_social', 'like', "%{$s}%"))))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->consultor_id, fn ($q, $id) => $q->where('consultor_id', $id))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Orcamentos/Index', [
            'orcamentos' => $orcamentos,
            'filters' => $request->only(['search', 'status', 'consultor_id']),
            'consultores' => User::where('tipo', '!=', 'admin')
                ->orderBy('name')
                ->get(['id', 'name']),
            'stats' => [
                'total' => Orcamento::count(),
                'novos' => Orcamento::where('status', 'novo')->count(),
                'aprovados' => Orcamento::where('status', 'aprovado')->count(),
                'instalando' => Orcamento::where('status', 'instalando')->count(),
                'valor_total' => (float) Orcamento::whereIn('status', ['aprovado', 'instalando', 'finalizado'])->sum('preco_total'),
            ],
        ]);
    }

    public function show(Orcamento $orcamento): Response
    {
        $orcamento->load([
            'consultor:id,name,email',
            'cliente',
            'cidade:id,cidade,estado',
            'info',
            'itens',
            'historicos.usuario:id,name',
            'aprovacao',
        ]);

        return Inertia::render('Admin/Orcamentos/Show', [
            'orcamento' => $orcamento,
            'transicoes' => $orcamento->transicoesPermitidas(),
        ]);
    }

    public function update(Request $request, Orcamento $orcamento): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|in:novo,aprovando,aprovado,aprovacao_reprovada,instalando,finalizado',
            'anotacoes' => 'nullable|string',
        ]);

        $statusAnterior = $orcamento->status;

        if ($data['status'] !== $statusAnterior && $orcamento->estaPerdido()) {
            return back()->withErrors(['status' => 'Orçamento marcado como perdido: reative-o no funil antes de mudar o status.']);
        }

        if ($data['status'] !== $statusAnterior && ! $orcamento->podeIrPara($data['status'])) {
            return back()->withErrors(['status' => "Não é possível mudar de \"{$statusAnterior}\" para \"{$data['status']}\"."]);
        }

        $orcamento->update($data);

        if ($data['status'] !== $statusAnterior) {
            OrcamentoHistorico::create([
                'orcamento_id' => $orcamento->id,
                'usuario_id' => Auth::id(),
                'status' => $data['status'],
                'mensagem' => "Status alterado pelo administrador: {$statusAnterior} → {$data['status']}.",
            ]);
        }

        return back()->with('success', 'Orçamento atualizado com sucesso.');
    }
}
