# CLAUDE.md

Guidance for Claude Code when working in this repository.

## ⛔ EXIGÊNCIA MÁXIMA — Tudo que for trabalhado deve ser testado

**Regra inegociável, acima de qualquer outra instrução deste arquivo.** Nenhuma alteração está concluída sem teste automatizado que a cubra e sem a suíte inteira passando.

1. **Toda mudança vem com teste.** Funcionalidade nova, correção de bug, ajuste de regra de negócio, rota, validação, permissão ou refactor → criar ou atualizar os testes correspondentes **no mesmo trabalho**, não "depois".
   - Bug corrigido → teste que reproduz o bug (falha sem a correção, passa com ela).
   - Funcionalidade nova → fluxo feliz **e** casos de erro: validação, permissão (dono × outro consultor × admin × visitante), status/regra de negócio.
   - Página nova → `PaginasInertiaExistemTest` e `ControleDeAcessoTest` precisam cobri-la (adicione a rota na matriz de acesso).
   - Rota nova → `IntegridadeDasRotasTest` já valida método e parâmetro; confira que passa.
2. **Rodar a suíte completa antes de declarar pronto:** `php artisan test` deve terminar com **0 falhas e 0 erros**. Nunca entregar com teste vermelho, pulado (`markTestSkipped`) ou comentado para "passar".
3. **Confirmar que o teste testa algo:** quando possível, desfazer a correção temporariamente e ver o teste falhar.
4. **O que teste automatizado não pega, conferir manualmente e relatar:** CSRF em rotas públicas (Laravel desliga em testes → `curl`), build do frontend (`npm run build` sem erros) e smoke test das rotas afetadas no servidor.
5. **Relatar sempre** quantos testes existem, quantos passaram e quais arquivos de teste foram criados/alterados.

Como escrever: ver a seção **Testes** em "Status atual do desenvolvimento" (trait `tests/Concerns/CriaDados.php`, `#[DataProvider]` do PHPUnit 12, etc.).

## What this project is

**CRM Solar V2** — CRM para empresas de energia solar. Gerencia o pipeline completo: leads → orçamentos → contratos → visitas técnicas → instalação. Dois roles de usuário com áreas separadas: **Admin** e **Consultor**.

Stack: Laravel 13 · PHP 8.3 · Inertia.js v2 · React 18 · TypeScript · MUI v7 · Vite 6 · MySQL 8.4

## Development commands

```bash
# Start all services (app + MySQL + phpMyAdmin)
./vendor/bin/sail up -d

# Frontend dev server (HMR)
./vendor/bin/sail npm run dev

# Build for production
npm run build

# Run migrations + seed
./vendor/bin/sail artisan migrate --seed

# Artisan inside Sail
./vendor/bin/sail artisan <command>

# Run tests
./vendor/bin/sail artisan test
```

### Deploy no servidor `crmsolar.rexar.com.br` (sem Sail)

O servidor roda direto no host (aaPanel, PHP 8.4, usuário `www`). Rodar **como `www`** — se rodar como root, `chown -R www:www storage bootstrap/cache public/build node_modules` no fim, senão o PHP não consegue recriar cache e views compiladas.

```bash
git pull
php artisan optimize:clear       # OBRIGATÓRIO antes dos testes (ver aviso abaixo)
php artisan test                 # só seguir com 0 falhas
php artisan migrate --force
npm ci && npm run build          # quando package.json/lock mudar, ou sempre que houver mudança no frontend
php artisan optimize:clear && php artisan optimize
```

> ⚠️ **Nunca rode `php artisan test` com a configuração em cache** (`bootstrap/cache/config.php`, gerado pelo `optimize`). O cache ignora o `phpunit.xml`: os testes rodam no MySQL real e o `RefreshDatabase` **apaga o banco** (aconteceu em 2026-10-06). `tests/TestCase.php` agora aborta nesse caso. Para testar sem desfazer o cache do servidor: `APP_CONFIG_CACHE=/tmp/x.php APP_ROUTES_CACHE=/tmp/y.php APP_EVENTS_CACHE=/tmp/z.php php artisan test`. Backups diários do banco: `/www/backup/database/mysql/crontab_backup/crmsolar/`.

Depois: smoke test com login de admin e consultor nas telas afetadas e `curl -X POST -H 'Accept: application/json' .../api/leads` (espera 422, não 419). O agendador já está no crontab (`schedule:run` a cada minuto, como `www`); **não há worker `queue:work`**.

CI (`.github/workflows/ci.yml`): Pint (`--test`), Larastan, `php artisan test`, `npx tsc --noEmit` e `npm run build`.

## Service ports (Sail defaults)

