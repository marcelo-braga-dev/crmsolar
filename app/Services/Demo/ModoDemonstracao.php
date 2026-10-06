<?php

namespace App\Services\Demo;

use App\Models\DemoVisitante;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use RuntimeException;

/**
 * Modo demonstração (DEMO.md): visitante entra sem senha, troca de perfil com um clique e só
 * visualiza. Concentra a lógica; controllers e middleware só chamam este serviço.
 */
class ModoDemonstracao
{
    /** Perfis oferecidos na demonstração (users.tipo => rótulo). Única fonte: backend, barra e exportação. */
    public const PERFIS = [
        'admin' => 'Administrador',
        'consultor' => 'Consultor',
    ];

    public const SESSAO_VISITANTE = 'demo.visitante_id';

    public const SESSAO_PERFIL = 'demo.perfil';

    public const AVISO_BLOQUEIO = 'Acesso de teste: criar, editar e excluir estão desativados nesta demonstração.';

    public function ativo(): bool
    {
        return (bool) config('demo.enabled');
    }

    /**
     * Cria o visitante ou, se o mesmo e-mail ou telefone já veio antes, atualiza e soma uma visita.
     *
     * @param  array{nome: string, email?: string|null, telefone?: string|null, empresa?: string|null, utm_source?: string|null, utm_medium?: string|null, utm_campaign?: string|null}  $dados
     */
    public function registrarVisitante(array $dados, Request $request): DemoVisitante
    {
        $email = isset($dados['email']) && $dados['email'] !== '' ? mb_strtolower(trim($dados['email'])) : null;
        $telefone = isset($dados['telefone']) ? (preg_replace('/\D/', '', $dados['telefone']) ?: null) : null;

        // Sem e-mail nem telefone não há como reconhecer quem volta (e a busca casaria com qualquer um).
        $visitante = $email || $telefone
            ? DemoVisitante::query()
                ->where(fn ($q) => $q
                    ->when($email, fn ($q) => $q->orWhere('email', $email))
                    ->when($telefone, fn ($q) => $q->orWhere('telefone', $telefone)))
                ->first()
            : null;

        $agora = now();
        $comuns = array_filter([
            'nome' => trim($dados['nome']),
            'email' => $email,
            'telefone' => $telefone,
            'empresa' => isset($dados['empresa']) ? trim((string) $dados['empresa']) ?: null : null,
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
        ], fn ($v) => $v !== null);

        if ($visitante) {
            $visitante->fill($comuns + ['ultimo_acesso_em' => $agora]);
            $visitante->visitas++;
            $visitante->save();

            return $visitante;
        }

        return DemoVisitante::create($comuns + [
            'referer' => mb_substr((string) $request->headers->get('referer'), 0, 255) ?: null,
            'utm_source' => $dados['utm_source'] ?? null,
            'utm_medium' => $dados['utm_medium'] ?? null,
            'utm_campaign' => $dados['utm_campaign'] ?? null,
            'visitas' => 1,
            'telas_vistas' => 0,
            'perfis_vistos' => [],
            'primeiro_acesso_em' => $agora,
            'ultimo_acesso_em' => $agora,
        ]);
    }

    /** Autentica como o usuário fictício do perfil e anota o perfil no visitante. */
    public function entrarComo(string $perfil, DemoVisitante $visitante, Request $request): User
    {
        $usuario = $this->usuarioPara($perfil);
        Auth::login($usuario);

        $request->session()->put(self::SESSAO_VISITANTE, $visitante->id);
        $request->session()->put(self::SESSAO_PERFIL, $perfil);

        $visitante->forceFill([
            'perfis_vistos' => array_values(array_unique([...($visitante->perfis_vistos ?? []), $perfil])),
            'ultimo_perfil' => $perfil,
            'ultimo_acesso_em' => now(),
        ])->save();

        return $usuario;
    }

    /** Usuário fictício do perfil: o configurado ou o primeiro ativo daquele perfil. */
    public function usuarioPara(string $perfil): User
    {
        if (! array_key_exists($perfil, self::PERFIS)) {
            throw new RuntimeException("Perfil de demonstração inválido: {$perfil}.");
        }

        $configurado = config("demo.users.{$perfil}");
        $usuario = User::where('tipo', $perfil)->where('status', true)
            ->when($configurado, fn ($q) => $q->where('email', $configurado))->first()
            ?? User::where('tipo', $perfil)->where('status', true)->orderBy('id')->first();

        return $usuario ?? throw new RuntimeException(
            "Nenhum usuário ativo do perfil \"{$perfil}\" para a demonstração. Rode o seeder de demonstração (MarketingDemoSeeder)."
        );
    }

    public function visitante(Request $request): ?DemoVisitante
    {
        $id = $request->hasSession() ? $request->session()->get(self::SESSAO_VISITANTE) : null;

        return $id ? DemoVisitante::find($id) : null;
    }

    /** Soma uma tela vista ao visitante da sessão (UPDATE atômico, sem ler antes). */
    public function registrarTelaVista(Request $request): void
    {
        $id = $request->hasSession() ? $request->session()->get(self::SESSAO_VISITANTE) : null;
        if ($id) {
            DemoVisitante::whereKey($id)->increment('telas_vistas', 1, ['ultimo_acesso_em' => now()]);
        }
    }

    /**
     * Dados para todas as páginas (prop `demo`).
     *
     * @return array{enabled: true, role: ?string, visitor: ?string, roles: array<int, array{key: string, label: string}>, allowed_paths: array<int, string>}
     */
    public function propsCompartilhadas(Request $request): array
    {
        $usuario = $request->user();

        return [
            'enabled' => true,
            'role' => $request->hasSession() ? ($request->session()->get(self::SESSAO_PERFIL) ?? $usuario?->tipo) : $usuario?->tipo,
            'visitor' => $this->visitante($request)?->nome,
            'roles' => collect(self::PERFIS)->map(fn ($rotulo, $chave) => ['key' => $chave, 'label' => $rotulo])->values()->all(),
            'allowed_paths' => $this->caminhosLiberados(),
            'message' => self::AVISO_BLOQUEIO,
        ];
    }

    /**
     * Caminhos de URL liberados no navegador (o servidor confere pelo nome da rota). Parâmetros
     * viram "*"; entradas terminadas em "/" valem como prefixo.
     *
     * @return array<int, string>
     */
    public function caminhosLiberados(): array
    {
        $nomes = [...config('demo.readonly_post_routes', []), ...config('demo.readonly_form_routes', []), 'logout'];

        $caminhos = collect($nomes)
            ->map(fn ($nome) => Route::getRoutes()->getByName($nome)?->uri())
            ->filter()
            ->map(fn ($uri) => '/'.ltrim(preg_replace('/\{[^}]+\}/', '*', $uri), '/'))
            ->values()
            ->all();

        return [...$caminhos, '/demo/'];
    }

    /** Nome da rota é uma das liberadas para leitura (POST que só calcula ou tela de vitrine). */
    public function rotaLiberada(?string $nome): bool
    {
        return $nome !== null && (
            $nome === 'logout'
            || str_starts_with($nome, 'demo.')
            || in_array($nome, config('demo.readonly_post_routes', []), true)
            || in_array($nome, config('demo.readonly_form_routes', []), true)
        );
    }
}
