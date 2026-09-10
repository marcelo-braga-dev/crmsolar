<?php

namespace App\Services\Integracoes\Edeltec;

use App\Models\Estrutura;
use App\Models\Marca;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Resolve nomes vindos da API Edeltec para IDs locais.
 *
 * Carrega todas as referências de uma vez (marcas, estruturas) para
 * evitar consultas individuais por produto durante o import.
 */
class EdeltecMapeamentos
{
    // Mapeamento explícito: nome Edeltec → nome local (estruturas)
    private const ESTRUTURAS_MAP = [
        'cerâmica'       => ['Telha Colonial (Cerâmico)', 'Telha Ondulada'],
        'ceramica'       => ['Telha Colonial (Cerâmico)', 'Telha Ondulada'],
        'colonial'       => ['Telha Colonial (Cerâmico)'],
        'fibrocimento'   => ['Telha Ondulada'],
        'fibro'          => ['Telha Ondulada'],
        'metálica'       => ['Telha Metálica Perfil 55cm', 'Telha Metálica Zipada'],
        'metalica'       => ['Telha Metálica Perfil 55cm', 'Telha Metálica Zipada'],
        'zipada'         => ['Telha Metálica Zipada'],
        'laje'           => ['Laje'],
        'solo'           => ['Solo'],
        'madeira'        => ['Parafuso Estrutura Madeira'],
        'metálico'       => ['Parafuso Estrutura Metálica'],
        'sem estrutura'  => ['Sem Estrutura'],
    ];

    /** @var array<string, int> Marca nome (lower) → id */
    private array $marcasIdx;
    /** @var array<string, int> Estrutura key → id */
    private array $estruturasIdx;

    public function __construct()
    {
        $this->carregarMarcas();
        $this->carregarEstruturas();
    }

    // ── Resolução de marcas ───────────────────────────────────────────────

    /**
     * Resolve o nome do fabricante/marca da Edeltec para um id local.
     * Se não encontrar, cria a marca automaticamente.
     */
    public function resolveMarca(string $nome): int
    {
        $chave = mb_strtolower(trim($nome));

        if (!isset($this->marcasIdx[$chave])) {
            $marca = Marca::firstOrCreate(['nome' => trim($nome)], ['ativo' => true]);
            $this->marcasIdx[$chave] = $marca->id;
            Log::info("Edeltec: marca criada automaticamente: {$nome} (id={$marca->id})");
        }

        return $this->marcasIdx[$chave];
    }

    // ── Resolução de estruturas ───────────────────────────────────────────

    /**
     * Resolve o nome da estrutura da Edeltec para um id local.
     * Retorna null se não encontrar correspondência (o kit será importado sem estrutura).
     */
    public function resolveEstrutura(string $nome): ?int
    {
        $chave = mb_strtolower(trim($nome));

        // Match direto no índice
        if (isset($this->estruturasIdx[$chave])) {
            return $this->estruturasIdx[$chave];
        }

        // Match por mapeamento explícito
        foreach (self::ESTRUTURAS_MAP as $keyword => $candidatos) {
            if (str_contains($chave, $keyword)) {
                foreach ($candidatos as $nomeLocal) {
                    $chaveLocal = mb_strtolower($nomeLocal);
                    if (isset($this->estruturasIdx[$chaveLocal])) {
                        return $this->estruturasIdx[$chaveLocal];
                    }
                }
            }
        }

        // Match fuzzy: procura se o nome da Edeltec aparece em qualquer estrutura local
        foreach ($this->estruturasIdx as $nomeLocal => $id) {
            if (str_contains($nomeLocal, $chave) || str_contains($chave, $nomeLocal)) {
                return $id;
            }
        }

        Log::warning("Edeltec: estrutura não mapeada: \"{$nome}\" — kit importado sem estrutura.");
        return null;
    }

    // ── Parsing de campos ─────────────────────────────────────────────────

    /** "5.00" ou "5000" → 5.000 (kWp). A API pode vir em kW ou W. */
    public function parsePotenciaKwp(string|float $valor): float
    {
        $n = (float) str_replace(',', '.', (string) $valor);
        // Heurística: se valor > 100, provavelmente está em Wp → converte para kWp
        return $n > 100 ? round($n / 1000, 3) : round($n, 3);
    }

    /** "220" ou "220V" → 220 */
    public function parseTensao(string $valor): int
    {
        return (int) preg_replace('/\D/', '', $valor);
    }

    /** "8500.00" ou "R$ 8.500,00" → 8500.00 */
    public function parsePreco(string $valor): float
    {
        $clean = preg_replace('/[^\d,.]/', '', $valor);
        // Se tem vírgula como decimal e ponto como milhar
        if (preg_match('/,\d{1,2}$/', $clean)) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        }
        return (float) $clean;
    }

    // ── Carga inicial ─────────────────────────────────────────────────────

    private function carregarMarcas(): void
    {
        $this->marcasIdx = Marca::all(['id', 'nome'])
            ->mapWithKeys(fn($m) => [mb_strtolower($m->nome) => $m->id])
            ->toArray();
    }

    private function carregarEstruturas(): void
    {
        $this->estruturasIdx = Estrutura::all(['id', 'nome'])
            ->mapWithKeys(fn($e) => [mb_strtolower($e->nome) => $e->id])
            ->toArray();
    }

    /** Recarrega os índices (usado após criar novas marcas em lote). */
    public function recarregar(): void
    {
        $this->carregarMarcas();
        $this->carregarEstruturas();
    }
}