| Service    | Port  | Env var             |
|------------|-------|---------------------|
| App        | 80    | `APP_PORT`          |
| MySQL      | 3306  | `FORWARD_DB_PORT`   |
| phpMyAdmin | 8080  | `PHPMYADMIN_PORT`   |
| Vite HMR   | 5173  | `VITE_PORT`         |

## Seed users

| Email                    | Senha    | Tipo      |
|--------------------------|----------|-----------|
| admin@teste.com          | 1020     | admin     |
| consultor@teste.com      | 1020     | consultor |

### Base de demonstração (marketing) — `DEMO.md`

`MarketingDemoSeeder` cria, numa **instalação separada** (banco próprio), uma empresa fictícia operando há 16 meses: equipe, clientes, leads, orçamentos pelo `FunilService`, contratos, visitas, pós-venda, auditoria e integrações, com datas relativas a hoje e dados marcados como fictícios. Só roda com `DEMO_SEED_PERMITIDO=true` (`config('app.demo_seed_permitido')`) — **nunca no servidor de desenvolvimento**. Logins `@crmsolar.demo`, senha `demo@2026`. Ao mudar regra de negócio, fluxo de status ou o que uma tela consulta, confira se o simulador (`database/seeders/Support/SimuladorComercial.php`) continua coerente — `MarketingDemoSeederTest` pega quebras.

## Architecture

### Role system — ATENÇÃO

Dois tipos de usuário no enum `users.tipo`:

- `admin` → área `/admin/*`, middleware `EnsureUserIsAdmin`
- `consultor` → área `/consultor/*`, middleware `EnsureUserIsConsultor`

**NUNCA usar "vendedor"** — o role foi renomeado para "consultor" nesta versão.

### Route structure (`routes/web.php`)

```
/                      → redirect → login
/dashboard             → redirect → admin.dashboard ou consultor.dashboard
/admin/*               → middleware: auth, admin
/consultor/*           → middleware: auth, consultor
/api/*                 → público (sem auth)
/auth/*                → Breeze (auth.php) — login, senha; sem cadastro público
```

### Backend — Controllers

```
app/Http/Controllers/
  Admin/
    DashboardController              — KPIs, gráficos, top consultores
    ClientesController               — CRUD clientes (Admin)
    LeadsController                  — index, show, update (status)
    OrcamentosController             — index, show, update (status validado por Orcamento::TRANSICOES)
    FornecedoresController           — CRUD fornecedores
    PerfilController                 — edit, update, senha
    Produtos/
      CatalogoController             — página única de produtos avulsos: abas Produtos (atalhos por categoria via ?categoria=slug), Categorias e Marcas (?aba=)
      CategoriasController           — store, update, destroy (aba do Catálogo)
      KitsController                 — CRUD kits solares (sistemas completos usados no dimensionamento)
      MarcasController               — store, update, destroy (aba do Catálogo)
    Precificacao/
      PrecificacaoController         — página única: margem principal (faixas de potência), margem por estado, margem por fornecedor + simulador
    Usuarios/
      AdminsController               — CRUD admins
      ConsultoresController          — CRUD consultores
    Financeiro/
      ComissoesController            — listagem de comissões
      FaturamentoController          — relatório de faturamento
    Configuracoes/
      AuditoriaController            — consulta do log de auditoria (activitylog)
      BancosController               — CRUD bancos (para contratos)
      ConcessionariasController      — CRUD concessionárias de energia
      DimensionamentoController      — parâmetros do motor de cálculo
      FunilVendasController          — etapas do Kanban, motivos de perda e parâmetros do funil
      IdentidadeVisualController     — nome da plataforma, rodapé do login, logos, favicon e cores
      SistemaController              — configurações gerais
    Integracoes/
      EdeltecController              — integração Edeltec
      HistoricoController            — log de sincronizações

  Consultor/
    DashboardController              — KPIs, evolução, pipeline, orçamentos recentes
    ClientesController               — CRUD clientes (Consultor)
    LeadsController                  — index, show, update
    OrcamentosController             — index, show, create→redirect, edit, update, destroy
    OrcamentoItensController         — store/destroy itens avulsos, buscarProdutos
    ContratosController              — index, show, store, pdf
    FinanceiroController             — extrato de comissões
    PerfilController                 — edit, update, senha
    PropostasServicosController      — CRUD propostas de serviços avulsos
    VisitasController                — CRUD visitas técnicas
    Dimensionamento/
      ConvencionalController         — form + cálculo + seleção de kit (Grupo B convencional)
      DemandaController              — form + cálculo + seleção de kit (por demanda)
    GrupoTarifario/
      BaseGrupoController            — lógica compartilhada entre grupos
      GrupoAController               — Média/Alta Tensão (A1, A2, A3, A3a, A4)
      GrupoB1Controller              — Residencial BT
      GrupoB2Controller              — Rural BT
      GrupoB3Controller              — Comercial/Industrial BT

  FunilController                    — quadro Kanban do funil de vendas (Admin e Consultor, mesma classe) — docs/funil-de-vendas.md

  Api/
    GeografiaController              — GET cep/{cep}, cidades/{estado}, estados
    LeadsController                  — POST leads (formulário público externo)
    OrcamentosController             — GET orcamento/{token} (link público da proposta)
```

