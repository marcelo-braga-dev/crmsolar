# CRM Solar V2

CRM para empresas de energia solar. Gerencia o pipeline completo de vendas: **leads → orçamentos → contratos → visitas técnicas → instalação**.

## Stack

| Camada     | Tecnologia                                      |
|------------|-------------------------------------------------|
| Backend    | Laravel 13 · PHP 8.3                            |
| Frontend   | React 18 · TypeScript · Inertia.js v2           |
| UI         | MUI v7 · Recharts                               |
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
- **Orçamentos** — visão geral de todas as propostas com filtros e status
- **Clientes** — cadastro e gestão de clientes
- **Leads** — acompanhamento de leads recebidos
- **Catálogo de Produtos** — painéis, inversores, trafos, kits solares, categorias, marcas
- **Precificação** — 5 camadas de margem: principal, por estado, por consultor, por estrutura, por fornecedor
- **Usuários** — gestão de admins e consultores
- **Financeiro** — comissões e faturamento
- **Fornecedores** — cadastro de fornecedores
- **Integrações** — sincronização de catálogo Aldo e Edeltec, histórico de execuções
- **Configurações** — bancos, concessionárias, parâmetros de dimensionamento, sistema

### Área Consultor

- **Dashboard** — KPIs pessoais, evolução mensal, pipeline, orçamentos recentes
- **Orçamentos** — criação com dimensionamento automático por grupo tarifário ANEEL:
  - **B1** — Residencial (Baixa Tensão)
  - **B2** — Rural
  - **B3** — Comercial/Industrial BT
  - **A (A1–A4)** — Média e Alta Tensão (THS Verde / Azul)
  - Análise econômica completa: payback, TIR, economia mensal, projeção 25 anos
- **Clientes** — CRUD com busca de CEP automática
- **Leads** — acompanhamento dos leads atribuídos
- **Propostas de Serviços** — criação de propostas para serviços avulsos (O&M, limpeza, etc.)
- **Contratos** — geração a partir de orçamentos aprovados
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
  Models/              — 29 modelos Eloquent (sem padrão EAV)
  Services/
    DimensionamentoService.php   — engine de cálculo solar
    GrupoTarifarioService.php    — análise econômica por grupo ANEEL
    PrecificacaoService.php      — cálculo de preços com margens em camadas
    Integracoes/                 — serviços de sync com distribuidores

resources/js/
  Pages/
    Admin/             — páginas da área admin
    Consultor/         — páginas da área consultor
    Auth/              — telas de autenticação
  Components/          — componentes reutilizáveis (PageHeader, KpiCard, etc.)
  Layouts/             — AppLayout (autenticado), GuestLayout (auth)

database/
  migrations/          — 34 migrations
  seeders/             — dados de estrutura + dados de teste
```

## Status do projeto

> ⚠️ **Em desenvolvimento.** O ambiente `crmsolar.rexar.com.br` contém **apenas dados de teste**. As credenciais padrão (senha `1020`) devem ser trocadas antes do go-live.

## Limitações conhecidas

- **Catálogo de Inversores/Painéis/Trafos (Admin)** — telas prontas, mas ainda sem rota registrada e sem entrada no menu lateral
- **Cadastro de usuários** — não há cadastro público; admins e consultores são criados em Admin → Usuários
- **Cadastro de Clientes (Admin)** — sem busca automática de CEP (o formulário do Consultor já tem)
- **Integração Aldo** — botão existe, mas a sincronização ainda não está implementada

## Problemas conhecidos (a corrigir antes do go-live)

Detalhes técnicos em [`CLAUDE.md`](CLAUDE.md#status-atual-do-desenvolvimento).

- Transições de status do orçamento pelo Admin não são validadas (ficam registradas no histórico)
- Fluxo legado de dimensionamento convencional grava orçamento sem grupo tarifário

## Testes

```bash
php artisan test   # 261 testes, SQLite em memória
```

> ⛔ **Exigência máxima:** tudo que for criado ou alterado (funcionalidade, correção, regra de negócio, rota, validação, permissão) **deve vir acompanhado de testes automatizados no mesmo trabalho**, e a suíte completa precisa passar com **0 falhas** antes de considerar a tarefa concluída. Bug corrigido exige teste que reproduza o bug. Detalhes em [`CLAUDE.md`](CLAUDE.md#-exigência-máxima--tudo-que-for-trabalhado-deve-ser-testado).

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
