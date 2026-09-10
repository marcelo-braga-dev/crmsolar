<?php

namespace App\Http\Controllers\Consultor;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\PropostaServico;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PropostasServicosController extends Controller
{
    private function clientes(): \Illuminate\Database\Eloquent\Collection
    {
        return Cliente::where('consultor_id', Auth::id())
            ->orderBy('nome')
            ->get(['id', 'tipo_pessoa', 'nome', 'razao_social']);
    }

    public function index(Request $request): Response
    {
        $propostas = PropostaServico::query()
            ->where('consultor_id', Auth::id())
            ->with('cliente:id,tipo_pessoa,nome,razao_social')
            ->when($request->search, fn ($q, $s) =>
                $q->where(fn ($q) => $q
                    ->where('titulo', 'like', "%{$s}%")
                    ->orWhereHas('cliente', fn ($q) => $q
                        ->where('nome', 'like', "%{$s}%")
                        ->orWhere('razao_social', 'like', "%{$s}%"))))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Consultor/PropostasServicos/Index', [
            'propostas' => $propostas,
            'filters'   => $request->only(['search', 'status']),
            'stats'     => [
                'total'    => PropostaServico::where('consultor_id', Auth::id())->count(),
                'enviadas' => PropostaServico::where('consultor_id', Auth::id())->where('status', 'enviada')->count(),
                'aceitas'  => PropostaServico::where('consultor_id', Auth::id())->where('status', 'aceita')->count(),
                'valor_aceito' => (float) PropostaServico::where('consultor_id', Auth::id())
                    ->where('status', 'aceita')->sum('valor'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Consultor/PropostasServicos/Form', [
            'clientes' => $this->clientes(),
            'proposta' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cliente_id'   => 'required|exists:clientes,id',
            'titulo'       => 'required|string|max:255',
            'valor'        => 'required|numeric|min:0',
            'validade'     => 'required|date|after_or_equal:today',
            'conteudo'     => 'required|string|min:10',
            'observacoes'  => 'nullable|string|max:3000',
            'status'       => 'required|in:rascunho,enviada',
        ]);

        $proposta = PropostaServico::create(array_merge($data, [
            'consultor_id' => Auth::id(),
        ]));

        return redirect()->route('consultor.proposta-servicos.show', $proposta)
            ->with('success', 'Proposta criada com sucesso!');
    }

    public function show(PropostaServico $propostaServico): Response
    {
        abort_unless($propostaServico->consultor_id === Auth::id(), 403);

        return Inertia::render('Consultor/PropostasServicos/Show', [
            'proposta' => $propostaServico->load('cliente:id,tipo_pessoa,nome,razao_social,email,telefone,cpf,cnpj'),
        ]);
    }

    public function edit(PropostaServico $propostaServico): Response
    {
        abort_unless($propostaServico->consultor_id === Auth::id(), 403);

        return Inertia::render('Consultor/PropostasServicos/Form', [
            'clientes' => $this->clientes(),
            'proposta' => $propostaServico->load('cliente:id,tipo_pessoa,nome,razao_social'),
        ]);
    }

    public function update(Request $request, PropostaServico $propostaServico): RedirectResponse
    {
        abort_unless($propostaServico->consultor_id === Auth::id(), 403);

        $data = $request->validate([
            'cliente_id'   => 'required|exists:clientes,id',
            'titulo'       => 'required|string|max:255',
            'valor'        => 'required|numeric|min:0',
            'validade'     => 'required|date',
            'conteudo'     => 'required|string|min:10',
            'observacoes'  => 'nullable|string|max:3000',
            'status'       => 'required|in:rascunho,enviada,aceita,recusada,expirada',
        ]);

        $propostaServico->update($data);

        return redirect()->route('consultor.proposta-servicos.show', $propostaServico)
            ->with('success', 'Proposta atualizada!');
    }

    public function destroy(PropostaServico $propostaServico): RedirectResponse
    {
        abort_unless($propostaServico->consultor_id === Auth::id(), 403);

        $propostaServico->delete();

        return redirect()->route('consultor.proposta-servicos.index')
            ->with('success', 'Proposta excluída.');
    }
}
