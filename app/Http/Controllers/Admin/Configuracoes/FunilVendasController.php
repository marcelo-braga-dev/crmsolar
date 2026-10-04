<?php

namespace App\Http\Controllers\Admin\Configuracoes;

use App\Http\Controllers\Controller;
use App\Models\Config;
use App\Models\FunilEtapa;
use App\Models\MotivoPerda;
use App\Models\OrcamentoHistorico;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Configuração do funil de vendas: etapas (colunas do Kanban), motivos de perda e parâmetros.
 * Etapas de sistema (aprovação, ganho, perdido) só têm nome e cor editáveis — docs/funil-de-vendas.md.
 */
class FunilVendasController extends Controller
{
    private const COR = ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'];

    public function index(): Response
    {
        return Inertia::render('Admin/Configuracoes/Funil/Index', [
            'etapas' => FunilEtapa::withCount(['orcamentos' => fn ($q) => $q->whereNull('perdido_em')->whereIn('status', ['novo', 'aprovacao_reprovada'])])
                ->orderBy('ordem')->orderBy('id')->get(),
            'motivos' => MotivoPerda::withCount('orcamentos')->orderBy('ordem')->orderBy('nome')->get(),
            'parametros' => [
                'dias_caixa_entrada' => (int) Config::get('funil.dias_caixa_entrada', 7),
                'max_tentativas_reativacao' => (int) Config::get('funil.max_tentativas_reativacao', 3),
            ],
        ]);
    }

    // ── Etapas ────────────────────────────────────────────────────────────

