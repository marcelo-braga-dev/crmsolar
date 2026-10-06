<?php

namespace App\Http\Controllers;

use App\Services\Demo\ExportacaoVisitantes;
use App\Services\Demo\ModoDemonstracao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Rotas do modo demonstração (/demo). Respondem 404 com o modo desligado. */
class DemoController extends Controller
{
    public function __construct(private readonly ModoDemonstracao $demo) {}

    /** Visitante se identifica e entra no perfil inicial, sem senha. */
    public function acesso(Request $request): RedirectResponse
    {
        abort_unless($this->demo->ativo(), 404);

        $dados = $request->validate([
            'nome' => 'required|string|min:2|max:120',
            'email' => 'nullable|required_without:telefone|email|max:255',
            'telefone' => ['nullable', 'required_without:email', 'regex:/^[\d\s()+\-]{8,20}$/'],
            'empresa' => 'nullable|string|max:120',
            'aceite' => 'accepted',
            'utm_source' => 'nullable|string|max:100',
            'utm_medium' => 'nullable|string|max:100',
            'utm_campaign' => 'nullable|string|max:100',
        ], [
            'email.required_without' => 'Informe o e-mail ou o telefone.',
            'telefone.required_without' => 'Informe o telefone ou o e-mail.',
            'telefone.regex' => 'Telefone inválido.',
            'aceite.accepted' => 'É preciso aceitar ser contatado pela equipe comercial.',
        ]);

        $visitante = $this->demo->registrarVisitante($dados, $request);

        $request->session()->regenerate();
        $this->demo->entrarComo((string) config('demo.initial_role', 'admin'), $visitante, $request);

        return redirect()->route('dashboard');
    }

    /** Troca de perfil pela barra de demonstração. */
    public function trocarPerfil(Request $request, string $perfil): RedirectResponse
    {
        abort_unless($this->demo->ativo() && array_key_exists($perfil, ModoDemonstracao::PERFIS), 404);

        $visitante = $this->demo->visitante($request);
        if (! $visitante) {
            return redirect()->route('login');
        }

        $this->demo->entrarComo($perfil, $visitante, $request);

        return redirect()->route('dashboard');
    }

    /** Visitantes em CSV para a equipe comercial (só com o token; sem ele, a rota "não existe"). */
    public function visitantesCsv(Request $request): StreamedResponse
    {
        $token = (string) config('demo.leads_token');
        abort_unless($this->demo->ativo() && $token !== '' && hash_equals($token, (string) $request->query('token')), 404);

        return response()->streamDownload(function () {
            $saida = fopen('php://output', 'w');
            ExportacaoVisitantes::escrever($saida);
            fclose($saida);
        }, 'visitantes-demo-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
