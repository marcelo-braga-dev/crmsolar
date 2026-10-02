<?php

namespace App\Services\Integracoes\Edeltec;

use App\Models\Fornecedor;
use App\Models\IntegracaoHistorico;
use App\Models\Kit;
use Illuminate\Support\Facades\Log;

/**
 * Orquestra a importação completa do catálogo Edeltec.
 *
 * Fluxo:
 *   1. Autentica via EdeltecApiClient (token cacheado)
 *   2. Pagina os produtos da API usando Generator (baixo uso de memória)
 *   3. Mapeia campos API → modelo Kit via EdeltecMapeamentos
 *   4. Upsert em lotes (bulk) — sem N+1
 *   5. Desativa SKUs que sumiram do catálogo do fornecedor
 *   6. Registra histórico com estatísticas e alertas
 */
class EdeltecImportService
{
    private const BATCH_SIZE = 200; // kits por upsert

    private EdeltecApiClient $api;

    private EdeltecMapeamentos $map;

    private int $fornecedorId;

    /** Acumuladores para o histórico */
    private array $skusImportados = [];

    private array $alertas = [];

    private int $qtdImportados = 0;

    private int $qtdAtualizados = 0;

    public function __construct()
    {
        $this->api = new EdeltecApiClient;
        $this->map = new EdeltecMapeamentos;
    }

    // ── Entrada pública ───────────────────────────────────────────────────

