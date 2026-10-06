<?php

namespace App\Http\Controllers\Admin\Configuracoes;

use App\Http\Controllers\Controller;
use App\Models\Config;
use App\Services\IdentidadeVisual;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin → Configurações → Identidade visual: nome da plataforma, rodapé do login,
 * logos, favicon e cores (primária, secundária e menu lateral).
 */
class IdentidadeVisualController extends Controller
{
    private const COR = ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'];

    public function index(): Response
    {
        return Inertia::render('Admin/Configuracoes/IdentidadeVisual/Index', [
            'atual' => IdentidadeVisual::atual(),
            'padrao' => IdentidadeVisual::PADRAO,
            'contraste_minimo' => IdentidadeVisual::CONTRASTE_MINIMO_MENU,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome' => 'required|string|max:40',
            'rodape' => 'nullable|string|max:80',
            'cor_primaria' => self::COR,
            'cor_secundaria' => self::COR,
            'menu_fundo' => self::COR,
            'menu_fonte' => self::COR,
            // SVG fica de fora: pode carregar script e é servido pelo próprio domínio.
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048|dimensions:max_width=2000,max_height=2000',
            'logo_clara' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048|dimensions:max_width=2000,max_height=2000',
            'favicon' => 'nullable|file|mimes:png,ico|max:512',
            'remover_logo' => 'boolean',
            'remover_logo_clara' => 'boolean',
            'remover_favicon' => 'boolean',
        ], [
            '*.regex' => 'Use uma cor no formato #RRGGBB.',
        ], [
            'nome' => 'nome da plataforma', 'cor_primaria' => 'cor primária', 'cor_secundaria' => 'cor secundária',
            'menu_fundo' => 'fundo do menu', 'menu_fonte' => 'fonte do menu', 'logo_clara' => 'logo para fundo claro',
        ]);

        $contraste = IdentidadeVisual::contraste($data['menu_fundo'], $data['menu_fonte']);
        if ($contraste < IdentidadeVisual::CONTRASTE_MINIMO_MENU) {
            return back()->withErrors(['menu_fonte' => "A fonte do menu fica ilegível sobre o fundo escolhido (contraste {$contraste}:1; mínimo "
                .IdentidadeVisual::CONTRASTE_MINIMO_MENU.':1). Escolha cores mais distantes.']);
        }

        $antes = IdentidadeVisual::atual();

        foreach (array_keys(IdentidadeVisual::PADRAO) as $campo) {
            $valor = $data[$campo] ?? null;
            IdentidadeVisual::salvar($campo, str_starts_with((string) $valor, '#') ? strtoupper($valor) : $valor);
        }

        foreach (array_keys(IdentidadeVisual::ARQUIVOS) as $campo) {
            if ($request->hasFile($campo)) {
                $this->trocarArquivo($campo, $request->file($campo)->store(IdentidadeVisual::PASTA, 'public'));
            } elseif ($request->boolean("remover_{$campo}")) {
                $this->trocarArquivo($campo, null);
            }
        }

        IdentidadeVisual::limparCache();
        $this->auditar($request, $antes, IdentidadeVisual::atual());

        return back()->with('success', 'Identidade visual atualizada.');
    }

    /** Volta tudo ao padrão (cores, textos e imagens). */
    public function destroy(Request $request): RedirectResponse
    {
        $antes = IdentidadeVisual::atual();

        foreach (array_keys(IdentidadeVisual::ARQUIVOS) as $campo) {
            $this->trocarArquivo($campo, null);
        }
        Config::where('grupo', IdentidadeVisual::GRUPO)->delete();

        IdentidadeVisual::limparCache();
        $this->auditar($request, $antes, IdentidadeVisual::atual());

        return back()->with('success', 'Identidade visual restaurada para o padrão.');
    }

    private function trocarArquivo(string $campo, ?string $novo): void
    {
        $anterior = IdentidadeVisual::arquivo($campo);
        if ($anterior && $anterior !== $novo) {
            Storage::disk('public')->delete($anterior);
        }
        IdentidadeVisual::salvar($campo, $novo);
    }

    /** Registra na Auditoria só o que mudou. */
    private function auditar(Request $request, array $antes, array $depois): void
    {
        $mudou = array_keys(array_diff_assoc($depois, $antes) + array_diff_assoc($antes, $depois));
        if ($mudou === []) {
            return;
        }

        activity()
            ->causedBy($request->user())
            ->event('updated')
            ->withChanges([
                'attributes' => array_intersect_key($depois, array_flip($mudou)),
                'old' => array_intersect_key($antes, array_flip($mudou)),
            ])
            ->log('Identidade visual alterada');
    }
}
