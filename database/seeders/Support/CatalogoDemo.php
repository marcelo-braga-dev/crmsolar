<?php

namespace Database\Seeders\Support;

/**
 * Catálogos fixos da base de demonstração (DEMO.md). Tudo aqui é fictício; o
 * DemoDadosFicticiosSeeder ainda marca nomes, documentos e contatos como tal.
 */
final class CatalogoDemo
{
    /** Meses de operação simulados (a empresa "abriu" há N meses). */
    public const MESES = 16;

    /** Domínio dos logins da demo e dos e-mails de clientes. */
    public const DOMINIO_EQUIPE = 'crmsolar.demo';

    public const DOMINIO_CLIENTES = 'cliente.demo';

    public const SENHA_PADRAO = 'demo@2026';

    public const EMPRESA = [
        'empresa_nome' => 'Sol Nascente Energia Solar',
        'empresa_telefone' => '(20) 0000-0100',
        'empresa_email' => 'contato@'.self::DOMINIO_EQUIPE,
    ];

    /**
     * Equipe. entrada/saida = mês de operação (0 = primeiro mês). ritmo multiplica o volume
     * de novos clientes. cidades = carteira (UF => cidades) — sempre na mesma região do cliente.
     */
    public const EQUIPE = [
        ['nome' => 'Ricardo Mendes', 'login' => 'admin', 'tipo' => 'admin', 'genero' => 'm', 'entrada' => 0],
        ['nome' => 'Juliana Prado', 'login' => 'gerente', 'tipo' => 'admin', 'genero' => 'f', 'entrada' => 2],
        ['nome' => 'Lucas Ferreira', 'login' => 'consultor', 'tipo' => 'consultor', 'genero' => 'm', 'entrada' => 0, 'comissao' => 5.0, 'ritmo' => 1.3,
            'cidades' => ['SP' => ['Campinas', 'Valinhos', 'Indaiatuba', 'Jundiaí']]],
        ['nome' => 'Mariana Costa', 'login' => 'mariana.costa', 'tipo' => 'consultor', 'genero' => 'f', 'entrada' => 0, 'comissao' => 4.5, 'ritmo' => 1.1,
            'cidades' => ['SP' => ['Ribeirão Preto', 'Sertãozinho', 'Franca']]],
        ['nome' => 'Diego Martins', 'login' => 'diego.martins', 'tipo' => 'consultor', 'genero' => 'm', 'entrada' => 1, 'saida' => 10, 'comissao' => 4.0, 'ritmo' => 0.9,
            'cidades' => ['SP' => ['Piracicaba', 'Limeira', 'Americana']]],
        ['nome' => 'Rafael Oliveira', 'login' => 'rafael.oliveira', 'tipo' => 'consultor', 'genero' => 'm', 'entrada' => 3, 'comissao' => 4.5, 'ritmo' => 1.0,
            'cidades' => ['MG' => ['Uberlândia', 'Araguari', 'Patos de Minas', 'Ituiutaba']]],
        ['nome' => 'Camila Santos', 'login' => 'camila.santos', 'tipo' => 'consultor', 'genero' => 'f', 'entrada' => 5, 'comissao' => 4.0, 'ritmo' => 1.05,
            'cidades' => ['SP' => ['Sorocaba', 'Itu']]],
        ['nome' => 'Bruno Almeida', 'login' => 'bruno.almeida', 'tipo' => 'consultor', 'genero' => 'm', 'entrada' => 7, 'comissao' => 4.0, 'ritmo' => 1.0,
            'cidades' => ['GO' => ['Goiânia', 'Aparecida de Goiânia', 'Anápolis', 'Rio Verde', 'Jataí']]],
        ['nome' => 'Patrícia Lima', 'login' => 'patricia.lima', 'tipo' => 'consultor', 'genero' => 'f', 'entrada' => 9, 'comissao' => 4.0, 'ritmo' => 0.95,
            'cidades' => ['PR' => ['Londrina', 'Maringá', 'Cambé', 'Apucarana', 'Arapongas']]],
        // Assume a carteira de quem saiu (Diego) no mês da saída.
        ['nome' => 'Thiago Barbosa', 'login' => 'thiago.barbosa', 'tipo' => 'consultor', 'genero' => 'm', 'entrada' => 10, 'comissao' => 4.0, 'ritmo' => 0.95,
            'cidades' => ['SP' => ['Piracicaba', 'Limeira', 'Americana', 'São Carlos', 'Araraquara']]],
        ['nome' => 'Fernanda Rocha', 'login' => 'fernanda.rocha', 'tipo' => 'consultor', 'genero' => 'f', 'entrada' => self::MESES - 2, 'comissao' => 3.5, 'ritmo' => 0.8,
            'cidades' => ['MG' => ['Uberaba']]],
    ];