    /**
     * Executa a integração completa. Cria um registro de histórico e retorna-o.
     */
    public function importar(): IntegracaoHistorico
    {
        $fornecedor = Fornecedor::where('nome', 'like', '%edeltec%')
            ->orWhere('nome', 'like', '%Edeltec%')
            ->firstOrFail();

        $this->fornecedorId = $fornecedor->id;

        $historico = IntegracaoHistorico::create([
            'fornecedor_id' => $this->fornecedorId,
            'tipo' => 'edeltec',
            'status' => 'iniciado',
            'iniciado_em' => now(),
        ]);

        try {
            $this->executar();

            $qtdDesativados = $this->desativarSKUsRemovidos();

            $historico->update([
                'status' => 'concluido',
                'finalizado_em' => now(),
                'itens_importados' => $this->qtdImportados,
                'itens_atualizados' => $this->qtdAtualizados,
                'itens_desativados' => $qtdDesativados,
                'alertas' => empty($this->alertas) ? null : implode(PHP_EOL, $this->alertas),
                'detalhes' => [
                    'skus_importados' => $this->skusImportados,
                    'total_paginas' => $this->qtdImportados > 0
                        ? (int) ceil($this->qtdImportados / 1000)
                        : 0,
                ],
            ]);

        } catch (\Throwable $e) {
            Log::error('Edeltec: falha crítica na integração', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            $historico->update([
                'status' => 'erro',
                'finalizado_em' => now(),
                'alertas' => 'ERRO CRÍTICO: '.$e->getMessage(),
            ]);

            throw $e;
        }

        return $historico->fresh();
    }

    // ── Processamento ─────────────────────────────────────────────────────

    private function executar(): void
    {
        $batch = [];

        foreach ($this->api->produtos() as $produto) {
            $sku = trim($produto['codProd'] ?? '');
            if (! $sku) {
                continue;
            }

            try {
                $row = $this->mapearProduto($produto);
                if ($row === null) {
                    continue;
                } // skip inválido

                $batch[] = $row;
                $this->skusImportados[] = $sku;

                if (count($batch) >= self::BATCH_SIZE) {
                    $this->upsertBatch($batch);
                    $batch = [];
                }
            } catch (\Throwable $e) {
                $msg = "SKU {$sku}: ".$e->getMessage();
                $this->alertas[] = $msg;
                Log::warning('Edeltec: produto ignorado', ['sku' => $sku, 'erro' => $e->getMessage()]);
            }
        }

        // Flush do batch restante
        if (! empty($batch)) {
            $this->upsertBatch($batch);
        }
    }

    /**
     * Mapeia um item da API para a estrutura do modelo Kit.
     * Retorna null se o produto não puder ser importado (campo obrigatório inválido).
     */
    private function mapearProduto(array $p): ?array
    {
        $sku = trim($p['codProd'] ?? '');
        $potenciaRaw = $p['potenciaGerador'] ?? $p['potencia'] ?? '0';
        $potencia = $this->map->parsePotenciaKwp($potenciaRaw);

        if ($potencia <= 0) {
            $this->alertas[] = "SKU {$sku}: potência inválida ({$potenciaRaw}) — ignorado.";

            return null;
        }

        $preco = $this->map->parsePreco((string) ($p['precoDoIntegrador'] ?? '0'));
        $tensao = $this->map->parseTensao((string) ($p['tensaoSaida'] ?? '220'));
        $estrutura = isset($p['estrutura']) ? $this->map->resolveEstrutura($p['estrutura']) : null;

        // Nome: remove sufixo " edeltec" que a API inclui
        $nome = preg_replace('/\s+edeltec\s*$/i', '', trim($p['titulo'] ?? $sku));

        return [
            'fornecedor_id' => $this->fornecedorId,
            'estrutura_id' => $estrutura,
            'nome' => $nome,
            'modelo' => $nome,
            'sku' => $sku,
            'categoria' => $this->resolverCategoria($p),
            'potencia_kwp' => $potencia,
            'tensao' => in_array($tensao, [127, 220, 380]) ? $tensao : 220,
            'preco_custo' => $preco,
            'ativo' => true,
            'ativo_fornecedor' => true,
            'observacoes' => $p['componentes'] ?? null,
            'updated_at' => now(),
        ];
    }

    /**
     * Resolve a categoria do kit a partir dos campos da API.
     * A Edeltec informa o tipo via campo "tipo" (ex: "GERADOR FOTOVOLTAICO")
     * ou via nome/título. Padrão: ongrid.
     */
    private function resolverCategoria(array $p): string
    {
        $tipo = mb_strtolower(trim($p['tipo'] ?? $p['categoria'] ?? ''));
        $titulo = mb_strtolower(trim($p['titulo'] ?? $p['nome'] ?? ''));
        $texto = $tipo.' '.$titulo;

        return match (true) {
            str_contains($texto, 'off') || str_contains($texto, 'off-grid') => 'offgrid',
            str_contains($texto, 'hibrido') || str_contains($texto, 'híbrido')
                || str_contains($texto, 'hybrid') => 'hibrido',
            str_contains($texto, 'microinversor') || str_contains($texto, 'micro') => 'microinversor',
            str_contains($texto, 'bomba') || str_contains($texto, 'pump') => 'bomba',
            default => 'ongrid',
        };
    }

    /**
     * Upsert em lote: INSERT ... ON DUPLICATE KEY UPDATE.
     * Separa inserções de atualizações para manter contadores precisos.
     */
    private function upsertBatch(array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        $skus = array_column($rows, 'sku');

        // IDs que já existem (para separar inserções de updates)
        $existentes = Kit::whereIn('sku', $skus)
            ->where('fornecedor_id', $this->fornecedorId)
            ->pluck('sku')
            ->flip()
            ->toArray();

        foreach ($rows as $row) {
            if (! isset($row['created_at'])) {
                $row['created_at'] = isset($existentes[$row['sku']]) ? null : now();
            }
        }

        // Upsert nativo do Laravel — uma única query
        Kit::upsert(
            $rows,
            ['sku'],           // coluna de conflito
            [                  // colunas a atualizar se já existir
                'nome', 'modelo', 'estrutura_id', 'categoria', 'potencia_kwp',
                'tensao', 'preco_custo', 'ativo', 'ativo_fornecedor',
                'observacoes', 'updated_at',
            ]
        );

        $novos = count($rows) - count($existentes);
        $atualizados = count($existentes);

        $this->qtdImportados += $novos;
        $this->qtdAtualizados += $atualizados;

        Log::debug('Edeltec: batch de '.count($rows)." — {$novos} novos, {$atualizados} atualizados.");
    }

    /**
     * Marca como `ativo_fornecedor = false` todos os kits do fornecedor
     * que não vieram na importação atual (foram removidos do catálogo).
     */
    private function desativarSKUsRemovidos(): int
    {
        if (empty($this->skusImportados)) {
            return 0;
        }

        return Kit::where('fornecedor_id', $this->fornecedorId)
            ->where('ativo_fornecedor', true)
            ->whereNotIn('sku', $this->skusImportados)
            ->update([
                'ativo_fornecedor' => false,
                'updated_at' => now(),
            ]);
    }
}
