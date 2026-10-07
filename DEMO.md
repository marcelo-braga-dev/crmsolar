# Demonstração da plataforma

Instalação separada da plataforma, aberta a quem pode comprar. Tem duas partes:

1. **Base fictícia** (`MarketingDemoSeeder`): uma empresa de energia solar operando há 16 meses, com todas as telas cheias, coerentes e com trabalho pendente hoje.
2. **Modo demonstração** (`DEMO_MODE=true`): o visitante se identifica (nome + e-mail ou telefone), entra **sem senha**, troca de perfil com um clique e **não consegue criar, editar nem excluir nada**. Cada visitante vira um lead para a equipe comercial.

É o **mesmo código** da plataforma: com `DEMO_MODE` desligado (padrão) nada muda — login com senha, nada bloqueado, nenhum componente de demo carregado e as rotas `/demo` respondendo 404.

## Modo demonstração

| Item | Como funciona |
|---|---|
| Login | `/login` mostra a página **Acesse a demonstração** (sem senha): nome, e-mail e/ou telefone, empresa (opcional) e aceite de contato. Os UTMs da URL vão junto |
| Primeiro acesso | o visitante é gravado em `demo_visitantes` (quem volta com o mesmo e-mail **ou** telefone não duplica: soma uma visita), a sessão é regenerada e ele entra no perfil inicial (`DEMO_INITIAL_ROLE`, padrão admin). Na primeira tela, um modal de boas-vindas aponta a barra |
| Troca de perfil | barra fixa no rodapé ("Ver como: Administrador · Consultor"); cada perfil usa uma conta da base fictícia (`DEMO_USER_*`) |
| Somente leitura | **garantido no servidor** pelo middleware `BloqueiaEscritaNaDemonstracao`: POST/PUT/PATCH/DELETE e as telas `.create`/`.edit`/`.senha` são negados (navegação volta com o aviso "Acesso de teste: criar, editar e excluir estão desativados nesta demonstração."; chamada de API recebe 403 em JSON). No navegador, a guarda `demoGuard` esmaece os botões de ação e avisa em vez de abrir formulários |
| Liberados | logout, rotas `/demo`, `readonly_post_routes` (cálculos do dimensionamento e busca de kits) e `readonly_form_routes` (telas do simulador por grupo tarifário: o visitante dimensiona e vê kits e payback; só o "Salvar" é bloqueado) |
| Senha | esqueci a senha, redefinição e confirmação levam ao acesso de demonstração; login com senha é bloqueado |
| Rotinas | o agendador não roda a sincronização Edeltec (nem nada que importe ou envie) com `DEMO_MODE` ligado; sem cron de integrações nem worker de fila |
| Visitantes | `GET /demo/visitantes.csv?token=DEMO_LEADS_TOKEN` (404 sem o token certo) ou `php artisan demo:visitantes [arquivo.csv] [--desde=AAAA-MM-DD]`. Colunas: nome, e-mail, telefone, empresa, primeiro e último acesso, visitas, telas vistas, perfis usados e UTMs. CSV com `;` e BOM (abre direto no Excel) |

### Links para campanhas

Os UTMs da URL ficam gravados no visitante:

```
https://demo.seu-dominio/login?utm_source=linkedin&utm_medium=post&utm_campaign=lancamento-out26
https://demo.seu-dominio/login?utm_source=whatsapp&utm_campaign=indicacao
```

### Perfis × conta

| Perfil | Variável | Conta padrão (base fictícia) |
|---|---|---|
| Administrador | `DEMO_USER_ADMIN` | `admin@crmsolar.demo` |
| Consultor | `DEMO_USER_CONSULTOR` | `consultor@crmsolar.demo` |

Se o e-mail configurado não existir, usa o primeiro usuário ativo do perfil; sem nenhum, a entrada falha com "Rode o seeder de demonstração". A lista de perfis fica em `App\Services\Demo\ModoDemonstracao::PERFIS`.

### Regra para quem desenvolve

**Toda nova rota POST que só lê ou calcula precisa entrar em `readonly_post_routes` (`config/demo.php`)** — senão fica bloqueada na demonstração. Telas `.create` que servem de vitrine (calculam sem gravar) entram em `readonly_form_routes`.

