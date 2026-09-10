import React from 'react';
import {
    Box, Button, Card, CardContent, CardHeader, Chip,
    Divider, Grid, Table, TableBody, TableCell, TableHead, TableRow, Typography,
} from '@mui/material';
import ReceiptLongRoundedIcon from '@mui/icons-material/ReceiptLongRounded';
import PeopleRoundedIcon from '@mui/icons-material/PeopleRounded';
import TrendingUpRoundedIcon from '@mui/icons-material/TrendingUpRounded';
import AccountBalanceWalletRoundedIcon from '@mui/icons-material/AccountBalanceWalletRounded';
import AddRoundedIcon from '@mui/icons-material/AddRounded';
import PendingRoundedIcon from '@mui/icons-material/PendingRounded';
import {
    AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip as ReTooltip, ResponsiveContainer,
} from 'recharts';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { KpiCard } from '@/Components/UI/KpiCard';
import { PageHeader } from '@/Components/UI/PageHeader';
import { OrcamentoStatusChip } from '@/Components/UI/StatusChip';
import { OrcamentoStatus, PageProps } from '@/types';

interface Stats {
    clientes: number; leads_abertos: number;
    orcamentos_mes: number; valor_aprovado_mes: number;
    em_aprovacao: number; aprovados: number;
}
interface EvolucaoItem { mes: string; qtd: number; valor: number; }
interface OrcamentoRecente {
    id: number; status: OrcamentoStatus; preco_total: number; created_at: string;
    cliente?: { nome?: string; razao_social?: string; tipo_pessoa: string };
}
interface Props extends PageProps {
    stats: Stats;
    evolucao: EvolucaoItem[];
    recentes: OrcamentoRecente[];
}

const fmtMoney = (v: number) =>
    new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', maximumFractionDigits: 0 }).format(v);

const fmtDate = (s: string) =>
    new Date(s).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });

