import React from 'react';
import DashboardRoundedIcon from '@mui/icons-material/DashboardRounded';
import ReceiptLongRoundedIcon from '@mui/icons-material/ReceiptLongRounded';
import PeopleRoundedIcon from '@mui/icons-material/PeopleRounded';
import TrendingUpRoundedIcon from '@mui/icons-material/TrendingUpRounded';
import Inventory2RoundedIcon from '@mui/icons-material/Inventory2Rounded';
import ManageAccountsRoundedIcon from '@mui/icons-material/ManageAccountsRounded';
import AccountBalanceRoundedIcon from '@mui/icons-material/AccountBalanceRounded';
import PriceChangeRoundedIcon from '@mui/icons-material/PriceChangeRounded';
import SettingsRoundedIcon from '@mui/icons-material/SettingsRounded';
import SyncRoundedIcon from '@mui/icons-material/SyncRounded';
import MapRoundedIcon from '@mui/icons-material/MapRounded';
import ArticleRoundedIcon from '@mui/icons-material/ArticleRounded';
import AccountBalanceWalletRoundedIcon from '@mui/icons-material/AccountBalanceWalletRounded';
import SolarPowerRoundedIcon from '@mui/icons-material/SolarPowerRounded';

export interface NavChild {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    icon: React.ReactNode;
    href?: string;
    children?: NavChild[];
}

export interface NavSection {
    subheader?: string;
    items: NavItem[];
}

export const adminNav: NavSection[] = [
    {
        items: [
            {
                title: 'Dashboard',
                icon: <DashboardRoundedIcon />,
                href: '/admin/dashboard',
            },
            {
                title: 'Orçamentos',
                icon: <ReceiptLongRoundedIcon />,
                children: [
                    { title: 'Funil (Kanban)', href: '/admin/funil' },
                    { title: 'Lista', href: '/admin/orcamentos' },
                ],
            },
            {
                title: 'Clientes',
                icon: <PeopleRoundedIcon />,
                href: '/admin/clientes',
            },
            {
                title: 'Leads',
                icon: <TrendingUpRoundedIcon />,
                href: '/admin/leads',
            },
        ],
    },
    {
        subheader: 'Catálogo',
        items: [
            {
                title: 'Produtos',
                icon: <Inventory2RoundedIcon />,
                children: [
                    { title: 'Kits Solares', href: '/admin/produtos/kits' },
                    { title: 'Catálogo', href: '/admin/produtos/catalogo' },
                ],
            },
            {
                title: 'Precificação',
                icon: <PriceChangeRoundedIcon />,
                href: '/admin/precificacao',
            },
        ],
    },
    {
        subheader: 'Gestão',
        items: [
            {
                title: 'Usuários',
                icon: <ManageAccountsRoundedIcon />,
                children: [
                    { title: 'Consultores', href: '/admin/usuarios/consultores' },
                    { title: 'Admins', href: '/admin/usuarios/admins' },
                ],
            },
            {
                title: 'Financeiro',
                icon: <AccountBalanceRoundedIcon />,
                children: [
                    { title: 'Comissões', href: '/admin/financeiro/comissoes' },
                    { title: 'Faturamento', href: '/admin/financeiro/faturamento' },
                ],
            },
            {
                title: 'Fornecedores',
                icon: <SolarPowerRoundedIcon />,
                href: '/admin/fornecedores',
            },
            {
                title: 'Integrações',
                icon: <SyncRoundedIcon />,
                children: [
                    { title: 'Distribuidora', href: '/admin/integracoes/distribuidora' },
                    { title: 'Histórico', href: '/admin/integracoes/historico' },
                ],
            },
        ],
    },
    {
        subheader: 'Sistema',
        items: [
            {
                title: 'Configurações',
                icon: <SettingsRoundedIcon />,
                children: [
                    { title: 'Auditoria', href: '/admin/configuracoes/auditoria' },
                    { title: 'Bancos', href: '/admin/configuracoes/bancos' },
                    { title: 'Concessionárias', href: '/admin/configuracoes/concessionarias' },
                    { title: 'Dimensionamento', href: '/admin/configuracoes/dimensionamento' },
                    { title: 'Funil de vendas', href: '/admin/configuracoes/funil' },
                    { title: 'Identidade visual', href: '/admin/configuracoes/identidade-visual' },
                    { title: 'Sistema', href: '/admin/configuracoes/sistema' },
                ],
            },
        ],
    },
];

export const consultorNav: NavSection[] = [
    {
        items: [
            {
                title: 'Dashboard',
                icon: <DashboardRoundedIcon />,
                href: '/consultor/dashboard',
            },
            {
                title: 'Orçamentos',
                icon: <ReceiptLongRoundedIcon />,
                children: [
                    { title: 'Funil (Kanban)', href: '/consultor/funil' },
                    { title: 'Lista', href: '/consultor/orcamentos' },
                ],
            },
            {
                title: 'Clientes',
                icon: <PeopleRoundedIcon />,
                href: '/consultor/clientes',
            },
            {
                title: 'Leads',
                icon: <TrendingUpRoundedIcon />,
                href: '/consultor/leads',
            },
            {
                title: 'Propostas de Serviços',
                icon: <ArticleRoundedIcon />,
                href: '/consultor/proposta-servicos',
            },
        ],
    },
    {
        subheader: 'Pós-Venda',
        items: [
            {
                title: 'Visitas Técnicas',
                icon: <MapRoundedIcon />,
                href: '/consultor/visitas',
            },
            {
                title: 'Contratos',
                icon: <ArticleRoundedIcon />,
                href: '/consultor/contratos',
            },
            {
                title: 'Financeiro',
                icon: <AccountBalanceWalletRoundedIcon />,
                href: '/consultor/financeiro',
            },
        ],
    },
];
