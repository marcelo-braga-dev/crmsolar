<?php

namespace App\Http\Controllers\Admin\Configuracoes;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Concessionaria;
use App\Models\Contrato;
use App\Models\Kit;
use App\Models\Lead;
use App\Models\MargemEstado;
use App\Models\MargemFornecedor;
use App\Models\MargemPrincipal;
use App\Models\Orcamento;
use App\Models\OrcamentoItem;
use App\Models\ParamDimensionamento;
use App\Models\Produto;
use App\Models\PropostaServico;
use App\Models\User;
use App\Models\VisitaTecnica;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

/**
 * Consulta do log de auditoria (spatie/activitylog): quem alterou o quê e quando.
 */
class AuditoriaController extends Controller
{
    /** Rótulos dos tipos auditados — a chave curta é usada no filtro da tela. */
    private const TIPOS = [
        'orcamento' => [Orcamento::class, 'Orçamento'],
        'orcamento_item' => [OrcamentoItem::class, 'Item de orçamento'],
        'margem_principal' => [MargemPrincipal::class, 'Margem por potência'],
        'margem_estado' => [MargemEstado::class, 'Margem por estado'],
        'margem_fornecedor' => [MargemFornecedor::class, 'Margem por fornecedor'],
        'kit' => [Kit::class, 'Kit'],
        'produto' => [Produto::class, 'Produto'],
        'concessionaria' => [Concessionaria::class, 'Concessionária'],
        'parametro' => [ParamDimensionamento::class, 'Parâmetro de dimensionamento'],
        'usuario' => [User::class, 'Usuário'],
        'cliente' => [Cliente::class, 'Cliente'],
        'lead' => [Lead::class, 'Lead'],
        'contrato' => [Contrato::class, 'Contrato'],
        'visita' => [VisitaTecnica::class, 'Visita técnica'],
        'proposta' => [PropostaServico::class, 'Proposta de serviço'],
    ];

    public function index(Request $request): Response
    {
        $rotulos = collect(self::TIPOS)->mapWithKeys(fn ($t) => [$t[0] => $t[1]]);

        $atividades = Activity::query()
            ->with('causer')
            ->when($request->tipo, fn ($q, $tipo) => $q->where('subject_type', self::TIPOS[$tipo][0] ?? '-'))
            ->when($request->usuario_id, fn ($q, $id) => $q->where('causer_type', User::class)->where('causer_id', $id))
            ->when($request->evento, fn ($q, $evento) => $q->where('event', $evento))
            ->latest('id')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (Activity $a) => [
                'id' => $a->id,
                'data' => $a->created_at?->toIso8601String(),
                'evento' => $a->event,
                'tipo' => $rotulos[$a->subject_type] ?? class_basename((string) $a->subject_type),
                'registro_id' => $a->subject_id,
                'usuario' => $a->causer?->name, // null = sistema (seed, integração)
                'alteracoes' => $this->alteracoes($a),
            ]);

        return Inertia::render('Admin/Configuracoes/Auditoria/Index', [
            'atividades' => $atividades,
            'filters' => $request->only(['tipo', 'usuario_id', 'evento']),
            'tipos' => collect(self::TIPOS)->map(fn ($t, $chave) => ['value' => $chave, 'label' => $t[1]])->values(),
            'usuarios' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** @return array<int, array{campo: string, antes: mixed, depois: mixed}> */
    private function alteracoes(Activity $a): array
    {
        $novos = $a->attribute_changes?->get('attributes', []) ?? [];
        $antigos = $a->attribute_changes?->get('old', []) ?? [];

        return collect(array_keys($novos + $antigos))
            ->map(fn ($campo) => ['campo' => $campo, 'antes' => $antigos[$campo] ?? null, 'depois' => $novos[$campo] ?? null])
            ->values()
            ->all();
    }
}
