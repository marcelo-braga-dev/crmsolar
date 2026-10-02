<?php

namespace App\Http\Controllers\Consultor;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Lead;
use App\Models\Orcamento;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $userId = Auth::id();
        $mesAtual = now()->startOfMonth();

        $stats = [
            'clientes' => Cliente::where('consultor_id', $userId)->count(),
            'leads_abertos' => Lead::where('consultor_id', $userId)->whereIn('status', ['novo', 'contatado', 'encaminhado'])->count(),
            'orcamentos_mes' => Orcamento::where('consultor_id', $userId)->where('created_at', '>=', $mesAtual)->count(),
            'valor_aprovado_mes' => (float) Orcamento::where('consultor_id', $userId)
                ->where('created_at', '>=', $mesAtual)
                ->whereIn('status', ['aprovado', 'instalando', 'finalizado'])
                ->sum('preco_total'),
            'em_aprovacao' => Orcamento::where('consultor_id', $userId)->where('status', 'aprovando')->count(),
            'aprovados' => Orcamento::where('consultor_id', $userId)->where('status', 'aprovado')->count(),
        ];

        $evolucao = collect(range(5, 0))->map(function ($mesesAtras) use ($userId) {
            $inicio = now()->subMonths($mesesAtras)->startOfMonth();
            $fim = now()->subMonths($mesesAtras)->endOfMonth();

            return [
                'mes' => $inicio->format('M/y'),
                'qtd' => Orcamento::where('consultor_id', $userId)->whereBetween('created_at', [$inicio, $fim])->count(),
                'valor' => (float) Orcamento::where('consultor_id', $userId)
                    ->whereBetween('created_at', [$inicio, $fim])
                    ->whereIn('status', ['aprovado', 'instalando', 'finalizado'])
                    ->sum('preco_total'),
            ];
        })->values();

        $recentes = Orcamento::where('consultor_id', $userId)
            ->with('cliente:id,nome,razao_social,tipo_pessoa')
            ->latest()
            ->limit(6)
            ->get(['id', 'cliente_id', 'status', 'preco_total', 'created_at']);

        $clientesRecentes = Cliente::where('consultor_id', $userId)
            ->with('cidade:id,cidade,estado')
            ->latest()
            ->limit(5)
            ->get(['id', 'nome', 'razao_social', 'tipo_pessoa', 'status', 'cidade_id', 'created_at']);

        return Inertia::render('Consultor/Dashboard', [
            'stats' => $stats,
            'evolucao' => $evolucao,
            'recentes' => $recentes,
            'clientesRecentes' => $clientesRecentes,
        ]);
    }
}
