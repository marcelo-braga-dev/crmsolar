<?php

/*
| Modo demonstração (DEMO.md): instalação aberta a visitantes, sem senha, com troca de perfil
| e somente leitura garantida no servidor (middleware BloqueiaEscritaNaDemonstracao).
| Desligado por padrão: nada muda e as rotas /demo respondem 404.
*/

return [

    'enabled' => (bool) env('DEMO_MODE', false),

    // Conta da base fictícia usada em cada perfil (MarketingDemoSeeder). Se o e-mail não
    // existir, usa o primeiro usuário ativo daquele perfil.
    'users' => [
        'admin' => env('DEMO_USER_ADMIN', 'admin@crmsolar.demo'),
        'consultor' => env('DEMO_USER_CONSULTOR', 'consultor@crmsolar.demo'),
    ],

    // Perfil em que o visitante entra primeiro.
    'initial_role' => env('DEMO_INITIAL_ROLE', 'admin'),

    // Chave para baixar os visitantes em CSV (/demo/visitantes.csv?token=...). Vazio = desativado.
    'leads_token' => env('DEMO_LEADS_TOKEN', ''),

    // Rotas POST que só leem/calculam (não gravam nada). Toda rota nova desse tipo precisa
    // entrar aqui, senão fica bloqueada na demonstração.
    'readonly_post_routes' => [
        'consultor.grupo.b1.calcular',
        'consultor.grupo.b2.calcular',
        'consultor.grupo.b3.calcular',
        'consultor.grupo.a.calcular',
        'consultor.dimensionamento.buscar_kits',
        'consultor.dimensionamento.demanda.buscar_kits',
    ],

    // Telas de formulário (.create) liberadas porque também servem de vitrine: o simulador de
    // dimensionamento calcula e mostra kits e payback; só o "Salvar" é bloqueado.
    'readonly_form_routes' => [
        'consultor.grupo.b1.create',
        'consultor.grupo.b2.create',
        'consultor.grupo.b3.create',
        'consultor.grupo.a.create',
    ],
];