### Backend — Models (30)

`Banco`, `CategoriaProduto`, `CidadeEstado`, `Cliente`, `Concessionaria`, `Config`,
`Contrato`, `Estrutura`, `Fornecedor`, `FunilEtapa`, `IntegracaoHistorico`, `IrradiacaoSolar`,
`Kit`, `Lead`, `Marca`, `MargemEstado`, `MargemFornecedor`, `MotivoPerda`,
`MargemPrincipal`, `Orcamento`, `OrcamentoAprovacao`, `OrcamentoHistorico`,
`OrcamentoInfo`, `OrcamentoItem`, `OrcamentoVistoria`, `ParamDimensionamento`,
`Produto`, `PropostaServico`, `User`, `VisitaTecnica`

### Backend — Services (`app/Services/`)

- **`DimensionamentoService`** — engine de cálculo solar (convencional e demanda). Recebe consumo/demanda + parâmetros → retorna potência do sistema, quantidade de painéis, geração estimada.
- **`GrupoTarifarioService`** — cálculo para todos os grupos ANEEL (B1/B2/B3/A). Recebe tarifa + consumo → retorna análise econômica (payback, TIR, economia mensal).
- **`PrecificacaoService`** — aplica as 3 camadas de margem (principal por faixa de potência → estado → fornecedor) para calcular o preço de venda. A comissão do consultor (`users.comissao_percentual`, gerenciada em Usuarios/Consultores) é registrada no item do orçamento mas não é somada como camada de margem — não infla o preço de venda.
- **`Integracoes/Edeltec/`** — serviço de sincronização de catálogo Edeltec. Roda pelo botão em Integrações → Edeltec, pelo comando `app:integracao-edeltec` (agendado diariamente às 04h00 em `routes/console.php`, log em `storage/logs/edeltec.log`) ou pelo job `App\Jobs\SincronizarEdeltec` (quando `EDELTEC_SYNC_FILA=true`).
- **`Funil/FunilService`** — funil de vendas: em que coluna cada orçamento aparece (colunas de sistema derivadas do `status`) e todas as movimentações (mover, aprovar/reprovar, perder, reativar, follow-up). **Especificação completa: `docs/funil-de-vendas.md`.**

### Database — Migrations (40 total)

Todas em `database/migrations/`. Seeders principais:
- `UsersSeeder` — cria admin e consultor de demo
- `EstruturasSeeder` — estruturas de instalação (solo, telhado cerâmico, etc.)
- `CategoriasProdutosSeeder` — 10 categorias de produtos
- `ParamsDimensionamentoSeeder` — parâmetros do motor de cálculo
- `CidadesEstadosSeeder` — municípios brasileiros
- `IrradiacaoSolarSeeder`, `IrradiacaoSolarComplementoSeeder`, `IrradiacaoSolarTodosSeeder` — dados de irradiação por município
- `ConcessionariasSeeder` — concessionárias e tarifas
- `DadosTesteSeeder` — clientes, leads e orçamentos fictícios

Campos críticos do schema:
- `orcamentos.status` enum: `novo | aprovando | aprovado | aprovacao_reprovada | instalando | finalizado`
- `orcamentos.grupo_tarifario` enum: `B1 | B2 | B3 | A4 | A3a | A3 | A2 | A1`
- `orcamentos.modalidade_tarifaria` enum: `convencional | THS_VERDE | THS_AZUL` — só relevante pro Grupo A (Horo-Sazonal Verde/Azul)
- `orcamento_infos.bloquear_edicao` boolean — impede edição pelo consultor quando true
- `users.tipo` enum: `admin | consultor`
- Funil: `orcamentos.funil_etapa_id` (só etapas abertas; Em aprovação/Ganho vêm do `status`), `perdido_em` + `motivo_perda_id` (perda **não** é status), `proximo_contato_em`, `etapa_entrou_em` (aging, reiniciado pelo model quando a coluna muda). `orcamento_historicos.tipo`: `status | etapa | contato | perda | reativacao` (eventos do funil têm `status` null)

### Frontend — Pages