> ⚠️ **Nunca rode no banco de desenvolvimento ou de produção.** O seeder só funciona com `DEMO_SEED_PERMITIDO=true` e deve ser usado numa instalação com domínio e banco próprios.

## O que a base contém

A "Sol Nascente Energia Solar (empresa fictícia)" abre há 16 meses e cresce mês a mês (sazonalidade e ruído de ±10–12%; nada é igual de um mês para o outro):

| Área | Conteúdo (base completa) |
|---|---|
| Equipe | 2 admins (diretor e gerente comercial) e 9 consultores contratados ao longo do tempo — um desligado no 10º mês (carteira em andamento reatribuída ao substituto) e uma recém-contratada |
| Clientes e leads | ~620 clientes PF e PJ (residências, comércio, rural, indústria) em 29 cidades de SP, MG, GO e PR — sempre na região do consultor e com a concessionária do estado; ~630 leads de 8 origens (convertidos, perdidos, na fila do admin) |
| Orçamentos | ~600, calculados com os **mesmos serviços do sistema** (dimensionamento, precificação em 3 camadas, análise econômica) para B1, B2, B3, A4 e A3a |
| Funil | cada orçamento percorre o funil pelo `FunilService`: atendimento, contatos, visita técnica, negociação, financiamento, aprovação (às vezes reprovação e reenvio), ou perda com motivo — alguns reativados depois |
| Pós-venda | contratos gerados e assinados, instalação, finalização, propostas de serviço (limpeza, O&M, carregador veicular) e ampliações |
| Hoje | negociações em todas as etapas (em dia, atenção, atrasadas, sem próximo passo), caixa de entrada com itens esfriando, aprovações pendentes, visitas agendadas, contratos aguardando assinatura, instalações em andamento e vendas já fechadas no mês |
| Casos-problema | negócios parados há meses (contato vencido, prazo da etapa estourado), contrato cancelado após assinatura, visita cancelada e reagendada, kits retirados pelo fornecedor, falha na sincronização Edeltec |
| Histórico | linha do tempo de cada orçamento, Auditoria com quem fez o quê e quando (inclusive reajustes de tarifa, margens, prazos do funil e comissão), ~110 execuções de integração |

As datas são **relativas a hoje**: rodar de novo daqui a meses gera uma base igualmente atual. A geração é **determinística** (semente fixa em `MarketingDemoSeeder::SEMENTE`): mesma data, mesma base.

### Dados claramente fictícios

O `DemoDadosFicticiosSeeder` roda no fim (e pode rodar de novo sem duplicar nada):

- nomes de pessoas e empresas com **"(fictício)" / "(fictícia)"** — clientes, leads, fornecedores e a própria equipe;
- CPF/CNPJ/RG começando com zeros e derivados do id (`000.001.234-xx`, `00.000.123/0001-xx`);
- telefones com DDD **20** (inexistente) e prefixo **0000**; e-mails em `@cliente.demo` e `@crmsolar.demo`;
- formas de pagamento com recebedor **EMPRESA FICTICIA**; IP de assinatura na faixa de documentação (192.0.2.x);
- PDFs de orçamento e contrato com a faixa **"Documento de demonstração com dados fictícios"** (configuração `demo_aviso`, que só existe nesta base);
- textos que copiaram nomes (contratos, linha do tempo, anotações, Auditoria) atualizados para os nomes marcados.

**O nome da distribuidora integrada é confidencial** e não aparece na demonstração: o fornecedor da integração se chama "Distribuidora Parceira (fictícia)" (SKUs `DPA-`, contatos fictícios) e é encontrado pela coluna `fornecedores.integracao = 'distribuidora'`, não pelo nome. Com `DEMO_MODE` ligado, menu, títulos e histórico dizem só "Distribuidora" (`App\Services\Integracoes\NomeDistribuidora`); URL, nomes de rota (Ziggy) e componente são genéricos (`/admin/integracoes/distribuidora`). Bases geradas antes disso são corrigidas rodando de novo o `DemoDadosFicticiosSeeder`. `MarketingDemoSeederTest` e `IntegracaoEdeltecTest` falham se o nome voltar a aparecer nos dados ou no HTML. Marcas de equipamentos (Jinko, Growatt…) e concessionárias são dados de referência.

