# CRM Solar V2

CRM para empresas de energia solar. Gerencia o pipeline completo de vendas: **leads → orçamentos → contratos → visitas técnicas → instalação**.

## Stack

| Camada     | Tecnologia                                      |
|------------|-------------------------------------------------|
| Backend    | Laravel 13 · PHP 8.3+                           |
| Frontend   | React 18 · TypeScript · Inertia.js v2           |
| UI         | MUI v7 · Recharts · @dnd-kit (Kanban)           |
| Build      | Vite 6                                          |
| Banco      | MySQL 8.4                                       |
| Infra      | Docker (Laravel Sail)                           |

## Pré-requisitos

- Docker Desktop (ou Docker Engine + Compose)
- Node.js 20+ (para build local sem Sail)

## Setup

```bash
# 1. Copiar e configurar variáveis de ambiente
cp .env.example .env

# 2. Instalar dependências PHP
docker run --rm -v $(pwd):/app composer install

# 3. Gerar chave da aplicação
./vendor/bin/sail artisan key:generate

# 4. Subir containers
./vendor/bin/sail up -d

# 5. Criar banco, rodar migrations e seeds
./vendor/bin/sail artisan migrate --seed

# 6. Instalar dependências JS e buildar
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev   # desenvolvimento (HMR)
# ou
npm run build                   # produção
```

## URLs de acesso

| Serviço    | URL                              |
|------------|----------------------------------|
| Aplicação  | http://localhost                 |
| phpMyAdmin | http://localhost:8080            |

## Usuários padrão (seed)

| E-mail                   | Senha    | Área         |
|--------------------------|----------|--------------|
| admin@teste.com          | 1020     | Admin        |
| consultor@teste.com      | 1020     | Consultor    |

## Funcionalidades

### Área Admin

- **Dashboard** — KPIs gerais, evolução mensal, pipeline por status, ranking de consultores
- **Orçamentos** — **Funil (Kanban)** de todos os consultores (aprovar = soltar em Ganho, trocar responsável, ações em lote) e **Lista** com filtros e mudança de status
- **Clientes** — cadastro e gestão de clientes
- **Leads** — acompanhamento de leads recebidos
- **Produtos** — **Kits Solares** (sistemas completos usados no dimensionamento) e **Catálogo** de produtos avulsos, com atalhos por categoria (painéis, inversores, transformadores…) e abas de Categorias e Marcas
- **Precificação** — 3 camadas de margem (principal por faixa de potência → estado → fornecedor) e simulador de preço. A comissão do consultor não entra no preço
- **Usuários** — gestão de admins e consultores
- **Financeiro** — comissões e faturamento
- **Fornecedores** — cadastro de fornecedores
- **Integrações** — sincronização de catálogo Edeltec, histórico de execuções
- **Configurações** — auditoria (log de alterações), bancos, concessionárias, parâmetros de dimensionamento, funil de vendas (etapas, motivos de perda), sistema

### Área Consultor

- **Dashboard** — KPIs pessoais, evolução mensal, pipeline, orçamentos recentes
- **Funil de vendas (Kanban)** — etapas comerciais com arrastar e soltar, caixa de entrada, próximo contato, perda com motivo e reativação; saúde de cada negociação (atrasado, atenção, sem próximo passo, em dia), painel lateral com WhatsApp/ligar e linha do tempo, filtros e ordenação, versão para celular ([especificação](docs/funil-de-vendas.md))
- **Orçamentos** — criação com dimensionamento automático por grupo tarifário ANEEL:
  - **B1** — Residencial (Baixa Tensão)
  - **B2** — Rural
  - **B3** — Comercial/Industrial BT
  - **A (A1–A4)** — Média e Alta Tensão (THS Verde / Azul)
  - Análise econômica completa: payback, TIR, economia mensal, projeção 25 anos
- **Clientes** — CRUD com busca de CEP automática
- **Leads** — acompanhamento dos leads atribuídos
- **Propostas de Serviços** — criação de propostas para serviços avulsos (O&M, limpeza, etc.)
- **Contratos** — geração a partir de orçamentos aprovados (valor e dados técnicos vêm do orçamento), com PDF
- **Visitas Técnicas** — agendamento e registro de visitas
- **Financeiro** — extrato de comissões

### API Pública (sem autenticação)

