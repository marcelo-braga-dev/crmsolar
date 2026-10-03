<?php

namespace App\Http\Controllers\Consultor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Consultor\ContratoStoreRequest;
use App\Models\Contrato;
use App\Models\Orcamento;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ContratosController extends Controller
{
    public function index(Request $request): Response
    {
        $contratos = Contrato::query()
            ->where('consultor_id', Auth::id())
            ->with(['orcamento.cliente'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Consultor/Contratos/Index', [
            'contratos' => $contratos,
            'filters' => $request->only(['status']),
        ]);
    }

    public function show(Contrato $contrato): Response
    {
        $this->authorize('view', $contrato);

        $contrato->load(['orcamento.cliente']);

        return Inertia::render('Consultor/Contratos/Show', [
            'contrato' => $contrato,
        ]);
    }

    public function create(Orcamento $orcamento): Response|RedirectResponse
    {
        $this->authorize('view', $orcamento);
        abort_if($orcamento->status !== 'aprovado', 403, 'Só é possível gerar contrato para um orçamento aprovado.');

        if ($orcamento->contrato) {
            return redirect()->route('consultor.contratos.show', $orcamento->contrato);
        }

        $orcamento->load(['cliente.cidade', 'itens', 'info']);

        $kitItem = $orcamento->itens->firstWhere('tipo', 'kit');

        return Inertia::render('Consultor/Contratos/Create', [
            'orcamento' => $orcamento,
            'sugestao' => [
                'nome_cliente' => $orcamento->cliente->nome_display,
                'documento_cliente' => $orcamento->cliente->tipo_pessoa === 'pj'
                    ? $orcamento->cliente->cnpj
                    : $orcamento->cliente->cpf,
                'endereco_instalacao' => collect([
                    $orcamento->cliente->rua,
                    $orcamento->cliente->numero,
                    $orcamento->cliente->bairro,
                    $orcamento->cliente->cidade?->cidade,
                    $orcamento->cliente->cidade?->estado,
                ])->filter()->implode(', '),
                'potencia_kwp' => $kitItem?->metadados['potencia_kwp'] ?? null,
                'consumo_mensal' => $orcamento->info?->consumo,
                'geracao_estimada' => $orcamento->geracao_estimada,
                'valor_total' => $orcamento->preco_total,
            ],
        ]);
    }

    public function store(ContratoStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Lock no orçamento: dois envios simultâneos não podem gerar dois contratos.
        $contrato = DB::transaction(function () use ($data) {
            $orcamento = Orcamento::with('itens')->lockForUpdate()->findOrFail($data['orcamento_id']);

            abort_if($orcamento->status !== 'aprovado', 403, 'Só é possível gerar contrato para um orçamento aprovado.');

            if ($existente = $orcamento->contrato()->first()) {
                return $existente;
            }

            $data['consultor_id'] = Auth::id();
            $data['status'] = 'gerado';
            // Valor vem sempre do orçamento aprovado, nunca do formulário.
            $data['valor_total'] = $orcamento->preco_total;
            $data['produtos_snapshot'] = $orcamento->itens->map(fn ($item) => [
                'descricao' => $item->descricao,
                'quantidade' => $item->quantidade,
                'preco_venda_unitario' => (float) $item->preco_venda_unitario,
                'preco_venda_total' => (float) $item->preco_venda_total,
            ])->all();

            return Contrato::create($data);
        });

        if (! $contrato->wasRecentlyCreated) {
            return redirect()->route('consultor.contratos.show', $contrato)->with('info', 'Este orçamento já possui contrato.');
        }

        return redirect()->route('consultor.contratos.show', $contrato)->with('success', 'Contrato gerado com sucesso.');
    }

    public function pdf(Contrato $contrato): HttpResponse
    {
        $this->authorize('view', $contrato);

        $contrato->load(['orcamento.cliente', 'consultor:id,name,email,celular']);

        $pdf = Pdf::loadView('pdf.contrato', ['contrato' => $contrato])
            ->setPaper('a4');

        return $pdf->stream("contrato-{$contrato->id}.pdf");
    }
}
