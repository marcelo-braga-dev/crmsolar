<?php

namespace App\Services\Demo;

use App\Models\DemoVisitante;
use Illuminate\Database\Eloquent\Builder;

/** Cabeçalho e linhas da exportação de visitantes (CSV da rota e comando demo:visitantes). */
class ExportacaoVisitantes
{
    /** @return array<int, string> */
    public static function cabecalho(): array
    {
        return ['Nome', 'E-mail', 'Telefone', 'Empresa', 'Primeiro acesso', 'Último acesso', 'Visitas', 'Telas vistas',
            'Perfis usados', 'utm_source', 'utm_medium', 'utm_campaign', 'IP'];
    }

    /** @return array<int, string|int|null> */
    public static function linha(DemoVisitante $v): array
    {
        $fuso = config('app.timezone_exibicao');

        return [
            $v->nome, $v->email, $v->telefone, $v->empresa,
            $v->primeiro_acesso_em?->setTimezone($fuso)->format('d/m/Y H:i'),
            $v->ultimo_acesso_em?->setTimezone($fuso)->format('d/m/Y H:i'),
            $v->visitas, $v->telas_vistas,
            collect($v->perfis_vistos ?? [])->map(fn ($p) => ModoDemonstracao::PERFIS[$p] ?? $p)->implode(', '),
            $v->utm_source, $v->utm_medium, $v->utm_campaign, $v->ip,
        ];
    }

    /** @return Builder<DemoVisitante> */
    public static function consulta(?string $desde = null): Builder
    {
        return DemoVisitante::query()
            ->when($desde, fn ($q, $d) => $q->where('ultimo_acesso_em', '>=', $d))
            ->orderByDesc('ultimo_acesso_em');
    }

    /** Grava o CSV (separador ";", BOM UTF-8 para o Excel mostrar os acentos) no recurso aberto. */
    public static function escrever($saida, ?string $desde = null): void
    {
        fwrite($saida, "\xEF\xBB\xBF");
        fputcsv($saida, self::cabecalho(), ';');
        self::consulta($desde)->lazy(200)->each(fn (DemoVisitante $v) => fputcsv($saida, self::linha($v), ';'));
    }
}