| Rota                        | Descrição                            |
|-----------------------------|--------------------------------------|
| `POST /api/leads`           | Recebe leads de formulários externos |
| `GET /api/orcamento/{token}`| Visualização pública da proposta     |
| `GET /api/cep/{cep}`        | Lookup de endereço por CEP (ViaCEP)  |
| `GET /api/estados`          | Lista de estados brasileiros         |
| `GET /api/cidades/{estado}` | Municípios por estado                |

## Estrutura do projeto

```
app/
  Http/
    Controllers/
      Admin/           — controllers da área admin
      Consultor/       — controllers da área consultor
      Api/             — endpoints públicos
      FunilController  — quadro Kanban (Admin e Consultor)
  Models/              — 30 modelos Eloquent
  Services/
    DimensionamentoService.php   — engine de cálculo solar
    GrupoTarifarioService.php    — análise econômica por grupo ANEEL
    PrecificacaoService.php      — cálculo de preços com margens em camadas
    Funil/FunilService.php       — regras do funil de vendas
    Integracoes/Edeltec/         — sincronização do catálogo Edeltec
  Jobs/SincronizarEdeltec.php    — sincronização em fila (opcional)

resources/js/
  Pages/
    Admin/             — páginas da área admin
    Consultor/         — páginas da área consultor
    Funil/             — quadro Kanban (compartilhado)
    Auth/              — telas de autenticação
  Components/          — componentes reutilizáveis (PageHeader, KpiCard, etc.)
  Layouts/             — AppLayout (autenticado), GuestLayout (auth)

database/
  migrations/          — 40 migrations
  seeders/             — dados de estrutura + dados de teste

docs/
  funil-de-vendas.md   — especificação do funil (regras, permissões, modelo de dados)
```

## Variáveis de ambiente específicas

Além das padrão do Laravel (ver `.env.example`):

| Variável                 | Padrão              | Uso                                                                 |
|--------------------------|---------------------|---------------------------------------------------------------------|
| `APP_TIMEZONE_EXIBICAO`  | `America/Sao_Paulo` | Fuso dos textos gerados no servidor (a aplicação grava em UTC)      |
| `EDELTEC_API_KEY`, `EDELTEC_SECRET`, `EDELTEC_API_URL` | — | Credenciais da API Edeltec (sincronização do catálogo de kits) |
| `EDELTEC_SYNC_FILA`      | `false`             | `true` = botão "Integrar" enfileira a sincronização. Exige worker `queue:work` |

A sincronização Edeltec também roda todo dia às 04h00 pelo agendador (`php artisan schedule:run` no cron).

## Status do projeto

> ⚠️ **Em desenvolvimento.** O ambiente `crmsolar.rexar.com.br` contém **apenas dados de teste**. As credenciais padrão (senha `1020`) devem ser trocadas antes do go-live.

## Limitações conhecidas

- **Cadastro de usuários** — não há cadastro público; admins e consultores são criados em Admin → Usuários

## Testes

```bash
php artisan test   # 455 testes, SQLite em memória
```

> ⚠️ No servidor, **rode `php artisan optimize:clear` antes dos testes**: com a configuração em cache o `phpunit.xml` é ignorado e os testes iriam para o MySQL real (o `RefreshDatabase` apagaria o banco). O `tests/TestCase.php` aborta nesse caso.

> ⛔ **Exigência máxima:** tudo que for criado ou alterado (funcionalidade, correção, regra de negócio, rota, validação, permissão) **deve vir acompanhado de testes automatizados no mesmo trabalho**, e a suíte completa precisa passar com **0 falhas** antes de considerar a tarefa concluída. Bug corrigido exige teste que reproduza o bug. Detalhes em [`CLAUDE.md`](CLAUDE.md#-exigência-máxima--tudo-que-for-trabalhado-deve-ser-testado).

## Base de demonstração

Para apresentar o produto há uma base fictícia completa (empresa operando há 16 meses, todas as telas cheias, logins por perfil), gerada pelo `MarketingDemoSeeder` numa instalação separada. Passo a passo, contas e renovação em [`DEMO.md`](DEMO.md).

## Comandos úteis

```bash
# Reset completo do banco (cuidado: apaga tudo)
./vendor/bin/sail artisan migrate:fresh --seed

# Tinker (REPL interativo)
./vendor/bin/sail artisan tinker

# Ver todas as rotas
./vendor/bin/sail artisan route:list

# Logs da aplicação
./vendor/bin/sail logs -f

# Parar containers
./vendor/bin/sail down
```