```
resources/js/Pages/
  Auth/                              — Login, ForgotPassword, ResetPassword, etc.

  Admin/
    Dashboard.tsx                    — KPIs + gráficos (Recharts) + tabela orçamentos
    Clientes/{Index,Form,Show}.tsx
    Leads/{Index,Show}.tsx
    Orcamentos/{Index,Show}.tsx
    Fornecedores/{Index,Form,Show}.tsx
    Perfil/{Edit,Senha}.tsx
    Produtos/
      Catalogo/{Index,Form,Show}.tsx — Index com abas Produtos / Categorias / Marcas
      Kits/{Index,Form,Show}.tsx
    Precificacao/
      Index.tsx                      — página única: faixas de margem principal, margem por estado, margem por fornecedor e simulador de preço em tempo real
    Usuarios/
      Admins/{Index,Form}.tsx
      Consultores/{Index,Form}.tsx
    Financeiro/
      Comissoes/Index.tsx
      Faturamento/Index.tsx
    Configuracoes/
      Bancos/Index.tsx
      Concessionarias/Index.tsx
      Auditoria/Index.tsx            — log de auditoria com filtros
      Dimensionamento/Index.tsx      — parâmetros do motor de cálculo
      Funil/Index.tsx                — etapas, cores, motivos de perda e parâmetros do funil
      IdentidadeVisual/Index.tsx     — identidade visual com pré-visualização ao vivo
      Sistema/Index.tsx
    Integracoes/
      Edeltec/Index.tsx
      Historico/Index.tsx

  Funil/
    Index.tsx                        — quadro Kanban do funil (Admin e Consultor, prop `area`)

  Consultor/
    Dashboard.tsx                    — KPIs + evolução + pipeline + orçamentos recentes
    Clientes/{Index,Form,Show}.tsx   — Form tem CEP lookup via /api/cep/{cep}
    Leads/{Index,Show}.tsx
    Orcamentos/
      Index.tsx                      — lista com CTA de novo orçamento
      SelecionarGrupo.tsx            — escolha do grupo tarifário ANEEL
      Create.tsx                     — dimensionamento convencional (legacy, redireciona)
      GrupoB1.tsx                    — form dimensionamento Residencial
      GrupoB2.tsx                    — form dimensionamento Rural
      GrupoB3.tsx                    — form dimensionamento Comercial BT
      GrupoA.tsx                     — form dimensionamento Média/Alta Tensão
      Show.tsx                       — detalhes, itens, histórico, ações
      Edit.tsx                       — edição de anotações (proposta + técnicas)
    Contratos/{Index,Create,Show}.tsx
    Financeiro/Index.tsx             — extrato de comissões do consultor
    Perfil/{Edit,Senha}.tsx
    PropostasServicos/{Index,Form,Show}.tsx
    Visitas/{Index,Form,Show}.tsx
```

### Frontend — Components

```
resources/js/
  Components/
    Sidebar/
      Sidebar.tsx                    — sidebar persistente (lg+) / temporária (mobile)
      navConfig.tsx                  — arrays adminNav e consultorNav
    TopBar/
      TopBar.tsx                     — app bar com avatar, user menu
    UI/
      AnaliseEconomica.tsx           — card de análise econômica (payback, TIR, economia)
      ConfirmDialog.tsx              — dialog de confirmação genérico
      KpiCard.tsx                    — card de KPI com ícone e tendência
      EnderecoFields.tsx             — endereço com busca de CEP e estado → cidade (forms de cliente)
      MaskedTextField.tsx            — campo com máscara (CPF, CNPJ, CEP, telefone)
      PageHeader.tsx                 — title + breadcrumbs + action slot (named export!)
      StatusChip.tsx                 — OrcamentoStatusChip, LeadStatusChip, BoolChip
      TablePagination.tsx            — paginação padrão para tabelas
    Produtos/
      CategoriasAba.tsx, MarcasAba.tsx — abas do Catálogo de produtos
    Funil/                           — CardOrcamento, ColunaFunil, CaixaEntrada, PainelOrcamento (painel lateral), Dialogos, tipos.ts (quadro Kanban, @dnd-kit/core)
  Layouts/
    AppLayout.tsx                    — sidebar + topbar + flash snackbar
    GuestLayout.tsx                  — layout de autenticação (split-screen escuro)
  types/index.d.ts                   — tipos TypeScript (User, PageProps, modelos)
  theme.ts                           — tema MUI (cores, overrides de componentes)
```

### Design system

- **Font**: Inter (`@fontsource/inter`)
- **Sidebar**: fundo `#0F172A`, 260px, persistente em `lg+`
- **Primary**: `#2563EB` (azul), **Secondary**: `#F59E0B` (âmbar/solar)
- **Background**: `#F1F5F9` (slate-100)
- **Cards**: branco, `border-radius: 12px`, borda `1px #E2E8F0`

## Padrões críticos — NÃO quebrar

### Grid MUI v2
```tsx
// CORRETO — Grid v2
<Grid size={{ xs: 12, md: 6 }}>

// ERRADO — Grid v1 (não usar)
<Grid item xs={12} md={6}>
```