    public function storeEtapa(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome' => 'required|string|max:60|unique:funil_etapas,nome',
            'cor' => self::COR,
            'probabilidade' => 'nullable|integer|min:0|max:100',
            'sla_dias' => 'nullable|integer|min:1|max:365',
        ]);

        // Nova etapa entra por último entre as abertas (antes das etapas de sistema).
        $ordem = (int) FunilEtapa::where('tipo', FunilEtapa::ABERTA)->max('ordem') + 1;
        FunilEtapa::create($data + ['tipo' => FunilEtapa::ABERTA, 'ordem' => $ordem, 'ativa' => true]);

        return back()->with('success', 'Etapa criada.');
    }

    public function updateEtapa(Request $request, FunilEtapa $etapa): RedirectResponse
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:60', Rule::unique('funil_etapas', 'nome')->ignore($etapa->id)],
            'cor' => self::COR,
            'probabilidade' => 'nullable|integer|min:0|max:100',
            'sla_dias' => 'nullable|integer|min:1|max:365',
            'ativa' => 'boolean',
        ]);

        if ($etapa->ehSistema()) {
            // Tipo de sistema: comportamento fixo. Só a aprovação tem SLA (tempo esperando o admin).
            $data = array_intersect_key($data, array_flip($etapa->tipo === FunilEtapa::APROVACAO ? ['nome', 'cor', 'sla_dias'] : ['nome', 'cor']));
        } elseif (array_key_exists('ativa', $data) && ! $data['ativa'] && $etapa->ativa) {
            if ($erro = $this->impedimentoParaRetirar($etapa)) {
                return back()->with('error', $erro);
            }
        }

        $etapa->update($data);

        return back()->with('success', 'Etapa atualizada.');
    }

    public function destroyEtapa(Request $request, FunilEtapa $etapa): RedirectResponse
    {
        if ($etapa->ehSistema()) {
            return back()->with('error', 'Etapas de sistema não podem ser excluídas.');
        }

        $vinculados = $etapa->orcamentos()->withTrashed()->count();

        $data = $request->validate([
            'destino_id' => [
                Rule::requiredIf($vinculados > 0),
                'nullable',
                Rule::exists('funil_etapas', 'id')->where('tipo', FunilEtapa::ABERTA)->where('ativa', true),
                Rule::notIn([$etapa->id]),
            ],
        ], ['destino_id.required' => 'Esta etapa tem orçamentos: escolha para qual etapa eles vão.']);

        if ($etapa->ativa && FunilEtapa::abertas()->whereKeyNot($etapa->id)->doesntExist()) {
            return back()->with('error', 'O funil precisa de pelo menos uma etapa aberta ativa.');
        }

        DB::transaction(function () use ($etapa, $data, $vinculados) {
            if ($vinculados > 0) {
                $destino = FunilEtapa::findOrFail($data['destino_id']);
                $ids = $etapa->orcamentos()->withTrashed()->pluck('id');

                // Em massa (sem eventos de model): o histórico registra a mudança de cada orçamento.
                $etapa->orcamentos()->withTrashed()->update(['funil_etapa_id' => $destino->id, 'etapa_entrou_em' => now()]);
                OrcamentoHistorico::insert($ids->map(fn ($id) => [
                    'orcamento_id' => $id,
                    'usuario_id' => Auth::id(),
                    'tipo' => 'etapa',
                    'status' => null,
                    'mensagem' => "Etapa \"{$etapa->nome}\" excluída: orçamento movido para \"{$destino->nome}\".",
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all());
            }

            $etapa->delete();
        });

        return back()->with('success', 'Etapa excluída.');
    }

    public function reordenarEtapas(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => ['integer', 'distinct', Rule::exists('funil_etapas', 'id')->where('tipo', FunilEtapa::ABERTA)],
        ]);

        $abertas = FunilEtapa::where('tipo', FunilEtapa::ABERTA)->pluck('id')->sort()->values()->all();
        $enviadas = collect($data['ids'])->map(fn ($id) => (int) $id)->sort()->values()->all();
        if ($abertas !== $enviadas) {
            return back()->with('error', 'A lista de etapas mudou. Recarregue a página e tente de novo.');
        }

        DB::transaction(function () use ($data) {
            foreach ($data['ids'] as $posicao => $id) {
                FunilEtapa::whereKey($id)->update(['ordem' => $posicao + 1]);
            }
        });

        return back()->with('success', 'Ordem das etapas atualizada.');
    }

    /** Motivo pelo qual a etapa não pode sair do quadro (desativar), ou null. */
    private function impedimentoParaRetirar(FunilEtapa $etapa): ?string
    {
        if (FunilEtapa::abertas()->whereKeyNot($etapa->id)->doesntExist()) {
            return 'O funil precisa de pelo menos uma etapa aberta ativa.';
        }

        if ($etapa->orcamentos()->whereNull('perdido_em')->whereIn('status', ['novo', 'aprovacao_reprovada'])->exists()) {
            return 'Esta etapa tem orçamentos em negociação. Mova-os ou exclua a etapa escolhendo um destino.';
        }

        return null;
    }

    // ── Motivos de perda ──────────────────────────────────────────────────

    public function storeMotivo(Request $request): RedirectResponse
    {
        $data = $this->validarMotivo($request);
        $data['ordem'] = (int) MotivoPerda::max('ordem') + 1;

        MotivoPerda::create($data + ['ativo' => true]);

        return back()->with('success', 'Motivo de perda criado.');
    }

    public function updateMotivo(Request $request, MotivoPerda $motivo): RedirectResponse
    {
        $motivo->update($this->validarMotivo($request, $motivo) + $request->validate(['ativo' => 'boolean']));

        return back()->with('success', 'Motivo de perda atualizado.');
    }

    public function destroyMotivo(MotivoPerda $motivo): RedirectResponse
    {
        if ($motivo->orcamentos()->withTrashed()->exists()) {
            return back()->with('error', 'Motivo já usado em orçamentos: desative-o em vez de excluir.');
        }

        $motivo->delete();

        return back()->with('success', 'Motivo de perda excluído.');
    }

    private function validarMotivo(Request $request, ?MotivoPerda $motivo = null): array
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:80', Rule::unique('motivos_perda', 'nome')->ignore($motivo?->id)],
            'reativavel' => 'boolean',
            'reativar_apos_dias' => 'nullable|required_if_accepted:reativavel|integer|min:1|max:730',
        ]);

        if (! ($data['reativavel'] ?? false)) {
            $data['reativar_apos_dias'] = null;
        }

        return $data;
    }

    // ── Parâmetros ────────────────────────────────────────────────────────

    public function updateParametros(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'dias_caixa_entrada' => 'required|integer|min:1|max:90',
            'max_tentativas_reativacao' => 'required|integer|min:1|max:10',
        ]);

        foreach ($data as $chave => $valor) {
            Config::set("funil.{$chave}", $valor, 'funil');
        }

        return back()->with('success', 'Parâmetros do funil atualizados.');
    }
}
