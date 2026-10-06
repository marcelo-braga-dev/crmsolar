<?php

namespace Database\Seeders\Support;

use Illuminate\Support\Str;

/**
 * Formatos impossíveis de confundir com dados reais (DEMO.md): documentos começando com
 * zeros e derivados do id, telefones com DDD 20 (inexistente) e prefixo 0000, e-mails em
 * domínio da demo, nomes com o sufixo "(fictício)/(fictícia)". Usado pelo simulador e pelo
 * DemoDadosFicticiosSeeder, que reaplica tudo de forma idempotente.
 */
final class Ficticio
{
    public const SUFIXO_M = ' (fictício)';

    public const SUFIXO_F = ' (fictícia)';

    public const RECEBEDOR = 'EMPRESA FICTICIA';

    public static function cpf(int $id): string
    {
        return sprintf('000.%03d.%03d-%02d', intdiv($id, 1000) % 1000, $id % 1000, $id % 97);
    }

    public static function cnpj(int $id): string
    {
        return sprintf('00.000.%03d/%04d-%02d', $id % 1000, intdiv($id, 1000) + 1, $id % 97);
    }

    /** CNPJ de fornecedor (único no banco): filial 0009, faixa diferente da dos clientes. */
    public static function cnpjFornecedor(int $id): string
    {
        return sprintf('00.000.%03d/0009-%02d', $id % 1000, $id % 97);
    }

    public static function rg(int $id): string
    {
        return sprintf('00.000.%03d-%d', $id % 1000, $id % 10);
    }

    /** DDD 20 não existe no Brasil; o prefixo 0000 também não é atribuído. */
    public static function telefone(int $id, int $variacao = 0): string
    {
        return sprintf('(20) 0000-%04d', ($id * 7 + $variacao * 3571) % 10000);
    }

    public static function email(string $nome, int $id, string $dominio): string
    {
        $base = Str::slug(Str::before($nome, ' ('), '.') ?: 'contato';

        return Str::limit($base, 30, '')."{$id}@{$dominio}";
    }

    public static function jaMarcado(?string $nome): bool
    {
        return $nome !== null && (str_contains($nome, '(fictício)') || str_contains($nome, '(fictícia)'));
    }

    /** Nome com o sufixo de dado fictício (empresas e nomes femininos levam "fictícia"). */
    public static function marcar(?string $nome, bool $feminino): ?string
    {
        if ($nome === null || $nome === '' || self::jaMarcado($nome)) {
            return $nome;
        }

        return $nome.($feminino ? self::SUFIXO_F : self::SUFIXO_M);
    }

    public static function nomeFeminino(string $nome): bool
    {
        return in_array(Str::before($nome, ' '), CatalogoDemo::NOMES_F, true);
    }
}