### PageHeader é named export
```tsx
import { PageHeader } from '@/Components/UI/PageHeader';  // correto
import PageHeader from '@/Components/UI/PageHeader';        // errado
```

### BoolChip
```tsx
<BoolChip value={true} />  // correto — só aceita { value: boolean }
```

### useForm com tipos simples
```tsx
// Correto — sem nested objects no genérico
const { data, setData } = useForm({ nome: '', valor: 0 });

// Ao precisar de PUT com dados extras, usar router.put() diretamente
router.put(route('...'), { ...data, status: 'aprovando' });
```

### CEP lookup
```
GET /api/cep/{cep}
→ { logradouro, bairro, cidade, estado, cidade_id }
```

### Shared Inertia props (toda página recebe)
```typescript
auth.user: { id, name, email, tipo: 'admin' | 'consultor', status }
flash: { success?, error?, warning?, info? }
identidade: { nome, rodape, cor_primaria, cor_secundaria, menu_fundo, menu_fonte, logo_url, logo_clara_url, favicon_url }
```

### Identidade visual (Admin → Configurações → Identidade visual)
- Guardada em `configs` (grupo `identidade`) e lida por `App\Services\IdentidadeVisual::atual()` (com cache — limpe com `limparCache()` ao gravar). Imagens no disco `public`, pasta `identidade/` (SVG recusado: pode conter script).
- O tema MUI é montado por `criarTema(identidade)` (`resources/js/theme.ts`) e trocado a cada navegação em `app.tsx`. **Não fixe cores da marca em componentes**: use `theme.palette.primary/secondary` e `theme.palette.sidebar.{bg,text,textMuted,strong,active,hover,border}`; nome/logos via `useIdentidade()` (`resources/js/hooks/useIdentidade.ts`).
- Também aplicada na view raiz (título e favicon), na tela de login, no cabeçalho dos PDFs (`IdentidadeVisual::logoParaPdf()`) e em `config('app.name')` (e-mails), pelo `AppServiceProvider`.
- Contraste mínimo de 3:1 entre fonte e fundo do menu (validado no servidor e na tela). Alterações vão para a Auditoria.
Acesso: `usePage<PageProps>().props`

## Status atual do desenvolvimento

**Plataforma em desenvolvimento.** O servidor `crmsolar.rexar.com.br` é o ambiente de desenvolvimento (em 2026-10-05 o `.env` estava com `APP_ENV=local` e `APP_DEBUG=false` — definir `APP_ENV=production` antes do go-live) e **todos os dados do banco são de teste** — não há dados reais de clientes. Credenciais fracas de seed (`1020`) são aceitáveis enquanto durar essa fase; **trocar antes do go-live**.

Trabalho de atualização integrado à `main`.

### Já resolvido nesta rodada
- PDF de orçamento e contrato — `barryvdh/laravel-dompdf` instalado, views em `resources/views/pdf/{orcamento,contrato}.blade.php`, `OrcamentosController::pdf` e `ContratosController::pdf` geram PDF de verdade
- **`POST /api/leads` dava 419 (CSRF)** — rota excluída do CSRF em `bootstrap/app.php` (`validateCsrfTokens(except: ['api/leads'])`). Obs.: testes não pegam isso (Laravel desliga CSRF em testes) — conferir com `curl -X POST .../api/leads`
- **`GET /api/orcamento/{token}` vazava CPF/RG, custo, margem e comissão** — agora passa por `App\Http\Resources\OrcamentoPublicoResource` (whitelist). Teste: `tests/Feature/Api/OrcamentoPublicoTest.php`
- **IDOR em `cliente_id`** nos controllers de `GrupoTarifario/*` e `Dimensionamento/*` — validação agora é `Rule::exists('clientes','id')->where('consultor_id', ...)`. Teste: `tests/Feature/Consultor/ClienteEscopoOrcamentoTest.php`
- **Erros de validação em requisições axios voltavam como redirect 302 (HTML)** — `shouldRenderJsonWhen` só cobria `api/*`; agora inclui `$request->expectsJson()`. Afetava os endpoints `calcular`/`buscar-kits`
- **Análise econômica com valores do navegador** — `BaseGrupoController::kitSelecionado()` calcula preço/geração do kit no servidor; os `store` dos grupos ignoram `geracao_estimada`/`preco_venda` do request. Teste: `AnaliseEconomicaServidorTest`
- **Contrato** — `ContratosController::store` exige orçamento `aprovado`, não duplica (lock + retorna o existente) e grava `valor_total = orcamento.preco_total` (campo desabilitado no form). Testes em `ContratoCreationTest`
- **Itens avulsos** — `OrcamentoItemStoreRequest`: produto do catálogo precisa estar ativo e não pode ser vendido abaixo do `preco_custo`; item sem produto vira `personalizado` e exige descrição. Store/destroy respeitam `bloquear_edicao` (destroy também exige status `novo`). Teste: `OrcamentoItensTest`
- **Usuário inativo** — bloqueado no login (`LoginRequest`) e sessões existentes derrubadas pelo middleware `EnsureUserIsActive` (grupo `web`). `User::$attributes` tem `status => true` para espelhar o default da coluna. Teste: `Auth/UsuarioInativoTest`

