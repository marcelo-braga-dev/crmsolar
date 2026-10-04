<?php

namespace App\Http\Controllers;

use App\Models\FunilEtapa;
use App\Models\MotivoPerda;
use App\Models\Orcamento;
use App\Models\User;
use App\Services\Funil\FunilService;
use App\Services\Funil\MovimentoInvalido;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Quadro Kanban do funil de vendas, compartilhado por Admin (todos os orçamentos) e
 * Consultor (só os seus). Regras de movimentação em FunilService — docs/funil-de-vendas.md.
 */
class FunilController extends Controller
{
    public function __construct(private FunilService $funil) {}

    public function index(Request $request): Response
    {
        /** @var User $usuario */
        $usuario = $request->user();

        $filtros = [
            'consultor_id' => $request->input('consultor_id'),
            'busca' => trim((string) $request->input('busca')) ?: null,
            'grupo' => $request->input('grupo'),
            'atrasados' => $request->boolean('atrasados'),
        ];

        return Inertia::render('Funil/Index', [
            'area' => $usuario->isAdmin() ? 'admin' : 'consultor',
            ...$this->funil->quadro($usuario, $filtros),
            'motivos' => MotivoPerda::where('ativo', true)->orderBy('ordem')->orderBy('nome')->get(['id', 'nome', 'reativavel']),
            'consultores' => $usuario->isAdmin()
                ? User::where('tipo', 'consultor')->orderBy('name')->get(['id', 'name'])
                : [],
            'filtros' => array_filter($filtros),
        ]);
    }

    public function mover(Request $request, Orcamento $orcamento): RedirectResponse
    {
        $this->autorizar($request->user(), $orcamento);

        $data = $request->validate([
            'etapa_id' => ['required', Rule::exists('funil_etapas', 'id')],
            'origem' => 'required|string|max:20',
            'proximo_contato_em' => 'nullable|date|after_or_equal:today',
        ]);

        return $this->executar(fn () => $this->funil->mover(
            $orcamento,
            FunilEtapa::findOrFail($data['etapa_id']),
            $request->user(),
            $data['origem'],
            $this->data($data['proximo_contato_em'] ?? null),
        ));
    }

    public function perder(Request $request, Orcamento $orcamento): RedirectResponse
    {
        $this->autorizar($request->user(), $orcamento);

        $data = $request->validate([
            'motivo_perda_id' => ['required', Rule::exists('motivos_perda', 'id')->where('ativo', true)],
            'observacao' => 'nullable|string|max:1000',
            'origem' => 'required|string|max:20',
        ]);

        return $this->executar(fn () => $this->funil->perder(
            $orcamento,
            MotivoPerda::findOrFail($data['motivo_perda_id']),
            $data['observacao'] ?? null,
            $request->user(),
            $data['origem'],
        ), 'Venda marcada como perdida.');
    }

    public function reativar(Request $request, Orcamento $orcamento): RedirectResponse
    {
        $this->autorizar($request->user(), $orcamento);

        $data = $request->validate([
            'etapa_id' => ['nullable', Rule::exists('funil_etapas', 'id')],
            'proximo_contato_em' => 'nullable|date|after_or_equal:today',
        ]);

        return $this->executar(fn () => $this->funil->reativar(
            $orcamento,
            $request->user(),
            isset($data['etapa_id']) ? FunilEtapa::findOrFail($data['etapa_id']) : null,
            $this->data($data['proximo_contato_em'] ?? null),
        ), 'Negociação reativada.');
    }

    public function contato(Request $request, Orcamento $orcamento): RedirectResponse
    {
        $this->autorizar($request->user(), $orcamento);

        $data = $request->validate([
            'nota' => 'nullable|string|max:1000|required_without:proximo_contato_em',
            'proximo_contato_em' => 'nullable|date|after_or_equal:today',
        ]);

        return $this->executar(fn () => $this->funil->registrarContato(
            $orcamento,
            $request->user(),
            $data['nota'] ?? null,
            $this->data($data['proximo_contato_em'] ?? null),
        ), 'Contato registrado.');
    }

    /** Admin opera qualquer orçamento; consultor só os seus (OrcamentoPolicy). */
    private function autorizar(User $usuario, Orcamento $orcamento): void
    {
        if (! $usuario->isAdmin()) {
            $this->authorize('update', $orcamento);
        }
    }

    private function executar(callable $operacao, ?string $sucesso = null): RedirectResponse
    {
        try {
            $operacao();
        } catch (MovimentoInvalido $e) {
            return back()->with('error', $e->getMessage());
        }

        return $sucesso ? back()->with('success', $sucesso) : back();
    }

    private function data(?string $valor): ?Carbon
    {
        return $valor ? Carbon::parse($valor) : null;
    }
}