    /** Cidade => [prefixo de CEP, concessionária]. A concessionária é sempre do estado do cliente. */
    public const CIDADES = [
        'SP' => [
            'Campinas' => ['130', 'CPFL Paulista'], 'Valinhos' => ['132', 'CPFL Paulista'], 'Indaiatuba' => ['133', 'CPFL Paulista'],
            'Jundiaí' => ['132', 'CPFL Paulista'], 'Ribeirão Preto' => ['140', 'CPFL Paulista'], 'Sertãozinho' => ['141', 'CPFL Paulista'],
            'Franca' => ['144', 'CPFL Paulista'], 'Piracicaba' => ['134', 'CPFL Paulista'], 'Limeira' => ['134', 'Elektro (Neoenergia SP)'],
            'Americana' => ['134', 'CPFL Paulista'], 'São Carlos' => ['135', 'CPFL Paulista'], 'Araraquara' => ['148', 'CPFL Paulista'],
            'Sorocaba' => ['180', 'CPFL Piratininga'], 'Itu' => ['133', 'CPFL Piratininga'],
        ],
        'MG' => ['Uberlândia' => ['384', 'CEMIG'], 'Araguari' => ['384', 'CEMIG'], 'Patos de Minas' => ['387', 'CEMIG'], 'Ituiutaba' => ['383', 'CEMIG'], 'Uberaba' => ['380', 'CEMIG']],
        'GO' => ['Goiânia' => ['740', 'Enel Goiás'], 'Aparecida de Goiânia' => ['749', 'Enel Goiás'], 'Anápolis' => ['750', 'Enel Goiás'], 'Rio Verde' => ['759', 'Enel Goiás'], 'Jataí' => ['758', 'Enel Goiás']],
        'PR' => ['Londrina' => ['860', 'Copel'], 'Maringá' => ['870', 'Copel'], 'Cambé' => ['861', 'Copel'], 'Apucarana' => ['868', 'Copel'], 'Arapongas' => ['867', 'Copel']],
    ];

    /** Interior com mais clientes rurais. */
    public const PESO_RURAL = ['SP' => 0.08, 'MG' => 0.16, 'GO' => 0.2, 'PR' => 0.14];

    public const NOMES_M = [
        'João', 'José', 'Antônio', 'Carlos', 'Paulo', 'Pedro', 'Marcos', 'Luiz', 'Gabriel', 'Rafael', 'Daniel', 'Marcelo', 'Bruno', 'Eduardo',
        'Felipe', 'Rodrigo', 'Gustavo', 'André', 'Fernando', 'Fábio', 'Leonardo', 'Vinícius', 'Mateus', 'Renato', 'Sérgio', 'Roberto', 'Hiroshi',
        'Kenji', 'Samir', 'Omar', 'Giovanni', 'Enzo', 'Otávio', 'Wagner', 'Adriano', 'Cláudio', 'Igor', 'Henrique', 'Murilo', 'Caio',
        'Benedito', 'Raimundo', 'Edson', 'Gilberto', 'Valdir', 'Jorge', 'Alexandre', 'Ricardo', 'Emerson', 'Wellington',
    ];

    public const NOMES_F = [
        'Maria', 'Ana', 'Francisca', 'Antônia', 'Adriana', 'Juliana', 'Márcia', 'Fernanda', 'Patrícia', 'Aline', 'Sandra', 'Camila', 'Amanda',
        'Bruna', 'Jéssica', 'Letícia', 'Larissa', 'Vanessa', 'Beatriz', 'Gabriela', 'Renata', 'Cristina', 'Simone', 'Luciana', 'Tatiane',
        'Yumi', 'Akemi', 'Leila', 'Samira', 'Giulia', 'Helena', 'Isabela', 'Valéria', 'Rosângela', 'Eliane', 'Débora', 'Priscila', 'Natália',
        'Carolina', 'Raquel', 'Sônia', 'Vera', 'Regina', 'Lúcia', 'Teresa', 'Mônica', 'Viviane', 'Daniela', 'Thaís', 'Marta', 'Mariana',
    ];