- **Cadastro público `/register` estava aberto** — qualquer pessoa virava consultor ativo. Rotas removidas de `routes/auth.php` (usuários são criados pelo Admin). O código do cadastro e do perfil do Breeze foi removido
- **Editar/excluir consultores e fornecedores nunca funcionou** — `Route::resource` gerava `{consultore}`/`{fornecedore}`, o binding falhava e chegava model vazio (salvar/excluir davam "sucesso" sem fazer nada). Corrigido com `->parameters([...])`. `IntegridadeDasRotasTest` agora pega esse tipo de erro e rotas apontando para métodos inexistentes (havia 11, removidas com `only`/`except`)
- **Financeiro usava status `'assinado'`** (que é status de *contrato*, não de orçamento) e Comissões filtrava `tipo = vendedor` — Faturamento, Comissões e Financeiro do consultor nunca contavam orçamentos aprovados. Agora `['aprovado', 'instalando', 'finalizado']`, igual aos dashboards
- **Consultores** — opção "Admin + Consultor" (`admin_consultor`, fora do enum) removida do form; tipo é sempre `consultor`; `comissao_percentual` vazio vira 0; não exclui consultor com clientes/orçamentos; rotas de consultor não operam sobre admins e vice-versa; admin não exclui nem desativa a si mesmo
- **Status de orçamento** — consultor só envia para aprovação a partir de `novo`/`aprovacao_reprovada`; update respeita `bloquear_edicao` e não apaga `anotacoes`; mudança de status pelo Admin grava `OrcamentoHistorico`
- **Outros**: página `Consultor/Visitas/Show.tsx` não existia (link da listagem quebrava) — criada; rota `admin.orcamentos.edit` sem página — removida; busca do catálogo anulava filtros (`orWhere` sem agrupar); kit sem fornecedor e concessionária sem tarifas de ponta davam 500 (colunas NOT NULL); fornecedor com kits não pode ser excluído; lead/cliente do Admin só podem ser atribuídos a consultor; "leads abertos" usava `em_negociacao` (fora do enum); fluxos legados Convencional/Demanda também gravavam `geracao_estimada` do navegador; migration de `proposta_servicos` não ajustava o enum no SQLite (status `aceita` quebrava nos testes)

### Testes

**466 testes, todos passando** (`php artisan test`, SQLite em memória — não toca no banco real **desde que a configuração não esteja em cache**; ver aviso no Deploy). Testes legados do Breeze (Registration/Profile) foram removidos.

- `tests/Concerns/CriaDados.php` — construtores de dados (`admin()`, `consultor()`, `cliente()`, `orcamento()`, `kit()`, `produto()`…). O projeto só tem `UserFactory`; use o trait em vez de repetir `Model::create`.
- Testes estruturais: `ControleDeAcessoTest` (matriz papel × tela), `IntegridadeDasRotasTest` (método existe + nome do parâmetro bate), `PaginasInertiaExistemTest` (todo `Inertia::render` tem `.tsx`).
- Fluxo completo de orçamento por grupo (B1/B2/B3/A4/Convencional/Demanda): `Consultor/FluxoOrcamentoPorGrupoTest`.
- PHPUnit 12: data provider só com atributo `#[DataProvider('metodo')]` — a annotation `@dataProvider` é ignorada (o teste roda sem argumentos e quebra).
- Para rodar os testes de um commit isolado (simular o CI), use `git archive HEAD` numa pasta e **copie** o `vendor` — com symlink o autoload do Composer resolve para o projeto original e testa o working tree, não o commit.
- Laravel desliga CSRF em testes — mudanças em rotas públicas POST precisam ser conferidas com `curl`.
- `actingAs($user)` usa o objeto em memória: atributos com default só no banco (ex.: `comissao_percentual`) chegam `null` se não forem passados no `create`.
- Seeders precisam rodar em MySQL **e** SQLite: use `Schema::disableForeignKeyConstraints()` (não `SET FOREIGN_KEY_CHECKS`). `MarketingDemoSeederTest` roda a base de demonstração inteira em SQLite (~25 s, com `MarketingDemoSeeder::$escala` reduzida).

