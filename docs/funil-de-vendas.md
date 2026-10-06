# Funil de Vendas (Kanban de Orçamentos)

> Especificação funcional e técnica do funil comercial do CRM Solar V2.
> Status: **Fase 1 concluída** · Evolução da experiência em andamento (seção 22) · Fases 2 e 3 planejadas · Atualizado em 2026-10-06

## Sumário

1. [Objetivo](#1-objetivo)
2. [Glossário](#2-glossário)
3. [Situação anterior e problema](#3-situação-anterior-e-problema)
4. [Princípios de projeto](#4-princípios-de-projeto)
5. [Estrutura do quadro](#5-estrutura-do-quadro)
6. [Regra de posicionamento do card](#6-regra-de-posicionamento-do-card)
7. [Movimentações e regras de negócio](#7-movimentações-e-regras-de-negócio)
8. [Caixa de entrada](#8-caixa-de-entrada)
9. [Perda e recuperação de vendas](#9-perda-e-recuperação-de-vendas)
10. [Follow-up (próximo contato)](#10-follow-up-próximo-contato)
11. [Interface](#11-interface)
12. [Permissões](#12-permissões)
13. [Configuração (Admin)](#13-configuração-admin)
14. [Modelo de dados](#14-modelo-de-dados)
15. [Rotas](#15-rotas)
16. [Migração dos dados existentes](#16-migração-dos-dados-existentes)
17. [Prevenção de erros](#17-prevenção-de-erros)
18. [Testes](#18-testes)
19. [Fases de entrega](#19-fases-de-entrega)
20. [Decisões e premissas](#20-decisões-e-premissas)
21. [Implementação (Fase 1)](#21-implementação-fase-1)
22. [Plano de evolução da experiência](#22-plano-de-evolução-da-experiência)

---

## 1. Objetivo

Dar visibilidade e controle ao trabalho comercial entre **"orçamento gerado"** e **"venda fechada"**:

- saber em que etapa cada negociação está e há quanto tempo está parada;
- garantir que todo orçamento tenha um **próximo passo** (follow-up);
- registrar **por que** vendas são perdidas e **recuperar** as que ainda têm chance;
- prever receita (**valor ponderado** por probabilidade de fechamento).

## 2. Glossário

| Termo | Significado |
|---|---|
| **Funil de vendas / Pipeline** | Sequência de etapas pelas quais uma oportunidade passa até ser ganha ou perdida |
| **Oportunidade / Negócio** | Um orçamento em negociação (cada card do quadro) |
| **Etapa** | Coluna do quadro. Pode ser *aberta* (trabalho comercial) ou *de sistema* (Em aprovação, Ganho, Perdido) |
| **Caixa de entrada** | Orçamentos recém-gerados (`status = novo`) que ainda não começaram a ser trabalhados |
| **Ganho** | Venda fechada — orçamento aprovado internamente (`aprovado`, `instalando`, `finalizado`) |
| **Perdido** | Negociação encerrada sem venda, sempre com **motivo de perda** |
| **Motivo de perda** | Classificação padronizada da perda (preço, concorrente, sem retorno…) |
| **Follow-up / Próximo contato** | Data combinada para o próximo contato com o cliente |
| **SLA da etapa** | Tempo máximo esperado (em dias) de permanência numa etapa; acima disso o card "esfria" |
| **Aging (tempo na etapa)** | Dias desde que o card entrou na etapa atual |
| **Probabilidade** | Chance estimada de fechamento associada à etapa (%) |
| **Valor ponderado / Forecast** | Soma de `valor × probabilidade` — previsão de receita |
| **Reativação / Recuperação** | Retomar uma oportunidade parada ou perdida |
| **Cadência** | Ritmo planejado de contatos de follow-up |

## 3. Situação anterior e problema

O campo `orcamentos.status` controla apenas o **fluxo interno/operacional**:

```
novo ──► aprovando ──► aprovado ──► instalando ──► finalizado
            │  (admin valida)
            └─► aprovacao_reprovada
```

Esse status alimenta **faturamento, comissões, dashboards, contratos e visitas**. Entre "novo" e "aprovando" não havia nenhum registro comercial: não se sabia se a proposta foi apresentada, se houve visita, quando é o próximo contato nem por que vendas foram perdidas. Resultado: orçamentos `novo` acumulados sem andamento.

## 4. Princípios de projeto

1. **O `status` existente não muda.** O funil é uma camada nova por cima dele. Financeiro, comissões, dashboards e transições continuam iguais.
2. **Fonte única da verdade.** As colunas de sistema (Em aprovação, Ganho) são **derivadas do `status`**, nunca gravadas em paralelo — o quadro não tem como dessincronizar do resto do sistema.
3. **Regras no servidor.** O frontend só reflete; toda movimentação é validada no backend.
4. **Nada é descartado automaticamente.** Orçamentos parados são sinalizados e sugeridos para recuperação, nunca perdidos sem ação humana.
5. **Configurável sem quebrar.** Nome, cor, ordem, probabilidade e SLA das etapas são editáveis; o comportamento é determinado pelo **tipo** da etapa, que não muda.

## 5. Estrutura do quadro

```
 Caixa de entrada (12) ▸   ← gaveta lateral, fora do quadro
┌──────────────┬──────────────┬─────────────┬─────────────┬──────────────┬──────────────╥─────────────┬─────────┐
│ Primeiro     │ Proposta     │ Visita      │ Negociação  │ Financiamento│ Fechamento   ║ Em aprovação│ Ganho ✓ │
│ contato 10%  │ apresentada  │ técnica 50% │ 70%         │ 80%          │ 90%          ║ (interna)   │         │
└──────────────┴──────────────┴─────────────┴─────────────┴──────────────┴──────────────╨─────────────┴─────────┘
                    etapas abertas (editáveis)                                  etapas de sistema
                                     Ao arrastar um card aparece a zona:  [ ✕ Perdido ]
```

### Tipos de etapa

| Tipo | Qtde | Comportamento | Excluir? |
|---|---|---|---|
| `aberta` | 1 ou mais | Trabalho comercial. Têm ordem, cor, probabilidade e SLA | Sim — se tiver cards, exige escolher a etapa de destino |
| `aprovacao` | exatamente 1 | Soltar aqui = **enviar para aprovação** (`status → aprovando`) | Não |
| `ganho` | exatamente 1 | Orçamentos `aprovado`/`instalando`/`finalizado`. Só o admin move para cá (= aprovar) | Não |
| `perdido` | exatamente 1 | Exige motivo de perda. Exibida como zona de soltar durante o arraste | Não |

Todas podem ser **renomeadas e recoloridas**.

### Etapas padrão

| Ordem | Etapa | Probabilidade | SLA |
|---|---|---|---|
| 1 | Primeiro contato | 10% | 2 dias |
| 2 | Proposta apresentada | 30% | 5 dias |
| 3 | Visita técnica | 50% | 7 dias |
| 4 | Negociação | 70% | 7 dias |
| 5 | Financiamento | 80% | 15 dias |
| 6 | Fechamento | 90% | 5 dias |
| — | Em aprovação *(sistema)* | 95% | 2 dias |
| — | Ganho *(sistema)* | 100% | — |
| — | Perdido *(sistema)* | 0% | — |

## 6. Regra de posicionamento do card

Calculada no servidor em um único lugar (`FunilService`), nesta ordem:

| # | Condição | Coluna |
|---|---|---|
| 1 | `perdido_em` preenchido | **Perdido** |
| 2 | `status ∈ {aprovado, instalando, finalizado}` | **Ganho** |
| 3 | `status = aprovando` | **Em aprovação** |
| 4 | `funil_etapa_id` preenchido | a etapa aberta gravada |
| 5 | `status = aprovacao_reprovada` sem etapa | primeira etapa aberta |
| 6 | `status = novo` sem etapa | **Caixa de entrada** |

Consequências:
- Aprovação feita pela tela do orçamento (fora do quadro) já aparece no quadro.
- Orçamento **reprovado** pelo admin volta para a etapa em que estava, com selo **"Reprovado"**.

## 7. Movimentações e regras de negócio

| De → Para | Quem | Efeito | Bloqueios |
|---|---|---|---|
| Caixa → etapa aberta | dono, admin | grava etapa ("iniciar atendimento") | perdido |
| etapa aberta → etapa aberta | dono, admin | grava etapa | perdido |
| etapa aberta → **Em aprovação** | dono, admin | `status → aprovando` + histórico | status fora de `novo`/`aprovacao_reprovada`; `bloquear_edicao`; perdido |
| **Em aprovação → Ganho** | admin | `status → aprovado` (= aprovar) | regras de `Orcamento::TRANSICOES` |
| **Em aprovação → etapa aberta** | admin | `status → aprovacao_reprovada` + etapa destino (= reprovar) | — |
| qualquer aberta/caixa → **Perdido** | dono, admin | grava perda + motivo | status fora de `novo`/`aprovacao_reprovada` |
| **Perdido → etapa aberta** | dono, admin | reativa (limpa perda, conta tentativa) | — |
| Ganho → qualquer | ninguém pelo quadro | — | usar a tela do orçamento |
| qualquer → Caixa | ninguém | — | a caixa só recebe orçamentos novos |

Toda movimentação:
- atualiza `etapa_entrou_em` (para aging/SLA);
- grava uma entrada na **linha do tempo** (`orcamento_historicos`, com `tipo`);
- é registrada na **Auditoria** (activitylog).

## 8. Caixa de entrada

- Orçamentos `novo` sem etapa **não aparecem nas colunas**.
- Ficam numa **gaveta lateral** com contador no topo do quadro, do mais antigo para o mais novo, com "aguardando há X dias".
- Ações rápidas: **Iniciar atendimento** (→ primeira etapa, pede próximo contato) e **Descartar** (→ Perdido com motivo).
- Após `funil.dias_caixa_entrada` dias (padrão 7) o item recebe o selo **"esfriando"** e entra na fila de Recuperação (Fase 2).

## 9. Perda e recuperação de vendas

### Motivos de perda (padrão, editáveis)

| Motivo | Reativável | Reativar após |
|---|---|---|
| Adiou a decisão | sim | 60 dias |
| Preço alto | sim | 30 dias |
| Sem retorno do cliente | sim | 15 dias |
| Crédito/financiamento negado | sim | 90 dias |
| Desistiu do projeto | sim | 120 dias |
| Fechou com concorrente | não | — |
| Inviabilidade técnica | não | — |

### Fila de Recuperação (Fase 2)

Calculada por consulta — **não depende de tarefa agendada (cron)**:

1. **Novos não trabalhados** — na caixa de entrada há mais de N dias.
2. **Negócios parados** — sem movimentação/contato acima do SLA da etapa.
3. **Perdidos reativáveis** — motivo reativável e prazo vencido.

Ações: **Reativar** (volta à primeira etapa, `tentativas_reativacao + 1`) ou **Agendar contato**. Após `funil.max_tentativas_reativacao` (padrão 3) o orçamento deixa de ser sugerido.

## 10. Follow-up (próximo contato)

- Cada oportunidade tem `proximo_contato_em`.
- **Registrar contato**: anotação + nova data → vai para a linha do tempo.
- Contato vencido: card com saúde **atrasado** (vermelho) e contagem no indicador **"Contatos atrasados"**.
- Negociação numa etapa aberta sem contato agendado: saúde **sem próximo passo** e contagem no indicador de mesmo nome. Mover um card assim entre etapas abertas pede a data (pode-se escolher "Sem data").
- Aprovar a venda limpa o próximo contato (o acompanhamento segue pelo pós-venda).

## 11. Interface

### Card

```
┌─────────────────────────────────┐
│▌● Comercial Pereira Ltda   #142 │  ← faixa com a cor da etapa · ponto de saúde
│▌ R$ 32.400                      │
│▌ 6,2 kWp · B1 · Fortaleza/CE    │
│▌ [📅 hoje 14:00]     ◎ ☎  (AS)  │  ← próximo passo · WhatsApp/ligar (hover) · consultor (admin)
│▌ ▓▓▓▓▓▓░░░░░░░░░░░░   5d / 7d   │  ← tempo na etapa ÷ SLA
│▌ [Reprovado] [Contrato]         │  ← selos de situação
└─────────────────────────────────┘
```

- **Saúde** (seção 22.3): 🔴 atrasado · 🟡 atenção · ⚪ sem próximo passo · 🟢 em dia; só em etapas abertas e Em aprovação.
- **Densidade**: confortável ou compacta (esconde linha técnica e selos), preferência no navegador.
- Clicar no card abre o **painel lateral** (cliente, WhatsApp/ligar/e-mail, próximo contato, ações, responsável — admin, itens e linha do tempo) sem sair do quadro; "Abrir completo" leva à tela do orçamento.
- Cabeçalho da coluna: quantidade · soma (R$) · valor ponderado (etapas abertas e Em aprovação) · barra da participação da etapa no valor em negociação.
- **Faixa do funil** acima do quadro: valor por etapa; clicar rola até a coluna.
- Indicadores no topo: em negociação, previsão ponderada (etapas abertas **+ Em aprovação**, com a probabilidade de 95%), contatos atrasados e sem próximo passo (clique filtra) e caixa de entrada.
- Filtros: busca instantânea por cliente ou `#número`, consultor (admin), grupo tarifário, chips **Atrasados**, **Sem próximo passo** e **Prazo estourado**; ordenação **Prioridade** (padrão: contato atrasado → contato mais cedo → mais tempo na etapa), **Maior valor** ou **Mais tempo na etapa**. Tudo fica na URL.
- Ganho e Perdido mostram os últimos 30 dias (`FunilService::DIAS_FECHADOS_NO_QUADRO`); o histórico completo fica em **Orçamentos → Lista**.
- Arrastar e soltar com `@dnd-kit` (mouse, toque e teclado); atualização otimista com reversão se o servidor recusar. Movimento entre etapas abertas pode ser **desfeito** por alguns segundos.
- Depois de abrir o WhatsApp ou ligar, o quadro oferece **registrar o contato**.
- **Seleção em lote** (admin, botão "Selecionar"): mover para etapa aberta, trocar responsável ou marcar como perdido vários cards.
- Atalhos: `/` busca, `C` caixa de entrada, `Esc` sai da seleção.
- **Celular**: uma etapa por vez, escolhida em abas; sem arraste — movimentos pelo menu ⋮ do card ou pelo painel.
- Consultor tem o botão **Novo orçamento** (leva à seleção de grupo tarifário).

### Menu

- Consultor e Admin: **Orçamentos → Funil (Kanban)** e **Orçamentos → Lista**.
- Admin: **Configurações → Funil de vendas**.

## 12. Permissões

| Ação | Consultor | Admin |
|---|---|---|
| Ver quadro | só os próprios orçamentos | todos (filtro por consultor) |
| Mover entre etapas abertas, para Perdido e para Em aprovação | os próprios | todos |
| Aprovar (Em aprovação → Ganho) / reprovar | não | sim |
| Reativar perdido, registrar contato | os próprios | todos |
| Ver o painel lateral do card | os próprios | todos |
| Trocar o responsável (negociação em andamento) | não | sim |
| Ações em lote | não | sim |
| Configurar etapas, motivos e parâmetros | não | sim |

## 13. Configuração (Admin)

Tela **Configurações → Funil de vendas**:

- **Etapas**: nome, cor (paleta + hex), ordem, probabilidade, SLA, ativa. Etapas de sistema: só nome e cor (e SLA da aprovação).
- **Excluir etapa aberta com cards**: obrigatório escolher a etapa de destino; a movimentação é feita em transação.
- Mínimo de **1 etapa aberta ativa**.
- **Motivos de perda**: nome, reativável, dias para reativar, ativo. Motivo em uso não é excluído (só desativado).
- **Parâmetros**: `funil.dias_caixa_entrada`, `funil.max_tentativas_reativacao` (tabela `configs`).

## 14. Modelo de dados

### `funil_etapas` (nova)

| Coluna | Tipo | Observação |
|---|---|---|
| id | bigint | |
| nome | string(60) | |
| cor | string(7) | hex `#RRGGBB` |
| tipo | enum `aberta`/`aprovacao`/`ganho`/`perdido` | imutável após criada |
| ordem | smallint | ordem entre as abertas |
| probabilidade | tinyint null | 0–100 |
| sla_dias | smallint null | |
| ativa | bool | etapas de sistema sempre ativas |

### `motivos_perda` (nova)

| Coluna | Tipo |
|---|---|
| id, nome (string 80), reativavel (bool), reativar_apos_dias (smallint null), ordem, ativo |

### `orcamentos` (colunas novas)

| Coluna | Tipo | Uso |
|---|---|---|
| funil_etapa_id | FK null → funil_etapas (restrict) | etapa aberta atual |
| etapa_entrou_em | timestamp null | aging/SLA; atualizado pelo model quando a coluna muda |
| proximo_contato_em | datetime null | follow-up |
| perdido_em | timestamp null | marca a perda |
| motivo_perda_id | FK null → motivos_perda (restrict) | |
| perda_observacao | text null | |
| tentativas_reativacao | tinyint default 0 | |

### `orcamento_historicos` (coluna nova)

| Coluna | Tipo | Valores |
|---|---|---|
| tipo | string(20) default `status` | `status`, `etapa`, `contato`, `perda`, `reativacao` |
| status | passa a aceitar null | eventos comerciais não mudam status |

## 15. Rotas

| Método | URI | Nome |
|---|---|---|
| GET | `/consultor/funil` | `consultor.funil.index` |
| POST | `/consultor/funil/{orcamento}/mover` | `consultor.funil.mover` |
| POST | `/consultor/funil/{orcamento}/perder` | `consultor.funil.perder` |
| POST | `/consultor/funil/{orcamento}/reativar` | `consultor.funil.reativar` |
| POST | `/consultor/funil/{orcamento}/contato` | `consultor.funil.contato` |
| GET/POST | `/admin/funil…` | `admin.funil.*` (mesmas ações; aprovar = soltar em Ganho, reprovar = tirar de Em aprovação) |
| GET | `/{area}/funil/{orcamento}` | `admin.funil.show`, `consultor.funil.show` — dados do painel lateral (JSON) |
| POST | `/admin/funil/{orcamento}/consultor` | `admin.funil.reatribuir` — troca o responsável |
| POST | `/admin/funil/lote` | `admin.funil.lote` — `acao` = `mover` \| `reatribuir` \| `perder` para vários `ids` |
| GET | `/admin/configuracoes/funil` | `admin.configuracoes.funil.index` |
| POST/PUT/DELETE | `/admin/configuracoes/funil/etapas…` | `…etapas.store`, `…etapas.update`, `…etapas.destroy` (com `destino_id`), `…etapas.reordenar` (PUT `etapas/ordem`) |
| POST/PUT/DELETE | `/admin/configuracoes/funil/motivos…` | `…motivos.store`, `…motivos.update`, `…motivos.destroy` |
| PUT | `/admin/configuracoes/funil/parametros` | `admin.configuracoes.funil.parametros` |

## 16. Migração dos dados existentes

1. Cria as tabelas e insere as **etapas e motivos padrão** na própria migration (produção recebe sem depender de seeder).
2. Orçamentos `aprovacao_reprovada` recebem a primeira etapa aberta.
3. Demais orçamentos já caem na coluna certa pela regra da seção 6 (`novo` → caixa; `aprovando` → Em aprovação; aprovado+ → Ganho).
4. `etapa_entrou_em` inicial = `updated_at` do orçamento.

## 17. Prevenção de erros

| Risco | Proteção |
|---|---|
| Quadro dessincronizado do status | colunas de sistema derivadas do status (seção 6) |
| Duas pessoas movem o mesmo card | requisição envia a coluna de origem; se mudou, o servidor recusa e o quadro recarrega |
| Excluir etapa com cards | exige destino; FK `restrict` impede órfãos |
| Ficar sem etapa aberta | validação: mínimo 1 ativa |
| Perder venda sem motivo | motivo obrigatório |
| Venda "ganha" sem aprovação | só o admin aprova; regra de `TRANSICOES` reaproveitada |
| Orçamento perdido aprovado pela tela antiga | tela do orçamento bloqueia mudança de status de orçamento perdido |
| Conflito de ordem entre usuários | ordem automática dentro da coluna (sem posição manual) |
| Quadro pesado | Ganho/Perdido limitados aos últimos 30 dias; colunas abertas com limite e aviso |

## 18. Testes

Fase 1 cobre, com dono × outro consultor × admin × visitante:

- posicionamento do card (seção 6) para cada combinação de status/etapa/perda;
- cada movimentação da seção 7, permitida e bloqueada;
- concorrência (coluna de origem divergente);
- perda exige motivo; reativação limpa a perda e conta tentativa;
- configuração: criar/editar/reordenar etapas, excluir com destino, mínimo de 1 aberta, etapas de sistema protegidas, motivos;
- migração dos dados existentes;
- quadro e configuração na matriz `ControleDeAcessoTest`.

## 19. Fases de entrega

| Fase | Escopo |
|---|---|
| **1 — Quadro completo** | Banco e migração, configuração de etapas/motivos, quadro consultor e admin com arrastar e soltar, caixa de entrada, perda com motivo, reativação, próximo contato, linha do tempo, testes |
| **1.5 — Experiência** | Correções e ondas 1 e 2 da seção 22: visual do card e das colunas, saúde do card, painel lateral, contato rápido (WhatsApp/ligar), filtros e versão mobile |
| **2 — Recuperação e métricas** | Aba Recuperação (3 filas), métricas (conversão por etapa, tempo médio por etapa, ranking de motivos), "contatos de hoje" no dashboard |
| **3 — Atividades** | Registro detalhado de interações (ligação, WhatsApp, e-mail, reunião), modelos de mensagem, automações por etapa |

## 20. Decisões e premissas

- **"Enviar para aprovação" = validação interna do Admin**, antes do contrato — não é o aceite do cliente. (A tabela `orcamento_aprovacoes`, com assinatura/IP do cliente, existe mas não é usada por nenhuma tela.)
- O `status` operacional permanece a fonte de verdade para financeiro e comissões.
- Sem ordenação manual dentro das colunas (evita conflitos e mantém a prioridade pelo follow-up).

## 21. Implementação (Fase 1)

### Arquivos

| Camada | Arquivo | Papel |
|---|---|---|
| Migration | `database/migrations/2026_10_04_000000_create_funil_de_vendas.php` | tabelas, colunas, etapas/motivos padrão, migração dos reprovados |
| Models | `app/Models/FunilEtapa.php`, `app/Models/MotivoPerda.php` | constantes de tipo, `scopeAbertas()` |
| Model | `app/Models/Orcamento.php` | `STATUS_EM_NEGOCIACAO`, `STATUS_GANHO`, `grupoDoStatus()`, `estaPerdido()`, `emNegociacao()`; hook que reinicia `etapa_entrou_em` só quando a coluna muda |
| Serviço | `app/Services/Funil/FunilService.php` | **toda** regra: `etapaDe()` (seção 6), `mover()`, `perder()`, `reativar()`, `registrarContato()`, `quadro()` |
| Exceção | `app/Services/Funil/MovimentoInvalido.php` | recusa de negócio → mensagem de erro para o usuário |
| Controller | `app/Http/Controllers/FunilController.php` | quadro e ações, compartilhado por Admin e Consultor |
| Controller | `app/Http/Controllers/Admin/Configuracoes/FunilVendasController.php` | configuração |
| Página | `resources/js/Pages/Funil/Index.tsx` | quadro (uma página para as duas áreas, prop `area`) |
| Página | `resources/js/Pages/Admin/Configuracoes/Funil/Index.tsx` | configuração |
| Componentes | `resources/js/Components/Funil/*` | `CardOrcamento`, `ColunaFunil`, `CaixaEntrada`, `PainelOrcamento` (painel lateral), `Dialogos` (perda, agenda), `tipos.ts` |

### Detalhes técnicos

- **Arrastar e soltar:** `@dnd-kit/core` com sensores de mouse (6 px para não confundir com clique), toque (segurar 180 ms) e teclado. O card que acompanha o cursor é um `DragOverlay`. Toda ação também existe pelo menu ⋮ do card ("Mover para…"), alternativa acessível ao arraste.
- **Atualização otimista:** o card muda de coluna antes da resposta; a resposta do Inertia devolve o quadro real, que substitui o estado local (se o servidor recusar, o card volta sozinho e aparece a mensagem).
- **Recarga parcial:** movimentos pedem só `colunas`, `caixa`, `resumo` e `flash` (`only`); durante a requisição só o card em envio deixa de ser arrastável.
- **Painel lateral:** carregado sob demanda por `GET funil/{orcamento}` (`FunilService::detalhe()`, JSON) e recarregado quando o quadro muda.
- **Contatos:** `FunilService::contatos()` monta `telefone` (dígitos, para `tel:`) e `whatsapp` (com DDI 55, para `wa.me`), preferindo o celular.
- **Concorrência:** cada requisição envia `origem` (id da etapa ou `caixa`); o serviço trava o orçamento (`lockForUpdate`) e recusa se a coluna atual for outra.
- **Fuso horário:** a aplicação grava em UTC. O navegador envia o próximo contato em ISO com fuso (`toISOString()`) e exibe as datas recebidas no horário local. Textos gerados no servidor (linha do tempo) usam `config('app.timezone_exibicao')` — `APP_TIMEZONE_EXIBICAO`, padrão `America/Sao_Paulo`.
- **Desempenho:** o quadro carrega as negociações abertas do usuário (ou filtro) e os fechamentos dos últimos 30 dias; cada coluna exibe até 100 cards (os totais consideram todos).
- **Colunas recolhidas:** preferência por navegador (`localStorage`); Perdido começa recolhida e reabre sozinha durante o arraste.
- **Telas antigas:** a tela do orçamento (Consultor e Admin) recusa mudança de status de orçamento perdido; a linha do tempo mostra o tipo do evento (Funil, Contato, Perdido, Reativado, Responsável).

### Testes

`tests/Feature/Funil/` — `FunilQuadroTest` (posicionamento, escopo, filtros, totais, aging, saúde do card), `FunilPainelELoteTest` (painel, contatos, filtros da seção 22, reatribuição, lote), `FunilMovimentacaoTest` (todas as movimentações, permissões, concorrência, perda, reativação, follow-up) e `FunilConfiguracaoTest` (etapas, motivos, parâmetros, migration). Apoio em `tests/Concerns/CriaFunil.php`. Quadro e configuração estão na matriz de `ControleDeAcessoTest`.

## 22. Plano de evolução da experiência

Análise feita em 2026-10-06 sobre a Fase 1. As regras de negócio estão sólidas; o que falta é **experiência de uso e gestão**. As regras das seções 6 e 7 não mudam.

### 22.1 Diagnóstico

| Ponto | Situação na Fase 1 |
|---|---|
| Sair do quadro para ver qualquer coisa | Clicar no card abre a tela do orçamento e perde filtros e rolagem |
| Sem contato rápido | O card não traz telefone/WhatsApp (`clientes.celular` existe) |
| "Todo orçamento tem um próximo passo" (seção 1) | Nada sinaliza card **sem** próximo contato — só os atrasados |
| Sinais visuais concorrentes | Borda vermelha (contato), texto vermelho (SLA) e selos competem; não há um indicador único |
| Quadro trava a cada movimento | Todos os cards ficam não arrastáveis até a resposta, e cada movimento recarrega todas as props |
| Busca | Só dispara com Enter |
| Mobile | Colunas de 296 px com rolagem horizontal e arraste com toque longo |
| Gestão | Sem métricas, Recuperação nem "contatos de hoje" (Fase 2) |

### 22.2 Correções (antes das melhorias)

| # | Problema | Correção |
|---|---|---|
| C1 | Aprovar não limpava `proximo_contato_em`: card em **Ganho** ficava com contato "atrasado" (borda vermelha) e aparecia no filtro "Só atrasados" | Hook do model limpa o próximo contato quando o status entra em Ganho; `contato_atrasado` e o filtro só valem para negociações abertas e Em aprovação |
| C2 | Cabeçalho de **Em aprovação** não mostrava o ponderado, mas a previsão do topo o somava (95%) | Ponderado exibido também em Em aprovação |
| C3 | Quadro inteiro congelado durante a requisição | Só o card em envio fica bloqueado (com indicador); movimentos recarregam apenas `colunas`, `caixa` e `resumo` |
| C4 | Busca por número: `'12abc'` encontrava o #12 (o MySQL converte texto em número) | Busca por id só com número puro (`#123` ou `123`) |

### 22.3 Onda 1 — Visual

- **Saúde do card** (`card.saude`, calculada no servidor) — um único indicador no lugar dos sinais concorrentes:

  | Saúde | Regra (na ordem) |
  |---|---|
  | `atrasado` 🔴 | contato vencido **ou** SLA da etapa estourado |
  | `atencao` 🟡 | contato hoje (fuso `app.timezone_exibicao`) **ou** ≥ 70% do SLA da etapa |
  | `sem_passo` ⚪ | etapa aberta sem próximo contato |
  | `em_dia` 🟢 | demais casos |
  | `null` | Ganho, Perdido e caixa de entrada (a caixa usa o selo "esfriando") |

  Em aprovação nunca é `sem_passo` (o próximo passo é do administrador).
- **Card redesenhado**: cliente e valor em destaque, linha técnica (kWp · grupo · cidade), rodapé com saúde, próximo passo e barra de tempo na etapa (dias ÷ SLA).
- **Colunas** com fundo tingido pela cor da etapa e barra da participação da etapa no valor do funil.
- **Faixa do funil** no topo: valor por etapa aberta + Em aprovação; clicar rola até a coluna.
- Animação ao soltar, indicador de envio no card, **densidade compacta/confortável** (preferência no navegador), estados vazios melhores.

### 22.4 Onda 2 — Praticidade

Entregue em 2026-10-06. Diferenças em relação ao planejado: o filtro por **faixa de valor** ficou para depois (a ordenação por valor cobre o caso mais comum) e, no celular, "Mover para" usa o menu do card em vez de folha inferior (o arraste fica desligado no celular).

Regras definidas na implementação:

- **Reatribuir** só vale para negociação em andamento (caixa ou etapa aberta, não perdida); Em aprovação, Ganho e Perdido mantêm o responsável. O `comissao_percentual` gravado nos itens passa a ser o do novo consultor (a comissão é de quem fecha). Evento `responsavel` na linha do tempo. O cliente continua vinculado ao consultor original.
- **Lote** (admin): mover só para etapa aberta ativa e só cards em negociação — em lote não se aprova, reprova nem reativa. Cada card é validado individualmente; recusas não impedem os demais e a mensagem traz um exemplo.
- **Pedir data ao mover**: entre etapas abertas, se o card não tem próximo contato (pode-se escolher "Sem data").
- **Desfazer**: só movimentos entre etapas abertas (os demais mudam status). O desfazer é um novo movimento e também fica na linha do tempo.
- **Contato rápido**: `FunilService::contatos()` prefere o celular; número de 10–11 dígitos recebe DDI 55.

Planejado:

- **Painel lateral** ao clicar no card: cliente, contatos, kit, valor, linha do tempo e ações (registrar contato, mover, perder, reativar, abrir orçamento). Dados carregados sob demanda; rota entra na matriz de acesso.
- **WhatsApp (`wa.me`) e Ligar (`tel:`)** no card e no painel; ao voltar, oferece "Registrar contato?".
- **Sem próximo passo**: indicador no topo, filtro e pedido de data ao mover para etapa aberta.
- **Busca instantânea** (300 ms) e filtros em chips (SLA estourado, sem próximo passo, faixa de valor); escolha da ordenação (prioridade, valor, mais antigo).
- **Desfazer** (5 s) em movimentos entre etapas abertas.
- **Admin**: reatribuir consultor pelo quadro e ações em lote (mover, reatribuir, perder).
- **Atalhos**: `/` busca, `C` caixa de entrada, `Esc` fecha.
- **Mobile**: abas por etapa com lista vertical e "Mover para" em folha inferior.
- Botão **+ Novo orçamento** no quadro.

### 22.5 Onda 3 — Gestão (= Fase 2)

- Visões **Quadro | Lista | Agenda** sobre o mesmo filtro (Lista com CSV; Agenda com hoje, atrasados e próximos 7 dias).
- Aba **Recuperação** (seção 9), **métricas** (conversão e tempo médio por etapa, ranking de motivos, por consultor) e **contatos de hoje** nos dashboards.
- Atualização automática leve (recarga parcial a cada 60 s com a aba visível — sem websockets nem fila).
- Opcional: `orcamentos.previsao_fechamento` para previsão de receita por mês.

### 22.6 Onda 4 — Atividades (= Fase 3)

Tipos de contato (ligação, WhatsApp, e-mail, visita, reunião), modelos de mensagem com link da proposta pública, automações por etapa e etiquetas.

### 22.7 Testes

Cada correção tem teste que falha sem ela; a saúde do card é coberta regra a regra em `FunilQuadroTest`; rotas novas (painel, reatribuição, lote) entram em `ControleDeAcessoTest` com dono × outro consultor × admin × visitante. O frontend não tem testes automatizados: `tsc`, `npm run build` e conferência manual com admin e consultor (desktop e mobile).

### 22.8 Andamento

| Item | Situação |
|---|---|
| C1–C4 | ✅ concluído (testes em `FunilQuadroTest`) |
| Onda 1 | ✅ concluído |
| Onda 2 | ✅ concluído (exceto filtro por faixa de valor) |
| Onda 3 / 4 | planejado (Fases 2 e 3) |
