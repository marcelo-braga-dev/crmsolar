# Base de demonstração (marketing)

Instalação separada da plataforma, com **dados 100% fictícios**, que parece uma empresa de energia solar operando de verdade há 16 meses. Serve para apresentar o produto: todas as telas (dashboards, funil, listas, financeiro, auditoria, integrações) ficam cheias, coerentes e com trabalho pendente hoje.

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

O fornecedor **Edeltec** mantém o nome (a tela Integrações → Edeltec o procura), mas CNPJ e contatos são fictícios. Marcas de equipamentos (Jinko, Growatt…) e concessionárias são dados de referência.

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
   ```

3. **Sem cron nem worker** nesta instalação (a sincronização Edeltec agendada não deve rodar: o histórico já vem pronto).
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

Como tudo é relativo a "hoje", basta recriar periodicamente (ex.: toda segunda de madrugada) para os gráficos e o funil acompanharem o calendário:

```bash
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

Teste: `tests/Feature/Demo/MarketingDemoSeederTest.php` roda a simulação num SQLite em memória (com `MarketingDemoSeeder::$escala = 0.35`: mesmos 16 meses e fluxos, menos registros) e confere volumes por etapa, ausência de datas no futuro, coerência do fluxo, marcação de fictícios e telas cheias em todos os perfis.

## O que a plataforma não tem (e por isso a demo não simula)

Tickets de suporte, notificações e log de acesso/login não existem no CRM. O "modo demonstração" com login sem senha, troca de perfil com um clique e somente leitura garantida no servidor também não foi implementado — é um próximo passo opcional.