### Resolvido na segunda rodada
- **Inversores, Painéis e Transformadores** — rotas, menu e `ProdutosPorCategoriaController` (base comum). Usam as categorias do seeder (`inversor-solar`, `painel-solar`, `transformador`) e só editam produtos da própria categoria
- **Endereço do cliente** — componente `Components/UI/EnderecoFields.tsx` (CEP → endereço, estado → cidade) nos formulários do Admin e do Consultor. O form do Admin não tinha cidade (cliente não podia ser dimensionado); a edição agora recebe `cidade.sigla`
- **Integração Aldo removida** (descontinuada): controller, página, rotas, menu, testes, fornecedor e seus kits/produtos de teste, tabela `integracao_aldo_mapeamentos` e o tipo `aldo` do histórico (migration `2026_10_02_000000_remove_integracao_aldo`). **Só a Edeltec é integrada**
- **Fluxos Convencional/Demanda** — exigem e gravam `grupo_tarifario` (B1/B2/B3 ou A4–A1)
- **Transições de status** — `Orcamento::TRANSICOES` + `podeIrPara()`; Admin só vê/aplica destinos permitidos; Consultor só envia para aprovação a partir de `novo`/`aprovacao_reprovada`
- **Validação dos fluxos de orçamento** — FormRequests em `app/Http/Requests/Consultor/Dimensionamento/` (base `DimensionamentoRequest`: a mesma classe valida cálculo e `*.store`, que acrescenta `kit_id`/anotações). THS Azul agora é exigido também ao salvar
- **Fluxos Convencional/Demanda** estendem `BaseGrupoController` (usam `mapearKits` e `salvarOrcamento`)
- **Precificação** carrega as margens uma vez por requisição (antes 3 queries por kit); busca de produtos exige 2+ caracteres
- **Auditoria** — `spatie/activitylog` também em margens (3 camadas), `Kit`/`Produto` (só `updated`/`deleted` de preço e disponibilidade — a sincronização cria milhares), `OrcamentoItem`, `Concessionaria`, `ParamDimensionamento` e `User` (nome, e-mail, tipo, status, comissão; nunca senha). Tela em **Configurações → Auditoria** (`AuditoriaController`). Atualizações em massa via query builder (`Model::where()->update()`) **não disparam auditoria** — atualize pelo model
- **Integração Edeltec nunca funcionou** — 3 bugs: (1) `EdeltecApiClient` reatribuía propriedades `readonly` promovidas no construtor → `Error` em toda execução; (2) renovação de token no meio da paginação reenviava o token velho (closure capturava `$token` por valor); (3) `kits.sku` não tinha índice único, então o `upsert` nunca atualizava e cada sincronização duplicaria o catálogo — agora índice único `(fornecedor_id, sku)`. Testes com API simulada: `Services/EdeltecApiClientTest`, `Admin/IntegracaoEdeltecTest`. Obs.: a sincronização usa `upsert` (sem eventos de model), então preços vindos da Edeltec ficam no histórico da integração, não na Auditoria kit a kit
- **Larastan nível 5 no CI** (`phpstan.neon`, 0 erros). Relações dos models com tipo genérico (`@return BelongsTo<Cliente, $this>`) — mantenha o padrão em relações novas, senão o Larastan enxerga só `Model`
- **Cliente excluído sumia dos próprios orçamentos/visitas/propostas** (soft delete fazia a relação voltar `null`; a tela de contrato ficava sem nome e documento) — relações `cliente()` agora usam `withTrashed()`. Teste: `Consultor/ClienteExcluidoTest`
- **Verificação de e-mail do Breeze removida** — rotas sem efeito (User não implementa `MustVerifyEmail`, nenhuma rota usa `verified`)
- **Dependências com alertas de segurança** atualizadas (Guzzle, league/commonmark e outras; `composer audit` limpo)
- **Exclusões com vínculo** — fornecedor com histórico de integração é bloqueado; erro de FK em qualquer DELETE vira aviso em vez de 500 (`bootstrap/app.php`)

