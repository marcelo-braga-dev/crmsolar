<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/dashboard', function () {
    return Auth::user()?->isAdmin()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('consultor.dashboard');
})->middleware('auth')->name('dashboard');

// ─────────────────────────────────────────────
// Admin routes
// ─────────────────────────────────────────────
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::resource('orcamentos', \App\Http\Controllers\Admin\OrcamentosController::class)
        ->only(['index', 'show', 'edit', 'update']);

    Route::resource('clientes', \App\Http\Controllers\Admin\ClientesController::class);

    Route::resource('leads', \App\Http\Controllers\Admin\LeadsController::class)
        ->only(['index', 'show', 'update']);

    Route::prefix('produtos')->name('produtos.')->group(function () {
        Route::resource('kits', \App\Http\Controllers\Admin\Produtos\KitsController::class);
        Route::resource('catalogo', \App\Http\Controllers\Admin\Produtos\CatalogoController::class);
        Route::resource('categorias', \App\Http\Controllers\Admin\Produtos\CategoriasController::class);
        Route::resource('marcas', \App\Http\Controllers\Admin\Produtos\MarcasController::class)
            ->only(['index', 'store', 'update', 'destroy']);
    });

    Route::prefix('usuarios')->name('usuarios.')->group(function () {
        Route::resource('consultores', \App\Http\Controllers\Admin\Usuarios\ConsultoresController::class);
        Route::resource('admins', \App\Http\Controllers\Admin\Usuarios\AdminsController::class);
    });

    Route::prefix('financeiro')->name('financeiro.')->group(function () {
        Route::resource('comissoes', \App\Http\Controllers\Admin\Financeiro\ComissoesController::class)
            ->only(['index', 'edit', 'update']);
        Route::get('faturamento', [\App\Http\Controllers\Admin\Financeiro\FaturamentoController::class, 'index'])
            ->name('faturamento');
    });

    Route::prefix('precificacao')->name('precificacao.')->group(function () {
        Route::resource('margem-principal', \App\Http\Controllers\Admin\Precificacao\MargemPrincipalController::class);
        Route::resource('estados', \App\Http\Controllers\Admin\Precificacao\EstadosController::class);
        Route::resource('consultores', \App\Http\Controllers\Admin\Precificacao\ConsultoresController::class);
        Route::resource('estruturas', \App\Http\Controllers\Admin\Precificacao\EstruturasController::class);
        Route::resource('fornecedores', \App\Http\Controllers\Admin\Precificacao\FornecedoresController::class);
    });

    Route::resource('fornecedores', \App\Http\Controllers\Admin\FornecedoresController::class);

    Route::prefix('integracoes')->name('integracoes.')->group(function () {
        Route::get('aldo', [\App\Http\Controllers\Admin\Integracoes\AldoController::class, 'index'])->name('aldo');
        Route::post('aldo/integrar', [\App\Http\Controllers\Admin\Integracoes\AldoController::class, 'integrar'])->name('aldo.integrar');
        Route::get('edeltec', [\App\Http\Controllers\Admin\Integracoes\EdeltecController::class, 'index'])->name('edeltec');
        Route::post('edeltec/integrar', [\App\Http\Controllers\Admin\Integracoes\EdeltecController::class, 'integrar'])->name('edeltec.integrar');
        Route::get('historico', [\App\Http\Controllers\Admin\Integracoes\HistoricoController::class, 'index'])->name('historico');
    });

    Route::prefix('configuracoes')->name('configuracoes.')->group(function () {
        Route::resource('bancos', \App\Http\Controllers\Admin\Configuracoes\BancosController::class);
        Route::resource('concessionarias', \App\Http\Controllers\Admin\Configuracoes\ConcessionariasController::class);
        Route::get('dimensionamento', [\App\Http\Controllers\Admin\Configuracoes\DimensionamentoController::class, 'index'])->name('dimensionamento');
        Route::put('dimensionamento', [\App\Http\Controllers\Admin\Configuracoes\DimensionamentoController::class, 'update'])->name('dimensionamento.update');
        Route::get('sistema', [\App\Http\Controllers\Admin\Configuracoes\SistemaController::class, 'index'])->name('sistema');
        Route::put('sistema', [\App\Http\Controllers\Admin\Configuracoes\SistemaController::class, 'update'])->name('sistema.update');
    });

    Route::prefix('perfil')->name('perfil.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PerfilController::class, 'edit'])->name('edit');
        Route::put('/', [\App\Http\Controllers\Admin\PerfilController::class, 'update'])->name('update');
        Route::get('/senha', [\App\Http\Controllers\Admin\PerfilController::class, 'editSenha'])->name('senha');
        Route::put('/senha', [\App\Http\Controllers\Admin\PerfilController::class, 'updateSenha'])->name('senha.update');
    });
});

