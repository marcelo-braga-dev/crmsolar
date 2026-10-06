<?php

namespace App\Http\Middleware;

use App\Services\Demo\ModoDemonstracao;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garantia real do modo demonstração (DEMO.md): no servidor, nada é gravado. O frontend só
 * esmaece botões e avisa; mesmo chamando a rota direto, a escrita é negada aqui.
 */
class BloqueiaEscritaNaDemonstracao
{
    /** Fluxos de senha: não existem na demonstração (o acesso é sem senha). */
    private const ROTAS_DE_SENHA = ['password.request', 'password.email', 'password.reset', 'password.store', 'password.confirm', 'password.update'];

    public function __construct(private readonly ModoDemonstracao $demo) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->demo->ativo()) {
            return $next($request);
        }

        $rota = $request->route()?->getName();

        if (in_array($rota, self::ROTAS_DE_SENHA, true) || ($request->isMethod('post') && $request->path() === 'confirm-password')) {
            return redirect()->route('login');
        }

        if ($this->escrita($request, $rota) || $this->telaDeFormulario($request, $rota)) {
            return $this->negar($request);
        }

        $resposta = $next($request);

        if ($request->isMethod('get') && $resposta->getStatusCode() === 200 && $this->pagina($request, $resposta)) {
            $this->demo->registrarTelaVista($request);
        }

        return $resposta;
    }

    private function escrita(Request $request, ?string $rota): bool
    {
        return ! $request->isMethodSafe() && ! $this->demo->rotaLiberada($rota);
    }

    /** Telas que só existem para alterar dados (.create, .edit e troca de senha). */
    private function telaDeFormulario(Request $request, ?string $rota): bool
    {
        return $request->isMethodSafe() && $rota !== null
            && (str_ends_with($rota, '.create') || str_ends_with($rota, '.edit') || str_ends_with($rota, '.senha'))
            && ! $this->demo->rotaLiberada($rota);
    }

    private function negar(Request $request): Response
    {
        // Chamada de API/axios (não navegação do Inertia): 403 em JSON.
        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['message' => ModoDemonstracao::AVISO_BLOQUEIO, 'demo' => true], 403);
        }

        $destino = $request->user() ? route('dashboard') : route('login');

        return redirect()->back(fallback: $destino)->with('warning', ModoDemonstracao::AVISO_BLOQUEIO);
    }

    /** Página de verdade (HTML ou navegação do Inertia), não chamada JSON ou arquivo. */
    private function pagina(Request $request, Response $resposta): bool
    {
        return $request->header('X-Inertia') !== null
            || str_contains((string) $resposta->headers->get('Content-Type'), 'text/html');
    }
}