    public const SOBRENOMES = [
        'Silva', 'Santos', 'Oliveira', 'Souza', 'Rodrigues', 'Ferreira', 'Alves', 'Pereira', 'Lima', 'Gomes', 'Costa', 'Ribeiro', 'Martins',
        'Carvalho', 'Almeida', 'Lopes', 'Soares', 'Fernandes', 'Vieira', 'Barbosa', 'Rocha', 'Dias', 'Nascimento', 'Andrade', 'Moreira',
        'Nunes', 'Marques', 'Machado', 'Mendes', 'Freitas', 'Cardoso', 'Ramos', 'Gonçalves', 'Santana', 'Teixeira', 'Tanaka', 'Yamamoto',
        'Nakamura', 'Rossi', 'Bertolini', 'Ferraz', 'Haddad', 'Nassar', 'Schmidt', 'Müller', 'Kowalski', 'Bianchi', 'Moraes', 'Prado',
        'Campos', 'Toledo', 'Queiroz', 'Barros', 'Pinheiro', 'Cunha', 'Azevedo', 'Brandão', 'Siqueira', 'Garcia', 'Sato',
    ];

    public const RUAS = [
        'Rua das Acácias', 'Rua dos Ipês', 'Avenida Brasil', 'Rua São José', 'Rua Sete de Setembro', 'Rua XV de Novembro', 'Avenida das Nações',
        'Rua Tiradentes', 'Rua Santos Dumont', 'Rua Rui Barbosa', 'Avenida Independência', 'Rua das Palmeiras', 'Rua Marechal Deodoro',
        'Rua Barão de Mauá', 'Rua Dom Pedro II', 'Rua Padre Anchieta', 'Avenida Getúlio Vargas', 'Rua dos Jacarandás', 'Rua Bela Vista',
        'Rua Monte Alegre', 'Rua Primavera', 'Rua do Comércio', 'Avenida Industrial', 'Rua dos Girassóis', 'Rua Esperança',
    ];

    public const BAIRROS = [
        'Centro', 'Jardim América', 'Vila Nova', 'Jardim Europa', 'Parque Industrial', 'Jardim Paulista', 'Vila Rica', 'Santa Mônica',
        'Jardim das Flores', 'Cidade Jardim', 'Bela Vista', 'Jardim Botânico', 'Vila Maria', 'Distrito Industrial', 'Alto da Boa Vista',
        'Jardim Primavera', 'Residencial Solar', 'Parque dos Lagos', 'Vila Operária', 'Jardim Bandeirantes',
    ];

    /**
     * Segmentos de pessoa jurídica: [nome base, grupo, consumo mín, consumo máx (kWh/mês), horas de funcionamento].
     * Uma padaria consome mais que uma residência; uma indústria A4, muito mais.
     */
    public const SEGMENTOS_B3 = [
        ['Padaria', 2200, 6500, 14], ['Supermercado', 4500, 12000, 14], ['Restaurante', 1800, 5200, 12], ['Clínica Odontológica', 900, 2600, 10],
        ['Academia', 2200, 5800, 16], ['Auto Center', 1300, 3600, 10], ['Escola', 1600, 4800, 10], ['Pousada', 1500, 4200, 24],
        ['Farmácia', 1100, 2800, 14], ['Pet Shop', 800, 2100, 10], ['Lavanderia', 2400, 6000, 12], ['Sorveteria', 1500, 4000, 12],
        ['Marcenaria', 1800, 4500, 10], ['Escritório Contábil', 700, 1800, 9], ['Hotel', 4200, 11000, 24], ['Laboratório de Análises', 1600, 4200, 12],
    ];

    public const SEGMENTOS_A = [
        ['Indústria de Plásticos', 9000, 26000, 180, 'A4'], ['Frigorífico', 12000, 32000, 220, 'A4'], ['Cerâmica', 8000, 22000, 160, 'A4'],
        ['Metalúrgica', 7000, 20000, 140, 'A4'], ['Atacadista', 8000, 19000, 130, 'A4'], ['Hospital', 10000, 26000, 170, 'A4'],
        ['Laticínio', 9000, 24000, 150, 'A4'], ['Cooperativa Agroindustrial', 18000, 42000, 300, 'A3a'], ['Têxtil', 9000, 26000, 170, 'A4'],
    ];

