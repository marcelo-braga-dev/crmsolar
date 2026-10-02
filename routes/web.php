<?php

use App\Http\Controllers\Admin\ClientesController;
use App\Http\Controllers\Admin\Configuracoes\BancosController;
use App\Http\Controllers\Admin\Configuracoes\ConcessionariasController;
use App\Http\Controllers\Admin\Configuracoes\DimensionamentoController;
use App\Http\Controllers\Admin\Configuracoes\SistemaController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Financeiro\ComissoesController;
use App\Http\Controllers\Admin\Financeiro\FaturamentoController;
use App\Http\Controllers\Admin\FornecedoresController;
use App\Http\Controllers\Admin\Integracoes\AldoController;
use App\Http\Controllers\Admin\Integracoes\EdeltecController;
use App\Http\Controllers\Admin\Integracoes\HistoricoController;
use App\Http\Controllers\Admin\LeadsController;
use App\Http\Controllers\Admin\OrcamentosController;
use App\Http\Controllers\Admin\PerfilController;
use App\Http\Controllers\Admin\Precificacao\PrecificacaoController;
use App\Http\Controllers\Admin\Produtos\CatalogoController;
use App\Http\Controllers\Admin\Produtos\CategoriasController;
use App\Http\Controllers\Admin\Produtos\InversoresController;
use App\Http\Controllers\Admin\Produtos\KitsController;
use App\Http\Controllers\Admin\Produtos\MarcasController;
use App\Http\Controllers\Admin\Produtos\PaineisController;
use App\Http\Controllers\Admin\Produtos\TrafosController;
use App\Http\Controllers\Admin\Usuarios\AdminsController;
use App\Http\Controllers\Admin\Usuarios\ConsultoresController;
use App\Http\Controllers\Api\GeografiaController;
use App\Http\Controllers\Consultor\ContratosController;
use App\Http\Controllers\Consultor\Dimensionamento\ConvencionalController;
use App\Http\Controllers\Consultor\Dimensionamento\DemandaController;
use App\Http\Controllers\Consultor\FinanceiroController;
use App\Http\Controllers\Consultor\GrupoTarifario\GrupoAController;
use App\Http\Controllers\Consultor\GrupoTarifario\GrupoB1Controller;
use App\Http\Controllers\Consultor\GrupoTarifario\GrupoB2Controller;
use App\Http\Controllers\Consultor\GrupoTarifario\GrupoB3Controller;
use App\Http\Controllers\Consultor\OrcamentoItensController;
use App\Http\Controllers\Consultor\PropostasServicosController;
use App\Http\Controllers\Consultor\VisitasController;
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
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('orcamentos', OrcamentosController::class)
        ->only(['index', 'show', 'update']);

    Route::resource('clientes', ClientesController::class);

    Route::resource('leads', LeadsController::class)
        ->only(['index', 'show', 'update']);

    Route::prefix('produtos')->name('produtos.')->group(function () {
        Route::resource('kits', KitsController::class);
        Route::resource('inversores', InversoresController::class)
            ->parameters(['inversores' => 'produto'])->except(['show']);
        Route::resource('paineis', PaineisController::class)
            ->parameters(['paineis' => 'produto'])->except(['show']);
        Route::resource('trafos', TrafosController::class)
            ->parameters(['trafos' => 'produto'])->except(['show']);
        Route::resource('catalogo', CatalogoController::class);
        Route::resource('categorias', CategoriasController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('marcas', MarcasController::class)
            ->only(['index', 'store', 'update', 'destroy']);
    });

    Route::prefix('usuarios')->name('usuarios.')->group(function () {
        Route::resource('consultores', ConsultoresController::class)
            ->parameters(['consultores' => 'consultor'])
            ->except(['show']);
        Route::resource('admins', AdminsController::class)
            ->except(['show']);
    });

    Route::prefix('financeiro')->name('financeiro.')->group(function () {
        Route::resource('comissoes', ComissoesController::class)
            ->only(['index']);
        Route::get('faturamento', [FaturamentoController::class, 'index'])
            ->name('faturamento');
    });

    Route::prefix('precificacao')->name('precificacao.')->group(function () {
        Route::get('/', [PrecificacaoController::class, 'index'])->name('index');
        Route::post('faixas', [PrecificacaoController::class, 'storeFaixa'])->name('faixas.store');
        Route::put('faixas/{faixa}', [PrecificacaoController::class, 'updateFaixa'])->name('faixas.update');
        Route::delete('faixas/{faixa}', [PrecificacaoController::class, 'destroyFaixa'])->name('faixas.destroy');
        Route::post('estados', [PrecificacaoController::class, 'updateEstado'])->name('estados.update');
        Route::post('fornecedores', [PrecificacaoController::class, 'updateFornecedor'])->name('fornecedores.update');
    });

    Route::resource('fornecedores', FornecedoresController::class)
        ->parameters(['fornecedores' => 'fornecedor']);

    Route::prefix('integracoes')->name('integracoes.')->group(function () {
        Route::get('aldo', [AldoController::class, 'index'])->name('aldo');
        Route::post('aldo/integrar', [AldoController::class, 'integrar'])->name('aldo.integrar');
        Route::get('edeltec', [EdeltecController::class, 'index'])->name('edeltec');
        Route::post('edeltec/integrar', [EdeltecController::class, 'integrar'])->name('edeltec.integrar');
        Route::get('historico', [HistoricoController::class, 'index'])->name('historico');
    });

    Route::prefix('configuracoes')->name('configuracoes.')->group(function () {
        Route::resource('bancos', BancosController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('concessionarias', ConcessionariasController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::get('dimensionamento', [DimensionamentoController::class, 'index'])->name('dimensionamento');
        Route::put('dimensionamento', [DimensionamentoController::class, 'update'])->name('dimensionamento.update');
        Route::get('sistema', [SistemaController::class, 'index'])->name('sistema');
        Route::put('sistema', [SistemaController::class, 'update'])->name('sistema.update');
    });

    Route::prefix('perfil')->name('perfil.')->group(function () {
        Route::get('/', [PerfilController::class, 'edit'])->name('edit');
        Route::put('/', [PerfilController::class, 'update'])->name('update');
        Route::get('/senha', [PerfilController::class, 'editSenha'])->name('senha');
        Route::put('/senha', [PerfilController::class, 'updateSenha'])->name('senha.update');
    });
});

// ─────────────────────────────────────────────
// Consultor routes
// ─────────────────────────────────────────────
Route::middleware(['auth', 'consultor'])->prefix('consultor')->name('consultor.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Consultor\DashboardController::class, 'index'])->name('dashboard');

    // Rotas estáticas ANTES do resource para evitar captura pelo parâmetro {orcamento}
    Route::get('orcamentos/selecionar-grupo', fn () => inertia('Consultor/Orcamentos/SelecionarGrupo'))->name('orcamentos.selecionar_grupo');
    Route::get('orcamentos/produtos/buscar', [OrcamentoItensController::class, 'buscarProdutos'])->name('orcamentos.produtos.buscar');

    Route::resource('orcamentos', App\Http\Controllers\Consultor\OrcamentosController::class);
    Route::get('orcamentos/{orcamento}/pdf', [App\Http\Controllers\Consultor\OrcamentosController::class, 'pdf'])->name('orcamentos.pdf');

    // Itens do orçamento
    Route::post('orcamentos/{orcamento}/itens', [OrcamentoItensController::class, 'store'])->name('orcamentos.itens.store');
    Route::delete('orcamentos/{orcamento}/itens/{item}', [OrcamentoItensController::class, 'destroy'])->name('orcamentos.itens.destroy');

    Route::prefix('orcamentos/dimensionamento')->name('dimensionamento.')->group(function () {
        Route::get('convencional', [ConvencionalController::class, 'create'])->name('convencional');
        Route::post('convencional', [ConvencionalController::class, 'store'])->name('convencional.store');
        Route::post('buscar-kits', [ConvencionalController::class, 'buscarKits'])->name('buscar_kits');
        Route::get('demanda', [DemandaController::class, 'create'])->name('demanda');
        Route::post('demanda', [DemandaController::class, 'store'])->name('demanda.store');
        Route::post('demanda/buscar-kits', [DemandaController::class, 'buscarKits'])->name('demanda.buscar_kits');
    });

    // ── Grupos Tarifários ANEEL ────────────────────────────────────────────
    Route::prefix('orcamentos/grupo')->name('grupo.')->group(function () {
        // Grupo B1 — Residencial
        Route::get('b1/create', [GrupoB1Controller::class, 'create'])->name('b1.create');
        Route::post('b1/calcular', [GrupoB1Controller::class, 'calcular'])->name('b1.calcular');
        Route::post('b1', [GrupoB1Controller::class, 'store'])->name('b1.store');
        // Grupo B2 — Rural
        Route::get('b2/create', [GrupoB2Controller::class, 'create'])->name('b2.create');
        Route::post('b2/calcular', [GrupoB2Controller::class, 'calcular'])->name('b2.calcular');
        Route::post('b2', [GrupoB2Controller::class, 'store'])->name('b2.store');
        // Grupo B3 — Comercial BT
        Route::get('b3/create', [GrupoB3Controller::class, 'create'])->name('b3.create');
        Route::post('b3/calcular', [GrupoB3Controller::class, 'calcular'])->name('b3.calcular');
        Route::post('b3', [GrupoB3Controller::class, 'store'])->name('b3.store');
        // Grupo A — Média e Alta Tensão (A4, A3a, A3, A2, A1)
        Route::get('{grupo}/create', [GrupoAController::class, 'create'])->name('a.create')
            ->where('grupo', 'A4|A3a|A3|A2|A1');
        Route::post('{grupo}/calcular', [GrupoAController::class, 'calcular'])->name('a.calcular')
            ->where('grupo', 'A4|A3a|A3|A2|A1');
        Route::post('{grupo}', [GrupoAController::class, 'store'])->name('a.store')
            ->where('grupo', 'A4|A3a|A3|A2|A1');
    });

    Route::resource('clientes', App\Http\Controllers\Consultor\ClientesController::class);

    Route::resource('proposta-servicos', PropostasServicosController::class);

    Route::resource('leads', App\Http\Controllers\Consultor\LeadsController::class)
        ->only(['index', 'show', 'update']);

    Route::resource('visitas', VisitasController::class);

    // Rota estática ANTES do resource para evitar captura pelo parâmetro {contrato}
    Route::get('contratos/criar/{orcamento}', [ContratosController::class, 'create'])->name('contratos.create');

    Route::resource('contratos', ContratosController::class)
        ->only(['index', 'show', 'store']);
    Route::get('contratos/{contrato}/pdf', [ContratosController::class, 'pdf'])->name('contratos.pdf');

    Route::get('financeiro', [FinanceiroController::class, 'index'])->name('financeiro');

    Route::prefix('perfil')->name('perfil.')->group(function () {
        Route::get('/', [App\Http\Controllers\Consultor\PerfilController::class, 'edit'])->name('edit');
        Route::put('/', [App\Http\Controllers\Consultor\PerfilController::class, 'update'])->name('update');
        Route::get('/senha', [App\Http\Controllers\Consultor\PerfilController::class, 'editSenha'])->name('senha');
        Route::put('/senha', [App\Http\Controllers\Consultor\PerfilController::class, 'updateSenha'])->name('senha.update');
    });
});

// ─────────────────────────────────────────────
// Public API (sem autenticação)
// ─────────────────────────────────────────────
Route::prefix('api')->name('api.')->group(function () {
    Route::post('leads', [App\Http\Controllers\Api\LeadsController::class, 'store'])
        ->middleware('throttle:leads-submit')->name('leads.store');
    Route::get('orcamento/{token}', [App\Http\Controllers\Api\OrcamentosController::class, 'show'])
        ->middleware('throttle:orcamento-publico')->name('orcamento.public');
    Route::get('cidades/{estado}', [GeografiaController::class, 'cidades'])
        ->middleware('throttle:public-lookup')->name('cidades');
    Route::get('estados', [GeografiaController::class, 'estados'])
        ->middleware('throttle:public-lookup')->name('estados');
    Route::get('cep/{cep}', [GeografiaController::class, 'buscarCep'])
        ->middleware('throttle:public-lookup')->name('cep');
});

require __DIR__.'/auth.php';
