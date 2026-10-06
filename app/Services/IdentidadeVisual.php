<?php

namespace App\Services;

use App\Models\Config;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Identidade visual da plataforma (Admin → Configurações → Identidade visual): nome, rodapé do login,
 * logos, favicon e cores. Guardada na tabela configs (grupo "identidade"), com cache, e
 * compartilhada com todas as páginas (HandleInertiaRequests), a view raiz, os PDFs e os e-mails.
 */
class IdentidadeVisual
{
    public const GRUPO = 'identidade';

    private const CACHE = 'identidade_visual';

    /** Arquivos ficam no disco público, nesta pasta. */
    public const PASTA = 'identidade';

    public const PADRAO = [
        'nome' => 'CRM Solar',
        'rodape' => 'CRM para energia solar',
        'cor_primaria' => '#2563EB',
        'cor_secundaria' => '#F59E0B',
        'menu_fundo' => '#0F172A',
        'menu_fonte' => '#CBD5E1',
    ];

    /** Imagens: chave da configuração => rótulo. */
    public const ARQUIVOS = ['logo' => 'Logo do menu', 'logo_clara' => 'Logo para fundo claro', 'favicon' => 'Favicon'];

    /** Contraste mínimo entre a fonte e o fundo do menu (WCAG para texto grande/ícones). */
    public const CONTRASTE_MINIMO_MENU = 3.0;

    /**
     * Valores atuais, com URLs públicas das imagens (com versão para o navegador não usar a antiga).
     *
     * @return array<string, string|null>
     */
    public static function atual(): array
    {
        try {
            return self::carregar();
        } catch (QueryException) {
            // Banco ainda não migrado/indisponível: a tela de login continua abrindo com o padrão.
            return self::PADRAO + array_fill_keys(array_map(fn ($c) => "{$c}_url", array_keys(self::ARQUIVOS)), null);
        }
    }

    /** @return array<string, string|null> */
    private static function carregar(): array
    {
        return Cache::rememberForever(self::CACHE, function () {
            $salvos = Config::where('grupo', self::GRUPO)->pluck('valor', 'chave')
                ->mapWithKeys(fn ($valor, $chave) => [substr($chave, strlen(self::GRUPO) + 1) => $valor]);

            $dados = [];
            foreach (self::PADRAO as $campo => $padrao) {
                $dados[$campo] = ($salvos[$campo] ?? '') ?: $padrao;
            }
            foreach (array_keys(self::ARQUIVOS) as $campo) {
                $caminho = $salvos[$campo] ?? null;
                $dados["{$campo}_url"] = $caminho ? Storage::disk('public')->url($caminho).'?v='.substr(md5($caminho), 0, 8) : null;
            }

            return $dados;
        });
    }

    public static function nome(): string
    {
        return self::atual()['nome'];
    }

    /** Caminho no disco público da imagem guardada (null = não definida). */
    public static function arquivo(string $campo): ?string
    {
        return Config::get(self::GRUPO.'.'.$campo) ?: null;
    }

    /** Caminho absoluto da logo para fundo claro (ou da logo do menu), para o dompdf. */
    public static function logoParaPdf(): ?string
    {
        $caminho = self::arquivo('logo_clara') ?? self::arquivo('logo');

        return $caminho && Storage::disk('public')->exists($caminho) ? Storage::disk('public')->path($caminho) : null;
    }

    public static function salvar(string $campo, ?string $valor): void
    {
        Config::set(self::GRUPO.'.'.$campo, $valor, self::GRUPO);
    }

    public static function limparCache(): void
    {
        Cache::forget(self::CACHE);
    }

    /** Razão de contraste WCAG entre duas cores #RRGGBB (1 a 21). */
    public static function contraste(string $cor1, string $cor2): float
    {
        [$l1, $l2] = [self::luminancia($cor1), self::luminancia($cor2)];

        return round((max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05), 2);
    }

    private static function luminancia(string $hex): float
    {
        $canais = array_map(function ($i) use ($hex) {
            $c = hexdec(substr(ltrim($hex, '#'), $i, 2)) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, [0, 2, 4]);

        return 0.2126 * $canais[0] + 0.7152 * $canais[1] + 0.0722 * $canais[2];
    }
}