    /** Nomes fantasia combinados com o segmento ("Padaria Pão Dourado"). */
    public const FANTASIAS = [
        'Bom Jesus', 'São Francisco', 'Primavera', 'Nova Era', 'Santa Clara', 'Boa Vista', 'Estrela', 'Horizonte', 'Pioneira', 'Real',
        'Central', 'do Vale', 'Ipê Amarelo', 'Três Irmãos', 'Bela Vista', 'Aurora', 'Imperial', 'Paulista', 'Mineira', 'Cerrado', 'Paraná',
        'Sol Maior', 'Esperança', 'Progresso', 'União', 'Vitória', 'Brasil', 'Alvorada', 'Recanto', 'Monte Verde',
    ];

    public const SUFIXOS_PJ = ['Ltda.', 'Ltda.', 'ME', 'EIRELI', 'S.A.', 'Ltda.'];

    /** Rural (B2): [tipo_instalacao do formulário, rótulo, consumo mín, máx, bombeamento?]. */
    public const PERFIS_RURAIS = [
        ['residencial_rural', 'Sítio', 350, 1200, false], ['produtivo', 'Granja', 2500, 9000, false], ['produtivo', 'Fazenda Leiteira', 1800, 6500, false],
        ['irrigacao', 'Fazenda', 1500, 5500, true], ['produtivo', 'Chácara', 600, 1800, false],
    ];

    public const ORIGENS_LEAD = ['Site', 'WhatsApp', 'Indicação', 'Instagram', 'Google Ads', 'Facebook', 'Feira Agro', 'Parceiro instalador'];

    /** Pesos das origens: indicação e WhatsApp dominam no interior. */
    public const PESO_ORIGENS = [22, 20, 18, 12, 12, 6, 4, 6];

    /** Sazonalidade por mês do ano (1 = jan): mais vendas antes do verão, menos no inverno e no fim do ano. */
    public const SAZONALIDADE = [1 => 0.95, 0.9, 1.0, 1.0, 0.95, 0.85, 0.85, 1.0, 1.05, 1.15, 1.15, 0.9];

    public const NOTAS_CONTATO = [
        'Cliente pediu para detalhar o payback com a conta dos últimos 12 meses.',
        'Enviei a proposta pelo WhatsApp; ficou de analisar com a família.',
        'Conversamos por telefone; dúvida sobre garantia dos inversores.',
        'Cliente quer comparar com outra proposta que recebeu.',
        'Pediu simulação de financiamento em 60x.',
        'Agendei apresentação presencial da proposta.',
        'Esclareci a regra de compensação da Lei 14.300.',
        'Cliente aprovou o layout no telhado; aguardando decisão do sócio.',
        'Solicitou desconto para pagamento à vista.',
        'Retornou dizendo que vai decidir até o fim do mês.',
        'Enviei vídeo de instalação semelhante na região.',
        'Pediu visita técnica para avaliar o telhado.',
        'Cliente está viajando; combinamos retorno na próxima semana.',
        'Reunião com o financeiro da empresa para fechar condições.',
        'Enviada nova versão com inversor de outra marca.',
    ];

    /** Observação da perda por motivo (nome do motivo padrão da migration). */
    public const OBSERVACOES_PERDA = [
        'Adiou a decisão' => ['Vai esperar o 13º para decidir.', 'Reforma da casa adiou o projeto.', 'Decidiu esperar o próximo ano.'],
        'Preço alto' => ['Achou o investimento alto para o momento.', 'Esperava pagar cerca de 15% menos.'],
        'Sem retorno do cliente' => ['Não responde desde a apresentação da proposta.', 'Três tentativas de contato sem retorno.'],
        'Crédito/financiamento negado' => ['Financiamento negado pelo banco.', 'Score insuficiente para o prazo pedido.'],
        'Desistiu do projeto' => ['Vai mudar de imóvel.', 'Priorizou outro investimento na empresa.'],
        'Fechou com concorrente' => ['Fechou com empresa local por preço menor.', 'Concorrente ofereceu prazo maior de pagamento.'],
        'Inviabilidade técnica' => ['Telhado sem condições estruturais.', 'Sombreamento severo inviabiliza a geração.'],
    ];

    /** Propostas de serviço oferecidas a quem já tem usina instalada: [título, valor mín, máx]. */
    public const SERVICOS = [
        ['Limpeza dos módulos fotovoltaicos', 280, 900], ['Manutenção preventiva anual (O&M)', 650, 2400],
        ['Instalação de carregador para carro elétrico', 2800, 6500], ['Laudo técnico e termografia', 900, 2200],
        ['Monitoramento remoto — plano anual', 480, 1200], ['Troca de string box e proteções', 750, 1900],
    ];
}
