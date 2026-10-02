<?php

namespace App\Services\Integracoes\Edeltec;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente HTTP para a API da Edeltec Solar.
 *
 * Responsável exclusivamente pela comunicação com a API:
 * autenticação com cache de token e busca paginada de produtos.
 */
class EdeltecApiClient
{
    private const TOKEN_CACHE_KEY = 'edeltec_api_token';

    private const TOKEN_TTL_MIN = 55;   // minutos (token dura ~60 min — renovamos 5 min antes)

    private const PAGE_LIMIT = 1000;

    private const TIPOS_PRODUTO = 'GERADOR FOTOVOLTAICO,GERADOR MICROINVERSOR';

    private const MAX_RETRIES = 3;

    private const RETRY_DELAY_MS = 800;  // ms entre tentativas

    public function __construct(
        private readonly string $apiUrl = '',
        private readonly string $apiKey = '',
        private readonly string $secret = '',
    ) {
        $this->apiUrl = $apiUrl ?: rtrim((string) config('services.edeltec.url', 'https://api.edeltecsolar.com.br'), '/');
        $this->apiKey = $apiKey ?: (string) config('services.edeltec.api_key');
        $this->secret = $secret ?: (string) config('services.edeltec.secret');
    }

    // ── Autenticação ──────────────────────────────────────────────────────

    /**
     * Retorna o token de acesso. Usa cache para evitar reautenticação desnecessária.
     *
     * @throws \RuntimeException se a autenticação falhar após as tentativas
     */
    public function token(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, self::TOKEN_TTL_MIN * 60, function () {
            return $this->autenticar();
        });
    }

    /** Força nova autenticação, descartando o token em cache. */
    public function renovarToken(): string
    {
        Cache::forget(self::TOKEN_CACHE_KEY);

        return $this->token();
    }

    private function autenticar(): string
    {
        $response = $this->tentarComRetry(function () {
            return Http::baseUrl($this->apiUrl)
                ->timeout(15)
                ->acceptJson()
                ->post('/api-access/token', [
                    'apiKey' => $this->apiKey,
                    'secret' => $this->secret,
                ]);
        });

        // A API pode retornar o token em diferentes formatos
        $body = $response->json();

        $token = $body['token']
            ?? $body['access_token']
            ?? $body['accessToken']
            ?? (is_string($body) ? $body : null)
            ?? $response->body();

        if (empty($token) || ! is_string($token)) {
            throw new \RuntimeException('Edeltec: falha na autenticação — token não encontrado na resposta.');
        }

        return trim($token);
    }

    // ── Produtos ──────────────────────────────────────────────────────────

    /**
     * Busca todos os produtos da API em todas as páginas.
     * Retorna um Generator para evitar carregar tudo em memória de uma vez.
     *
     * @return iterable<array> cada item é um produto (array associativo)
     *
     * @throws \RuntimeException em caso de falha de autenticação ou API
     */
    public function produtos(): iterable
    {
        $token = $this->token();
        $page = 1;
        $total = null;

        do {
            $resposta = $this->tentarComRetry(function () use ($token, $page) {
                return Http::baseUrl($this->apiUrl)
                    ->timeout(30)
                    ->withToken($token)
                    ->acceptJson()
                    ->get('/produtos/integration', [
                        'limit' => self::PAGE_LIMIT,
                        'page' => $page,
                        'tipo' => self::TIPOS_PRODUTO,
                    ]);
            }, onUnauthorized: function () use (&$token) {
                // Token expirou no meio da execução — renova e tenta de novo
                $token = $this->renovarToken();
            });

            $items = $resposta->json('items', []);
            $meta = $resposta->json('meta', []);
            $totalPages = (int) ($meta['totalPages'] ?? 0);

            if (! empty($items)) {
                foreach ($items as $item) {
                    if (! empty($item['codProd'])) {
                        yield $item;
                    }
                }
            }

            if ($total === null) {
                $total = (int) ($meta['totalItems'] ?? 0);
                Log::info("Edeltec: {$total} produtos encontrados em {$totalPages} páginas.");
            }

            $page++;
        } while ($this->deveContiuar($items, $page, $totalPages));
    }

    private function deveContiuar(array $items, int $proximaPagina, int $totalPages): bool
    {
        if (empty($items)) {
            return false;
        }
        if ($totalPages > 0) {
            return ($proximaPagina - 1) < $totalPages;
        }

        return true; // sem metadados: continua enquanto vier item
    }

    // ── HTTP helpers ──────────────────────────────────────────────────────

    /**
     * Executa a closure com retry exponencial para erros de rede e 5xx.
     * Em 401/403, chama o callback $onUnauthorized (se fornecido) e retenta uma vez.
     */
    private function tentarComRetry(callable $request, ?callable $onUnauthorized = null): Response
    {
        $tentativa = 0;

        while (true) {
            $tentativa++;
            try {
                $response = $request();

                if ($response->status() === 401 || $response->status() === 403) {
                    if ($onUnauthorized && $tentativa === 1) {
                        $onUnauthorized();

                        continue; // tenta mais uma vez com novo token
                    }
                    throw new \RuntimeException("Edeltec: acesso negado (HTTP {$response->status()}).");
                }

                $response->throw(); // lança RequestException para 4xx/5xx

                return $response;

            } catch (ConnectionException $e) {
                if ($tentativa >= self::MAX_RETRIES) {
                    throw new \RuntimeException("Edeltec: falha de conexão após {$tentativa} tentativas: ".$e->getMessage(), 0, $e);
                }
                Log::warning("Edeltec: tentativa {$tentativa} falhou (conexão). Retentando...");
                usleep(self::RETRY_DELAY_MS * 1000 * $tentativa);

            } catch (RequestException $e) {
                $status = $e->response->status();
                // Só retenta em 5xx
                if ($status >= 500 && $tentativa < self::MAX_RETRIES) {
                    Log::warning("Edeltec: HTTP {$status} na tentativa {$tentativa}. Retentando...");
                    usleep(self::RETRY_DELAY_MS * 1000 * $tentativa);

                    continue;
                }
                throw new \RuntimeException("Edeltec: erro HTTP {$status}: ".$e->getMessage(), 0, $e);
            }
        }
    }
}
