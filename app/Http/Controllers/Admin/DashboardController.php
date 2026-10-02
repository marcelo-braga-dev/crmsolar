<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Lead;
use App\Models\Orcamento;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $mesAtual    = now()->startOfMonth();
        $mesAnterior = now()->subMonth()->startOfMonth();

        // KPIs principais
        $stats = [
            'orcamentos_mes'       => Orcamento::where('created_at', '>=', $mesAtual)->count(),
            'orcamentos_mes_ant'   => Orcamento::whereBetween('created_at', [$mesAnterior, $mesAtual])->count(),
            'clientes_total'       => Cliente::count(),
            'leads_abertos'        => Lead::whereIn('status', ['novo', 'contatado', 'encaminhado'])->count(),
            'valor_aprovado_mes'   => (float) Orcamento::where('created_at', '>=', $mesAtual)
                ->whereIn('status', ['aprovado', 'instalando', 'finalizado'])
                ->sum('preco_total'),
            'valor_aprovado_total' => (float) Orcamento::whereIn('status', ['aprovado', 'instalando', 'finalizado'])
                ->sum('preco_total'),
            'consultores_ativos'   => User::where('tipo', 'consultor')->where('status', true)->count(),
            'em_instalacao'        => Orcamento::where('status', 'instalando')->count(),
        ];

        // Orçamentos por status
        $porStatus = Orcamento::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->toArray();

        // Últimos 6 meses — orçamentos e valor
        $evolucao = collect(range(5, 0))->map(function ($mesesAtras) {
            $inicio = now()->subMonths($mesesAtras)->startOfMonth();
            $fim    = now()->subMonths($mesesAtras)->endOfMonth();

            return [
                'mes'   => $inicio->format('M/y'),
                'qtd'   => Orcamento::whereBetween('created_at', [$inicio, $fim])->count(),
                'valor' => (float) Orcamento::whereBetween('created_at', [$inicio, $fim])
                    ->whereIn('status', ['aprovado', 'instalando', 'finalizado'])
                    ->sum('preco_total'),
            ];
        })->values();

        // Top consultores por valor aprovado no mês
        $topConsultores = User::where('tipo', 'consultor')
            ->withSum(['orcamentos as valor_mes' => fn ($q) => $q
                ->where('created_at', '>=', $mesAtual)
                ->whereIn('status', ['aprovado', 'instalando', 'finalizado'])
            ], 'preco_total')
            ->withCount(['orcamentos as qtd_mes' => fn ($q) => $q->where('created_at', '>=', $mesAtual)])
            ->orderByDesc('valor_mes')
            ->limit(5)
            ->get(['id', 'name', 'email']);

        // Orçamentos recentes
        $recentes = Orcamento::with(['consultor:id,name', 'cliente:id,nome,razao_social,tipo_pessoa'])
            ->latest()
            ->limit(8)
            ->get(['id', 'consultor_id', 'cliente_id', 'status', 'preco_total', 'created_at']);

        return Inertia::render('Admin/Dashboard', [
            'stats'          => $stats,
            'porStatus'      => $porStatus,
            'evolucao'       => $evolucao,
            'topConsultores' => $topConsultores,
            'recentes'       => $recentes,
        ]);
    }
}