// ─────────────────────────────────────────────
// Consultor routes
// ─────────────────────────────────────────────
Route::middleware(['auth', 'consultor'])->prefix('consultor')->name('consultor.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Consultor\DashboardController::class, 'index'])->name('dashboard');

    // Rotas estáticas ANTES do resource para evitar captura pelo parâmetro {orcamento}
    Route::get('orcamentos/selecionar-grupo', fn() => inertia('Consultor/Orcamentos/SelecionarGrupo'))->name('orcamentos.selecionar_grupo');
    Route::get('orcamentos/produtos/buscar', [\App\Http\Controllers\Consultor\OrcamentoItensController::class, 'buscarProdutos'])->name('orcamentos.produtos.buscar');

    Route::resource('orcamentos', \App\Http\Controllers\Consultor\OrcamentosController::class);
    Route::post('orcamentos/{orcamento}/pdf', [\App\Http\Controllers\Consultor\OrcamentosController::class, 'pdf'])->name('orcamentos.pdf');

    // Itens do orçamento
    Route::post('orcamentos/{orcamento}/itens', [\App\Http\Controllers\Consultor\OrcamentoItensController::class, 'store'])->name('orcamentos.itens.store');
    Route::delete('orcamentos/{orcamento}/itens/{item}', [\App\Http\Controllers\Consultor\OrcamentoItensController::class, 'destroy'])->name('orcamentos.itens.destroy');

    Route::prefix('orcamentos/dimensionamento')->name('dimensionamento.')->group(function () {
        Route::get('convencional', [\App\Http\Controllers\Consultor\Dimensionamento\ConvencionalController::class, 'create'])->name('convencional');
        Route::post('convencional', [\App\Http\Controllers\Consultor\Dimensionamento\ConvencionalController::class, 'store'])->name('convencional.store');
        Route::post('buscar-kits', [\App\Http\Controllers\Consultor\Dimensionamento\ConvencionalController::class, 'buscarKits'])->name('buscar_kits');
        Route::get('demanda', [\App\Http\Controllers\Consultor\Dimensionamento\DemandaController::class, 'create'])->name('demanda');
        Route::post('demanda', [\App\Http\Controllers\Consultor\Dimensionamento\DemandaController::class, 'store'])->name('demanda.store');
        Route::post('demanda/buscar-kits', [\App\Http\Controllers\Consultor\Dimensionamento\DemandaController::class, 'buscarKits'])->name('demanda.buscar_kits');
    });

    // ── Grupos Tarifários ANEEL ────────────────────────────────────────────
    Route::prefix('orcamentos/grupo')->name('grupo.')->group(function () {
        // Grupo B1 — Residencial
        Route::get('b1/create',     [\App\Http\Controllers\Consultor\GrupoTarifario\GrupoB1Controller::class, 'create'])->name('b1.create');
        Route::post('b1/calcular',  [\App\Http\Controllers\Consultor\GrupoTarifario\GrupoB1Controller::class, 'calcular'])->name('b1.calcular');
        Route::post('b1',           [\App\Http\Controllers\Consultor\GrupoTarifario\GrupoB1Controller::class, 'store'])->name('b1.store');
        // Grupo B2 — Rural
        Route::get('b2/create',     [\App\Http\Controllers\Consultor\GrupoTarifario\GrupoB2Controller::class, 'create'])->name('b2.create');
        Route::post('b2/calcular',  [\App\Http\Controllers\Consultor\GrupoTarifario\GrupoB2Controller::class, 'calcular'])->name('b2.calcular');
        Route::post('b2',           [\App\Http\Controllers\Consultor\GrupoTarifario\GrupoB2Controller::class, 'store'])->name('b2.store');
        // Grupo B3 — Comercial BT
        Route::get('b3/create',     [\App\Http\Controllers\Consultor\GrupoTarifario\GrupoB3Controller::class, 'create'])->name('b3.create');
        Route::post('b3/calcular',  [\App\Http\Controllers\Consultor\GrupoTarifario\GrupoB3Controller::class, 'calcular'])->name('b3.calcular');
        Route::post('b3',           [\App\Http\Controllers\Consultor\GrupoTarifario\GrupoB3Controller::class, 'store'])->name('b3.store');
        // Grupo A — Média e Alta Tensão (A4, A3a, A3, A2, A1)
        Route::get('{grupo}/create',    [\App\Http\Controllers\Consultor\GrupoTarifario\GrupoAController::class, 'create'])->name('a.create')
            ->where('grupo', 'A4|A3a|A3|A2|A1');
        Route::post('{grupo}/calcular', [\App\Http\Controllers\Consultor\GrupoTarifario\GrupoAController::class, 'calcular'])->name('a.calcular')
            ->where('grupo', 'A4|A3a|A3|A2|A1');
        Route::post('{grupo}',          [\App\Http\Controllers\Consultor\GrupoTarifario\GrupoAController::class, 'store'])->name('a.store')
            ->where('grupo', 'A4|A3a|A3|A2|A1');
    });

    Route::resource('clientes', \App\Http\Controllers\Consultor\ClientesController::class);

    Route::resource('proposta-servicos', \App\Http\Controllers\Consultor\PropostasServicosController::class);

    Route::resource('leads', \App\Http\Controllers\Consultor\LeadsController::class)
        ->only(['index', 'show', 'update']);

    Route::resource('visitas', \App\Http\Controllers\Consultor\VisitasController::class);

    Route::resource('contratos', \App\Http\Controllers\Consultor\ContratosController::class)
        ->only(['index', 'show', 'store']);
    Route::post('contratos/{contrato}/pdf', [\App\Http\Controllers\Consultor\ContratosController::class, 'pdf'])->name('contratos.pdf');

    Route::get('financeiro', [\App\Http\Controllers\Consultor\FinanceiroController::class, 'index'])->name('financeiro');

    Route::prefix('perfil')->name('perfil.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Consultor\PerfilController::class, 'edit'])->name('edit');
        Route::put('/', [\App\Http\Controllers\Consultor\PerfilController::class, 'update'])->name('update');
        Route::get('/senha', [\App\Http\Controllers\Consultor\PerfilController::class, 'editSenha'])->name('senha');
        Route::put('/senha', [\App\Http\Controllers\Consultor\PerfilController::class, 'updateSenha'])->name('senha.update');
    });
});

// ─────────────────────────────────────────────
// Public API (sem autenticação)
// ─────────────────────────────────────────────
Route::prefix('api')->name('api.')->group(function () {
    Route::post('leads', [\App\Http\Controllers\Api\LeadsController::class, 'store'])->name('leads.store');
    Route::get('orcamento/{token}', [\App\Http\Controllers\Api\OrcamentosController::class, 'show'])->name('orcamento.public');
    Route::get('cidades/{estado}', [\App\Http\Controllers\Api\GeografiaController::class, 'cidades'])->name('cidades');
    Route::get('estados', [\App\Http\Controllers\Api\GeografiaController::class, 'estados'])->name('estados');
    Route::get('cep/{cep}', [\App\Http\Controllers\Api\GeografiaController::class, 'buscarCep'])->name('cep');
});

require __DIR__.'/auth.php';