export default function ConsultorDashboard({ auth, stats, evolucao, recentes }: Props) {
    const firstName = auth.user.name.split(' ')[0];

    const nomeCliente = (o: OrcamentoRecente) =>
        o.cliente?.tipo_pessoa === 'pj' ? (o.cliente.razao_social ?? '—') : (o.cliente?.nome ?? '—');

    return (
        <AppLayout title="Meu Painel">
            <Head title="Dashboard" />

            <PageHeader
                title={`Olá, ${firstName}!`}
                subtitle="Acompanhe sua performance."
                action={
                    <Button
                        variant="contained"
                        startIcon={<AddRoundedIcon />}
                        component={Link}
                        href={route('consultor.dimensionamento.convencional')}
                        sx={{ fontWeight: 700 }}
                    >
                        Novo Orçamento
                    </Button>
                }
            />

            {/* KPIs */}
            <Grid container spacing={2.5} sx={{ mb: 3 }}>
                <Grid size={{ xs: 12, sm: 6, lg: 3 }}>
                    <KpiCard title="Orçamentos no Mês" value={String(stats.orcamentos_mes)} icon={<ReceiptLongRoundedIcon />} color="#2563EB" />
                </Grid>
                <Grid size={{ xs: 12, sm: 6, lg: 3 }}>
                    <KpiCard title="Valor Aprovado" value={fmtMoney(stats.valor_aprovado_mes)} icon={<AccountBalanceWalletRoundedIcon />} color="#10B981" />
                </Grid>
                <Grid size={{ xs: 12, sm: 6, lg: 3 }}>
                    <KpiCard title="Meus Clientes" value={String(stats.clientes)} icon={<PeopleRoundedIcon />} color="#8B5CF6" />
                </Grid>
                <Grid size={{ xs: 12, sm: 6, lg: 3 }}>
                    <KpiCard title="Leads em Aberto" value={String(stats.leads_abertos)} icon={<TrendingUpRoundedIcon />} color="#F59E0B" />
                </Grid>
            </Grid>

            <Grid container spacing={2.5} sx={{ mb: 3 }}>
                {/* Status mini-pipeline */}
                <Grid size={{ xs: 12, md: 4 }}>
                    <Card variant="outlined" sx={{ borderRadius: 2, height: '100%' }}>
                        <CardHeader
                            title="Meu Pipeline"
                            titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }}
                            sx={{ pb: 0 }}
                        />
                        <CardContent sx={{ pt: 2 }}>
                            {[
                                { label: 'Em Aprovação', value: stats.em_aprovacao, color: '#F59E0B', icon: <PendingRoundedIcon fontSize="small" /> },
                                { label: 'Aprovados', value: stats.aprovados, color: '#10B981', icon: <TrendingUpRoundedIcon fontSize="small" /> },
                            ].map((item) => (
                                <Box key={item.label} sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', py: 1.5, borderBottom: '1px solid', borderColor: 'divider', '&:last-child': { borderBottom: 'none' } }}>
                                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, color: item.color }}>
                                        {item.icon}
                                        <Typography variant="body2" fontWeight={500}>{item.label}</Typography>
                                    </Box>
                                    <Chip label={item.value} size="small" sx={{ bgcolor: item.color + '20', color: item.color, fontWeight: 700 }} />
                                </Box>
                            ))}
                            <Box sx={{ mt: 2 }}>
                                <Button size="small" fullWidth variant="outlined" component={Link} href={route('consultor.orcamentos.index')}>
                                    Ver todos os orçamentos
                                </Button>
                            </Box>
                        </CardContent>
                    </Card>
                </Grid>

                {/* Evolução */}
                <Grid size={{ xs: 12, md: 8 }}>
                    <Card variant="outlined" sx={{ borderRadius: 2 }}>
                        <CardHeader
                            title="Evolução de Vendas"
                            subheader="Valor aprovado nos últimos 6 meses"
                            titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }}
                            subheaderTypographyProps={{ variant: 'caption' }}
                            sx={{ pb: 0 }}
                        />
                        <CardContent>
                            <ResponsiveContainer width="100%" height={220}>
                                <AreaChart data={evolucao} margin={{ top: 10, right: 10, left: 0, bottom: 0 }}>
                                    <defs>
                                        <linearGradient id="grad" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#2563EB" stopOpacity={0.2} />
                                            <stop offset="95%" stopColor="#2563EB" stopOpacity={0} />
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#F1F5F9" />
                                    <XAxis dataKey="mes" tick={{ fontSize: 12, fill: '#94A3B8' }} axisLine={false} tickLine={false} />
                                    <YAxis tick={{ fontSize: 12, fill: '#94A3B8' }} axisLine={false} tickLine={false} tickFormatter={(v) => `R$${(v / 1000).toFixed(0)}k`} />
                                    <ReTooltip
                                        contentStyle={{ borderRadius: 8, border: '1px solid #E2E8F0', fontSize: 12 }}
                                        formatter={(v: number) => [fmtMoney(v), 'Valor aprovado']}
                                    />
                                    <Area type="monotone" dataKey="valor" stroke="#2563EB" strokeWidth={2.5} fill="url(#grad)" dot={{ fill: '#2563EB', r: 4 }} activeDot={{ r: 6 }} />
                                </AreaChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </Grid>
            </Grid>

            {/* Orçamentos recentes */}
            <Card variant="outlined" sx={{ borderRadius: 2 }}>
                <CardHeader
                    title="Orçamentos Recentes"
                    titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }}
                    action={
                        <Button size="small" component={Link} href={route('consultor.orcamentos.index')}>
                            Ver todos
                        </Button>
                    }
                    sx={{ pb: 0 }}
                />
                <Divider />
                {recentes.length === 0 ? (
                    <Box sx={{ py: 4, textAlign: 'center', color: 'text.secondary' }}>
                        <Typography variant="body2">Nenhum orçamento criado ainda.</Typography>
                        <Button
                            sx={{ mt: 1 }}
                            variant="contained"
                            size="small"
                            component={Link}
                            href={route('consultor.dimensionamento.convencional')}
                        >
                            Criar primeiro orçamento
                        </Button>
                    </Box>
                ) : (
                    <Table size="small">
                        <TableHead sx={{ bgcolor: '#F8FAFC' }}>
                            <TableRow>
                                <TableCell sx={{ fontWeight: 600 }}>#</TableCell>
                                <TableCell sx={{ fontWeight: 600 }}>Cliente</TableCell>
                                <TableCell sx={{ fontWeight: 600 }} align="right">Valor</TableCell>
                                <TableCell sx={{ fontWeight: 600 }}>Status</TableCell>
                                <TableCell sx={{ fontWeight: 600 }}>Data</TableCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {recentes.map((o) => (
                                <TableRow
                                    key={o.id}
                                    hover
                                    sx={{ cursor: 'pointer' }}
                                    onClick={() => router.visit(route('consultor.orcamentos.show', o.id))}
                                >
                                    <TableCell>
                                        <Typography variant="caption" color="primary" fontWeight={600}>#{o.id}</Typography>
                                    </TableCell>
                                    <TableCell>
                                        <Typography variant="body2" fontWeight={500}>{nomeCliente(o)}</Typography>
                                    </TableCell>
                                    <TableCell align="right">
                                        <Typography variant="body2" fontWeight={600}>{fmtMoney(o.preco_total)}</Typography>
                                    </TableCell>
                                    <TableCell>
                                        <OrcamentoStatusChip status={o.status} />
                                    </TableCell>
                                    <TableCell>
                                        <Typography variant="caption" color="text.secondary">{fmtDate(o.created_at)}</Typography>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
            </Card>
        </AppLayout>
    );
}