## Como subir a instância

1. Clone o projeto num diretório próprio, com **domínio e banco de dados próprios** (ex.: `demo.crmsolar...` e banco `crmsolar_demo`).
2. `.env` sem nenhuma credencial real:

   ```dotenv
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://demo.seu-dominio
   DB_DATABASE=crmsolar_demo            # banco exclusivo da demo
   MAIL_MAILER=log                      # nenhum e-mail sai
   QUEUE_CONNECTION=sync
   EDELTEC_API_KEY=                     # vazio: a integração nunca chama a API real
   EDELTEC_SECRET=
   EDELTEC_SYNC_FILA=false
   DEMO_SEED_PERMITIDO=true
   DEMO_MODE=false                      # liga só no passo 5, depois da identidade visual
   DEMO_LEADS_TOKEN=uma-chave-longa-e-aleatoria
   # DEMO_USER_ADMIN=admin@crmsolar.demo
   # DEMO_USER_CONSULTOR=consultor@crmsolar.demo
   # DEMO_INITIAL_ROLE=admin
   ```

3. **Sem cron de integrações nem worker** nesta instalação (com `DEMO_MODE` ligado o agendador já não roda a sincronização Edeltec; o histórico vem pronto).
4. Instale e gere a base:

   ```bash
   composer install --no-dev && npm ci && npm run build
   php artisan key:generate
   php artisan config:clear                       # o seeder lê DEMO_SEED_PERMITIDO da configuração
   php artisan migrate:fresh --force
   php artisan db:seed --class=MarketingDemoSeeder --force   # ~1 minuto; imprime o resumo e os logins
   php artisan optimize
   ```

   O `MarketingDemoSeeder` popula sozinho as tabelas de referência (cidades, irradiação, concessionárias, parâmetros) quando estão vazias. Rodar `migrate:fresh --seed` antes também funciona, mas traz os dados de teste do `DadosTesteSeeder` (que também recebem a marcação de fictícios).

5. **Configure a identidade visual antes de ligar o modo** (com o modo ligado ninguém consegue salvar): entre como `admin@crmsolar.demo` (senha `demo@2026`), ajuste nome, logos e cores em Configurações → Identidade visual. Depois defina `DEMO_MODE=true` no `.env` e rode `php artisan config:clear && php artisan optimize`.
6. Confira: `/login` mostra "Acesse a demonstração"; entre, troque de perfil pela barra e tente salvar algo (deve aparecer o aviso de acesso de teste).

## Contas

Senha de todas: **`demo@2026`**

| Perfil | Login | O que mostra |
|---|---|---|
| Admin (diretor) | `admin@crmsolar.demo` | visão geral, aprovações, financeiro, auditoria, configurações |
| Admin (gerente comercial) | `gerente@crmsolar.demo` | mesma área; aparece como autora de aprovações e ajustes de margem |
| Consultor (melhor carteira) | `consultor@crmsolar.demo` | Campinas e região — maior volume, funil cheio, comissões |
| Consultora | `mariana.costa@crmsolar.demo` | Ribeirão Preto e região |
| Consultor | `rafael.oliveira@crmsolar.demo` | Triângulo Mineiro (mais clientes rurais) |
| Consultora | `camila.santos@crmsolar.demo` | Sorocaba e Itu |
| Consultor | `bruno.almeida@crmsolar.demo` | Goiás |
| Consultora | `patricia.lima@crmsolar.demo` | norte do Paraná |
| Consultor | `thiago.barbosa@crmsolar.demo` | herdou a carteira do consultor desligado |
| Consultora (recém-contratada) | `fernanda.rocha@crmsolar.demo` | carteira pequena, primeiros negócios |
| Consultor desligado | `diego.martins@crmsolar.demo` | login bloqueado (usuário inativo) — o histórico dele aparece nos relatórios |

## Renovar a base

Como tudo é relativo a "hoje", basta recriar periodicamente (ex.: toda segunda de madrugada) para os gráficos e o funil acompanharem o calendário. **Exporte os visitantes antes**: o `migrate:fresh` apaga a tabela `demo_visitantes` (e a identidade visual — reaplique-a ou guarde um backup da tabela `configs` e da pasta `storage/app/public/identidade`).

