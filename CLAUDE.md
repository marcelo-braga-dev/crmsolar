# CLAUDE.md

Guidance for Claude Code when working in this repository.

## What this project is

**AppSolar V2** — CRM para empresas de energia solar. Gerencia o pipeline completo: leads → orçamentos → contratos → visitas técnicas → instalação. Dois roles de usuário com áreas separadas: **Admin** e **Consultor**.

Stack: Laravel 12 · PHP 8.3 · Inertia.js v2 · React 18 · TypeScript · MUI v7 · Vite 6 · MySQL 8.4

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
| admin@appsolar.com       | 10203040 | admin     |
| consultor@appsolar.com   | 10203040 | consultor |

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
/auth/*                → Breeze (auth.php)
```

### Backend — Controllers

```
app/Http/Controllers/
  Admin/
    DashboardController              — KPIs, gráficos, top consultores
    ClientesController               — CRUD clientes (Admin)
    LeadsController                  — index, show, update (status)
    OrcamentosController             — index, show, edit, update
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
      MargemPrincipalController      — margem base do sistema
      EstadosController              — margem por estado
      ConsultoresController          — margem por consultor
      EstruturasController           — margem por tipo de estrutura
      FornecedoresController         — margem por fornecedor
    Usuarios/
      AdminsController               — CRUD admins
      ConsultoresController          — CRUD consultores
    Financeiro/
      ComissoesController            — listagem/edição de comissões
      FaturamentoController          — relatório de faturamento
    Configuracoes/
      BancosController               — CRUD bancos (para contratos)
      ConcessionariasController      — CRUD concessionárias de energia
      DimensionamentoController      — parâmetros do motor de cálculo
      SistemaController              — configurações gerais
    Integracoes/
      AldoController                 — integração Aldo (catálogo ZIP/XML)
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

### Backend — Models (29)

`Banco`, `CategoriaProduto`, `CidadeEstado`, `Cliente`, `Concessionaria`, `Config`,
`Contrato`, `Estrutura`, `Fornecedor`, `IntegracaoHistorico`, `IrradiacaoSolar`,
`Kit`, `Lead`, `Marca`, `MargemEstado`, `MargemEstrutura`, `MargemFornecedor`,
`MargemPrincipal`, `Orcamento`, `OrcamentoAprovacao`, `OrcamentoHistorico`,
`OrcamentoInfo`, `OrcamentoItem`, `OrcamentoVistoria`, `ParamDimensionamento`,
`Produto`, `PropostaServico`, `User`, `VisitaTecnica`

### Backend — Services (`app/Services/`)

- **`DimensionamentoService`** — engine de cálculo solar (convencional e demanda). Recebe consumo/demanda + parâmetros → retorna potência do sistema, quantidade de painéis, geração estimada.
- **`GrupoTarifarioService`** — cálculo para todos os grupos ANEEL (B1/B2/B3/A). Recebe tarifa + consumo → retorna análise econômica (payback, TIR, economia mensal).
- **`PrecificacaoService`** — aplica as 5 camadas de margem (principal → estado → estrutura → fornecedor → consultor) para calcular o preço de venda.
- **`Integracoes/Edeltec/`** — serviço de sincronização de catálogo Edeltec.

### Database — Migrations (34 total)

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
      MargemPrincipal/Index.tsx
      Estados/Index.tsx
      Consultores/Index.tsx
      Estruturas/Index.tsx
      Fornecedores/Index.tsx
    Usuarios/
      Admins/{Index,Form}.tsx
      Consultores/{Index,Form}.tsx
    Financeiro/
      Comissoes/Index.tsx
      Faturamento/Index.tsx
    Configuracoes/
      Bancos/Index.tsx
      Concessionarias/Index.tsx
      Dimensionamento/Index.tsx      — parâmetros do motor de cálculo
      Sistema/Index.tsx
    Integracoes/
      Aldo/Index.tsx
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
    Contratos/Index.tsx
    Financeiro/Index.tsx             — extrato de comissões do consultor
    Perfil/{Edit,Senha}.tsx
    PropostasServicos/{Index,Form,Show}.tsx
    Visitas/{Index,Form}.tsx
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

**Build limpo.** Todos os módulos do Admin e Consultor estão implementados.

### Pendências conhecidas
- `Admin/Produtos/Inversores`, `Paineis`, `Trafos` — controllers e pages prontos, mas **sem rotas no `web.php`** e **sem entrada no sidebar**
- **PDF do orçamento** — rota `consultor.orcamentos.pdf` existe, botão na UI existe, mas sem implementação no controller e sem biblioteca PDF instalada
- **Admin Clientes Form** — sem CEP lookup (o `Consultor/Clientes/Form.tsx` tem; o Admin não)
