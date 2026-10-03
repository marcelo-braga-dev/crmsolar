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
      CatalogoController             — CRUD catálogo geral de produtos
      CategoriasController           — CRUD categorias
      InversoresController           — CRUD produtos categoria "inversor"
      KitsController                 — CRUD kits solares (com KitComponente)
      MarcasController               — index, store, update, destroy
      PaineisController              — CRUD produtos categoria "painel"
      TrafosController               — CRUD produtos categoria "trafo"
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

  Api/
    GeografiaController              — GET cep/{cep}, cidades/{estado}, estados
    LeadsController                  — POST leads (formulário público externo)
    OrcamentosController             — GET orcamento/{token} (link público da proposta)
```

### Backend — Models (28)

`Banco`, `CategoriaProduto`, `CidadeEstado`, `Cliente`, `Concessionaria`, `Config`,
`Contrato`, `Estrutura`, `Fornecedor`, `IntegracaoHistorico`, `IrradiacaoSolar`,
`Kit`, `Lead`, `Marca`, `MargemEstado`, `MargemFornecedor`,
`MargemPrincipal`, `Orcamento`, `OrcamentoAprovacao`, `OrcamentoHistorico`,
`OrcamentoInfo`, `OrcamentoItem`, `OrcamentoVistoria`, `ParamDimensionamento`,
`Produto`, `PropostaServico`, `User`, `VisitaTecnica`

### Backend — Services (`app/Services/`)

- **`DimensionamentoService`** — engine de cálculo solar (convencional e demanda). Recebe consumo/demanda + parâmetros → retorna potência do sistema, quantidade de painéis, geração estimada.
- **`GrupoTarifarioService`** — cálculo para todos os grupos ANEEL (B1/B2/B3/A). Recebe tarifa + consumo → retorna análise econômica (payback, TIR, economia mensal).
- **`PrecificacaoService`** — aplica as 3 camadas de margem (principal por faixa de potência → estado → fornecedor) para calcular o preço de venda. A comissão do consultor (`users.comissao_percentual`, gerenciada em Usuarios/Consultores) é registrada no item do orçamento mas não é somada como camada de margem — não infla o preço de venda.
- **`Integracoes/Edeltec/`** — serviço de sincronização de catálogo Edeltec.

### Database — Migrations (38 total)

Todas em `database/migrations/`. Seeders principais:
- `UsersSeeder` — cria admin e consultor de demo
- `EstruturasSeeder` — estruturas de instalação (solo, telhado cerâmico, etc.)
- `CategoriasProdutosSeeder` — 10 categorias de produtos
- `ParamsDimensionamentoSeeder` — parâmetros do motor de cálculo
- `CidadesEstadosSeeder` — municípios brasileiros
- `IrradiacaoSolarSeeder` — dados de irradiação por município
- `DadosTesteSeeder` — clientes, leads e orçamentos fictícios

Campos críticos do schema:
- `orcamentos.status` enum: `novo | aprovando | aprovado | aprovacao_reprovada | instalando | finalizado`
- `orcamentos.grupo_tarifario` enum: `B1 | B2 | B3 | A4 | A3a | A3 | A2 | A1`
- `orcamentos.modalidade_tarifaria` enum: `convencional | THS_VERDE | THS_AZUL` — só relevante pro Grupo A (Horo-Sazonal Verde/Azul)
- `orcamento_infos.bloquear_edicao` boolean — impede edição pelo consultor quando true
- `users.tipo` enum: `admin | consultor`

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
      Catalogo/{Index,Form,Show}.tsx
      Inversores/{Index,Form}.tsx
      Kits/{Index,Form,Show}.tsx
      Marcas/Index.tsx               — inline edit/delete (sem página própria)
      Paineis/{Index,Form}.tsx
      Trafos/{Index,Form}.tsx
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
      Sistema/Index.tsx
    Integracoes/
      Edeltec/Index.tsx
      Historico/Index.tsx

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
```
Acesso: `usePage<PageProps>().props`

## Status atual do desenvolvimento

**Plataforma em desenvolvimento.** O servidor `crmsolar.rexar.com.br` roda com `APP_ENV=production`, mas **todos os dados do banco são de teste** — não há dados reais de clientes. Credenciais fracas de seed (`1020`) são aceitáveis enquanto durar essa fase; **trocar antes do go-live**.

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

**345 testes, todos passando** (`php artisan test`, SQLite em memória — não toca no banco real). Testes legados do Breeze (Registration/Profile) foram removidos.

- `tests/Concerns/CriaDados.php` — construtores de dados (`admin()`, `consultor()`, `cliente()`, `orcamento()`, `kit()`, `produto()`…). O projeto só tem `UserFactory`; use o trait em vez de repetir `Model::create`.
- Testes estruturais: `ControleDeAcessoTest` (matriz papel × tela), `IntegridadeDasRotasTest` (método existe + nome do parâmetro bate), `PaginasInertiaExistemTest` (todo `Inertia::render` tem `.tsx`).
- Fluxo completo de orçamento por grupo (B1/B2/B3/A4/Convencional/Demanda): `Consultor/FluxoOrcamentoPorGrupoTest`.
- PHPUnit 12: data provider só com atributo `#[DataProvider('metodo')]` — a annotation `@dataProvider` é ignorada (o teste roda sem argumentos e quebra).
- Para rodar os testes de um commit isolado (simular o CI), use `git archive HEAD` numa pasta e **copie** o `vendor` — com symlink o autoload do Composer resolve para o projeto original e testa o working tree, não o commit.
- Laravel desliga CSRF em testes — mudanças em rotas públicas POST precisam ser conferidas com `curl`.
- `actingAs($user)` usa o objeto em memória: atributos com default só no banco (ex.: `comissao_percentual`) chegam `null` se não forem passados no `create`.

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

### Pendências conhecidas (funcionalidade)
- **Entradas de "Novo orçamento"** — Dashboard e ficha do cliente levam ao fluxo Convencional (com opção de kWp direto); a lista de Orçamentos leva à seleção de grupo. Decidir se unifica

### Problemas encontrados na análise (2026-10-02) — corrigir antes do go-live

#### 🟡 Qualidade
- Nenhum item pendente da análise.

#### 🔵 Melhorias recomendadas
- Valores monetários com centavos inteiros ou `bcmath` em vez de `float`.
- Sincronização Edeltec via fila (`QUEUE_CONNECTION=database` já configurado). **Pré-requisito:** este servidor não tem worker de fila para o crmsolar — configurar `queue:work` (supervisor/aaPanel) antes, senão os jobs nunca rodam.
- Ativar Sentry (`SENTRY_LARAVEL_DSN`) e `SESSION_ENCRYPT=true`. Subir o nível do Larastan aos poucos (hoje 5).

**Prioridade:** qualidade → melhorias.
