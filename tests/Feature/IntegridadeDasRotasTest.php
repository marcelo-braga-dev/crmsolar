<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use ReflectionMethod;
use ReflectionNamedType;
use Tests\TestCase;

/**
 * Protege contra dois erros silenciosos de Route::resource():
 *  - rota apontando para método inexistente (500 ao acessar);
 *  - parâmetro da URL com nome diferente do argumento do controller
 *    (ex.: {consultore} × $consultor) — o binding falha e chega um model vazio,
 *    então update()/delete() "funcionam" sem fazer nada.
 */
class IntegridadeDasRotasTest extends TestCase
{
    /** @return array<int, array{string, string, string}> [uri, classe, método] */
    private function rotasDaAplicacao(): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => is_string($r->getAction('uses')) && str_starts_with($r->getAction('uses'), 'App\\'))
            ->map(fn ($r) => [$r->uri(), ...explode('@', $r->getAction('uses'))])
            ->values()
            ->all();
    }

    public function test_toda_rota_aponta_para_metodo_existente(): void
    {
        $quebradas = collect($this->rotasDaAplicacao())
            ->reject(fn ($r) => method_exists($r[1], $r[2]))
            ->map(fn ($r) => "{$r[0]} → {$r[1]}@{$r[2]}")
            ->values()
            ->all();

        $this->assertSame([], $quebradas, "Rotas sem método:\n".implode("\n", $quebradas));
    }

    public function test_parametros_de_model_batem_com_argumentos_do_controller(): void
    {
        $divergentes = [];

        foreach ($this->rotasDaAplicacao() as [$uri, $classe, $metodo]) {
            if (! method_exists($classe, $metodo)) {
                continue; // coberto pelo teste acima
            }

            preg_match_all('/\{(\w+)\??\}/', $uri, $m);
            $parametrosUrl = $m[1];

            $argsModel = collect((new ReflectionMethod($classe, $metodo))->getParameters())
                ->filter(fn ($p) => $p->getType() instanceof ReflectionNamedType
                    && is_subclass_of($p->getType()->getName(), Model::class))
                ->map(fn ($p) => $p->getName());

            foreach ($argsModel as $arg) {
                // O Laravel também aceita a versão snake_case ($propostaServico ↔ {proposta_servico}).
                if (! in_array($arg, $parametrosUrl, true) && ! in_array(Str::snake($arg), $parametrosUrl, true)) {
                    $divergentes[] = "{$uri} → {$classe}@{$metodo}(\${$arg})";
                }
            }
        }

        $this->assertSame([], $divergentes, "Argumentos de model sem parâmetro correspondente na URL:\n".implode("\n", $divergentes));
    }
}