```bash
php artisan demo:visitantes storage/app/visitantes-$(date +%F).csv
php artisan config:clear && php artisan migrate:fresh --force \
  && php artisan db:seed --class=MarketingDemoSeeder --force && php artisan optimize
```

Rodar o seeder de novo **sem** `migrate:fresh` não faz nada (ele detecta a base existente e avisa).

## Como funciona (para quem for mexer)

| Arquivo | Papel |
|---|---|
| `database/seeders/MarketingDemoSeeder.php` | orquestrador: trava de segurança, referências, semente, transação, resumo |
| `database/seeders/DemoCatalogoSeeder.php` | fornecedores, produtos, ~650 kits (2 a 115 kWp em 5 estruturas e 2 tensões), margens, bancos, configurações |
| `database/seeders/Support/SimuladorComercial.php` | a história: equipe, clientes, leads e o ciclo de cada orçamento pelo `FunilService` |
| `database/seeders/Support/Agenda.php` | "viagem no tempo": cada evento roda com `Carbon::setTestNow()` na data dele e o usuário autenticado — timestamps, linha do tempo, relógio da etapa e Auditoria saem como na operação real; eventos depois de hoje ficam pendentes |
| `database/seeders/Support/FabricaOrcamento.php` | orçamento como o `BaseGrupoController::salvarOrcamento` (mesmos serviços e campos) |
| `database/seeders/DemoIntegracoesSeeder.php` | histórico Edeltec/planilha no formato do `EdeltecImportService` |
| `database/seeders/DemoDadosFicticiosSeeder.php` | marcação de dados fictícios (idempotente) |
| `database/seeders/Support/CatalogoDemo.php`, `Ficticio.php`, `Sorteio.php` | catálogos fixos, formatos fictícios e sorteio determinístico |

### Arquivos do modo demonstração

| Arquivo | Papel |
|---|---|
| `config/demo.php` | `enabled`, contas por perfil, perfil inicial, token do CSV, `readonly_post_routes`, `readonly_form_routes` |
| `app/Services/Demo/ModoDemonstracao.php` | toda a lógica: registrar visitante, entrar como perfil, contar telas, props compartilhadas, rotas liberadas |
| `app/Http/Middleware/BloqueiaEscritaNaDemonstracao.php` | a garantia real (grupo `web`, depois do Inertia) |
| `app/Http/Controllers/DemoController.php` + `routes/web.php` (`demo.*`) | acesso (10/min), troca de perfil (60/min), CSV de visitantes (10/min) |
| `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | troca o login pela página de acesso quando ligado |
| `app/Services/Demo/ExportacaoVisitantes.php`, `app/Console/Commands/DemoVisitantesCommand.php` | exportação (rota e comando) |
| `app/Models/DemoVisitante.php`, migration `2026_10_07_000000_create_demo_visitantes` | visitantes |
| `resources/js/Pages/Demo/Acesso.tsx` | página de acesso (vitrine + formulário) |
| `resources/js/Components/Demo/DemoBar.tsx` | barra do rodapé, boas-vindas e aviso de bloqueio (montada na raiz em `app.tsx`, só baixada com o modo ligado) |
| `resources/js/Components/Demo/demoGuard.ts` | esmaece botões de ação, intercepta cliques, navegação do Inertia, axios e fetch |
| `routes/console.php` | rotinas desligadas com o modo ligado |
| `tests/Feature/Demo/ModoDemonstracaoTest.php` | modo desligado (nada muda) e ligado (acesso, validação, retorno, troca de perfil, props, bloqueios, contagem, senha, CSV, comando) |

Teste da base: `tests/Feature/Demo/MarketingDemoSeederTest.php` roda a simulação num SQLite em memória (com `MarketingDemoSeeder::$escala = 0.35`: mesmos 16 meses e fluxos, menos registros) e confere volumes por etapa, ausência de datas no futuro, coerência do fluxo, marcação de fictícios e telas cheias em todos os perfis.

## O que a plataforma não tem (e por isso a demo não simula)

Tickets de suporte, notificações e log de acesso/login não existem no CRM.