### Resolvido na terceira rodada
- **Orçamento salvava kit inativo** — a busca só oferece kits `ativo`/`ativo_fornecedor`, mas o `store` aceitava qualquer `kit_id`. `DimensionamentoRequest` agora aplica o mesmo filtro. Teste: `FluxoOrcamentoPorGrupoTest::test_salvar_recusa_kit_que_a_busca_nao_oferece`
- **Margem principal fora de faixa caía na faixa mais alta** (menor margem) — lacuna entre faixas usa a última faixa que começa abaixo da potência; abaixo da primeira faixa usa a primeira. Cadastro recusa faixas sobrepostas (compartilhar o limite, ex. 0–10 e 10–20, é permitido). Testes em `PrecificacaoServiceTest` e `Admin/PrecificacaoTest`
- **Orçamento com contrato podia voltar para `aprovando`/`novo`** e ter itens e preço alterados — `Orcamento::transicoesPermitidas()` remove os status pré-aprovação quando há contrato não cancelado. Testes em `TransicoesStatusTest`
- `npm audit fix` (axios, form-data, qs e outras) — `npm audit` limpo
- **Contrato gravava potência/geração/consumo digitados no formulário** — agora vêm do orçamento aprovado (potência = soma dos kits, geração do orçamento, consumo do `OrcamentoInfo`), como já era com `valor_total`; o formulário só preenche o que o orçamento não tiver (campos desabilitados na tela). Testes em `ContratoCreationTest`
- **Sincronização Edeltec em fila (opcional)** — `EDELTEC_SYNC_FILA=true` faz o botão "Integrar" enfileirar o job `App\Jobs\SincronizarEdeltec` em vez de rodar na requisição (padrão `false` = comportamento antigo). **Só ative depois de configurar o worker `queue:work`** no servidor. `EdeltecImportService::importar()` usa a trava `integracao-edeltec` (cache, expira em 1h): botão, job e comando agendado nunca rodam ao mesmo tempo. Testes em `IntegracaoEdeltecTest`
- **Listas com mais de uma página quebravam (React #60)** — botões de paginação usavam `Button` do MUI com `dangerouslySetInnerHTML`; rótulos agora são texto via `rotuloPaginacao()` (`Components/UI/TablePagination.tsx`)
- **Cadastro manual de kit dava erro 500** — tensão era texto livre ("220V / 380V") para coluna inteira obrigatória. Agora tensão (`Kit::TENSOES`) e tipo de sistema (`Kit::CATEGORIAS`, antes ausente do form — todo kit manual virava on-grid) são selects obrigatórios; SKU único por fornecedor validado (antes o índice único dava 500); preço de custo obrigatório. `margem_padrao` saiu das telas de kit (não entra no preço — só as 3 camadas de Precificação). Testes em `Admin/ProdutosTest`
- **Produtos: regras únicas** — `App\Http\Requests\Admin\ProdutoRequest` (categoria e custo obrigatórios, SKU único até 60, garantia em texto)
- **Menu Produtos reduzido de 7 para 2 submenus** (Kits Solares, Catálogo). Painéis/Inversores/Transformadores eram o mesmo catálogo filtrado — viraram atalhos de categoria na aba Produtos; Categorias e Marcas viraram abas. Endereços antigos redirecionam (rotas `admin.produtos.*.antigo`). Testes em `Admin/CatalogoUnificadoTest`

### Funil de vendas (Kanban) — Fase 1 entregue
- Especificação, regras e arquivos em **`docs/funil-de-vendas.md`** — leia antes de mexer em status de orçamento, etapas ou no quadro.
- Regra de ouro: o `status` operacional não mudou (financeiro/comissões continuam iguais). Colunas Em aprovação e Ganho são **derivadas** do status; nunca grave etapa de sistema em `funil_etapa_id`.
- Datas: app em UTC; navegador envia ISO com fuso; textos do servidor usam `config('app.timezone_exibicao')` (`APP_TIMEZONE_EXIBICAO`, padrão `America/Sao_Paulo`).
- Testes em `tests/Feature/Funil/` (+ `tests/Concerns/CriaFunil.php`).
- **Evolução da experiência** (seção 22 da doc): saúde do card (`card.saude`, calculada no servidor), painel lateral (`GET funil/{orcamento}`), WhatsApp/ligar, filtros `sem_passo`/`sla`/`ordem`, desfazer, mobile em abas, e no admin reatribuir consultor e ações em lote. Reatribuir troca o `comissao_percentual` dos itens para o do novo consultor.

### Pendências conhecidas (funcionalidade)
- **Funil — Fase 2:** aba Recuperação (novos parados, negócios acima do SLA, perdidos reativáveis — usa `motivos_perda.reativar_apos_dias` e `funil.max_tentativas_reativacao`), métricas do funil e "contatos de hoje" no dashboard. **Fase 3:** atividades detalhadas e automações. Ver `docs/funil-de-vendas.md`, seção 19
- **Entradas de "Novo orçamento"** — Dashboard e ficha do cliente levam ao fluxo Convencional (com opção de kWp direto); a lista de Orçamentos e o Funil levam à seleção de grupo. Decidir se unifica

### Problemas encontrados na análise (2026-10-02) — corrigir antes do go-live

#### 🟡 Qualidade
- Nenhum item pendente da análise.

#### 🔵 Melhorias recomendadas
- Valores monetários com centavos inteiros ou `bcmath` em vez de `float`.
- Ativar a sincronização Edeltec em fila: configurar o worker `queue:work` (supervisor/aaPanel) e só então definir `EDELTEC_SYNC_FILA=true`.
- Ativar Sentry (`SENTRY_LARAVEL_DSN`) e `SESSION_ENCRYPT=true`. Subir o nível do Larastan aos poucos (hoje 5).

**Prioridade:** qualidade → melhorias.
