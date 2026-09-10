<?php

namespace App\Http\Controllers\Admin\Integracoes;

use App\Http\Controllers\Controller;
use App\Models\Fornecedor;
use App\Models\IntegracaoHistorico;
use App\Services\Integracoes\Edeltec\EdeltecImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class EdeltecController extends Controller
{
    public function index(): Response
    {
        $fornecedor = Fornecedor::where('nome', 'like', '%edeltec%')
            ->orWhere('nome', 'like', '%Edeltec%')
            ->withCount('kits')
            ->first();

        $historicos = IntegracaoHistorico::where('tipo', 'edeltec')
            ->with('fornecedor:id,nome')
            ->latest('iniciado_em')
            ->limit(20)
            ->get()
            ->map(fn($h) => [
                'id'               => $h->id,
                'status'           => $h->status,
                'itens_importados' => $h->itens_importados,
                'itens_atualizados'=> $h->itens_atualizados,
                'itens_desativados'=> $h->itens_desativados,
                'alertas'          => $h->alertas,
                'iniciado_em'      => $h->iniciado_em?->format('d/m/Y H:i'),
                'finalizado_em'    => $h->finalizado_em?->format('d/m/Y H:i'),
                'duracao_s'        => $h->iniciado_em && $h->finalizado_em
                    ? $h->iniciado_em->diffInSeconds($h->finalizado_em)
                    : null,
            ]);

        $configurado = !empty(config('services.edeltec.api_key'))
            && !empty(config('services.edeltec.secret'));

        return Inertia::render('Admin/Integracoes/Edeltec/Index', [
            'fornecedor'   => $fornecedor ? [
                'id'        => $fornecedor->id,
                'nome'      => $fornecedor->nome,
                'kits_count'=> $fornecedor->kits_count,
            ] : null,
            'historicos'   => $historicos,
            'configurado'  => $configurado,
        ]);
    }

    public function integrar(): RedirectResponse
    {
        if (empty(config('services.edeltec.api_key'))) {
            return back()->with('error', 'Credenciais da Edeltec não configuradas. Verifique as variáveis EDELTEC_API_KEY e EDELTEC_SECRET no .env.');
        }

        try {
            $historico = (new EdeltecImportService())->importar();

            $msg = "Integração concluída: {$historico->itens_importados} importados, "
                 . "{$historico->itens_atualizados} atualizados, "
                 . "{$historico->itens_desativados} desativados.";

            if ($historico->alertas) {
                return back()->with('warning', $msg . ' Há alertas — verifique o histórico.');
            }

            return back()->with('success', $msg);

        } catch (\Throwable $e) {
            Log::error('Edeltec (controller): ' . $e->getMessage());
            return back()->with('error', 'Falha na integração: ' . $e->getMessage());
        }
    }
}
