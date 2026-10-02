<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Todo componente passado para Inertia::render()/inertia() precisa existir em resources/js/Pages.
 * Página ausente = tela em branco em produção (o backend responde 200).
 */
class PaginasInertiaExistemTest extends TestCase
{
    public function test_todo_componente_renderizado_tem_arquivo_tsx(): void
    {
        $faltando = collect(File::allFiles(app_path()))
            ->merge(File::allFiles(base_path('routes')))
            ->flatMap(fn ($arquivo) => preg_match_all(
                "/(?:Inertia::render|inertia)\\(\\s*'([^']+)'/",
                $arquivo->getContents(),
                $m,
            ) ? $m[1] : [])
            ->unique()
            ->reject(fn ($componente) => File::exists(resource_path("js/Pages/{$componente}.tsx")))
            ->values()
            ->all();

        $this->assertSame([], $faltando, 'Componentes Inertia sem arquivo: '.implode(', ', $faltando));
    }
}
