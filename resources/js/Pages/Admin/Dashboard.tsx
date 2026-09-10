import React from 'react';
import {
    Avatar, Box, Card, CardContent, CardHeader, Divider,
    Grid, LinearProgress, Table, TableBody, TableCell, TableHead, TableRow, Typography, alpha,
} from '@mui/material';
import ReceiptLongRoundedIcon from '@mui/icons-material/ReceiptLongRounded';
import PeopleRoundedIcon from '@mui/icons-material/PeopleRounded';
import TrendingUpRoundedIcon from '@mui/icons-material/TrendingUpRounded';
import AttachMoneyRoundedIcon from '@mui/icons-material/AttachMoneyRounded';
import {
    AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip as ReTooltip, ResponsiveContainer,
} from 'recharts';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { KpiCard } from '@/Components/UI/KpiCard';
import { PageHeader } from '@/Components/UI/PageHeader';
import { OrcamentoStatusChip } from '@/Components/UI/StatusChip';
import { OrcamentoStatus, PageProps } from '@/types';

interface Stats {
    orcamentos_mes: number; orcamentos_mes_ant: number;
    clientes_total: number; leads_abertos: number;
    valor_aprovado_mes: number; valor_aprovado_total: number;
    consultores_ativos: number; em_instalacao: number;
}
interface EvolucaoItem { mes: string; qtd: number; valor: number; }
interface Consultor { id: number; name: string; email: string; valor_mes?: number; qtd_mes?: number; }
interface OrcamentoRecente {
    id: number; status: OrcamentoStatus; preco_total: number; created_at: string;
    consultor?: { id: number; name: string };
    cliente?: { nome?: string; razao_social?: string; tipo_pessoa: string };
}
interface Props extends PageProps {
    stats: Stats;
    porStatus: Record<string, number>;
    evolucao: EvolucaoItem[];
    topConsultores: Consultor[];
    recentes: OrcamentoRecente[];
}

const fmtMoney = (v: number) =>
    new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', maximumFractionDigits: 0 }).format(v);

const AVATAR_COLORS = ['#2563EB', '#8B5CF6', '#10B981', '#F59E0B', '#EF4444'];

const STATUS_LABELS: Record<string, { label: string; color: string }> = {
    novo: { label: 'Novos', color: '#06B6D4' },
    aprovando: { label: 'Para Aprovação', color: '#F59E0B' },
    aprovado: { label: 'Aprovados', color: '#10B981' },
    aprovacao_reprovada: { label: 'Reprovados', color: '#EF4444' },
    instalando: { label: 'Em Instalação', color: '#8B5CF6' },
    finalizado: { label: 'Finalizados', color: '#64748B' },
};

export default function AdminDashboard({ auth, stats, evolucao, topConsultores, recentes, porStatus }: Props) {
    const firstName = auth.user.name.split(' ')[0];
    const totalOrcamentos = Object.values(porStatus).reduce((a, b) => a + b, 0);

    const nomeTrend = stats.orcamentos_mes_ant > 0
        ? Math.round(((stats.orcamentos_mes - stats.orcamentos_mes_ant) / stats.orcamentos_mes_ant) * 100)
        : undefined;

    const nomeCliente = (o: OrcamentoRecente) =>
        o.cliente?.tipo_pessoa === 'pj' ? (o.cliente.razao_social ?? '—') : (o.cliente?.nome ?? '—');

    return (
        <AppLayout title="Dashboard">
            <Head title="Dashboard" />

            <PageHeader
                title={`Olá, ${firstName}!`}
                subtitle="Aqui está o resumo da operação."
            />

            {/* KPIs */}
            <Grid container spacing={2.5} sx={{ mb: 3 }}>
                <Grid size={{ xs: 12, sm: 6, lg: 3 }}>
                    <KpiCard title="Orçamentos no Mês" value={String(stats.orcamentos_mes)} icon={<ReceiptLongRoundedIcon />} color="#2563EB" trend={nomeTrend} />
                </Grid>
                <Grid size={{ xs: 12, sm: 6, lg: 3 }}>
                    <KpiCard title="Valor Aprovado" value={fmtMoney(stats.valor_aprovado_mes)} icon={<AttachMoneyRoundedIcon />} color="#10B981" />
                </Grid>
                <Grid size={{ xs: 12, sm: 6, lg: 3 }}>
                    <KpiCard title="Total Clientes" value={String(stats.clientes_total)} icon={<PeopleRoundedIcon />} color="#8B5CF6" />
                </Grid>
                <Grid size={{ xs: 12, sm: 6, lg: 3 }}>
                    <KpiCard title="Em Instalação" value={String(stats.em_instalacao)} icon={<TrendingUpRoundedIcon />} color="#F59E0B" subtitle={`${stats.leads_abertos} leads em aberto`} />
                </Grid>
            </Grid>

            <Grid container spacing={2.5} sx={{ mb: 3 }}>
                {/* Evolução */}
                <Grid size={{ xs: 12, lg: 8 }}>
                    <Card variant="outlined" sx={{ borderRadius: 2 }}>
                        <CardHeader
                            title="Evolução de Vendas"
                            subheader="Valor aprovado nos últimos 6 meses"
                            titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }}
                            subheaderTypographyProps={{ variant: 'caption' }}
                            sx={{ pb: 0 }}
                        />
                        <CardContent>
                            <ResponsiveContainer width="100%" height={260}>
                                <AreaChart data={evolucao} margin={{ top: 10, right: 10, left: 0, bottom: 0 }}>
                                    <defs>
                                        <linearGradient id="gradAdmin" x1="0" y1="0" x2="0" y2="1">
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
                                    <Area type="monotone" dataKey="valor" stroke="#2563EB" strokeWidth={2.5} fill="url(#gradAdmin)" dot={{ fill: '#2563EB', r: 4 }} activeDot={{ r: 6 }} />
                                </AreaChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </Grid>

                {/* Status pipeline */}
                <Grid size={{ xs: 12, lg: 4 }}>
                    <Card variant="outlined" sx={{ borderRadius: 2, height: '100%' }}>
                        <CardHeader
                            title="Pipeline de Orçamentos"
                            subheader={`${totalOrcamentos} no total`}
                            titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }}
                            subheaderTypographyProps={{ variant: 'caption' }}
                            sx={{ pb: 0 }}
                        />
                        <CardContent sx={{ pt: 2 }}>
                            {Object.entries(STATUS_LABELS).map(([key, cfg]) => {
                                const count = porStatus[key] ?? 0;
                                const pct = totalOrcamentos > 0 ? (count / totalOrcamentos) * 100 : 0;
                                return (
                                    <Box key={key} sx={{ mb: 1.5, '&:last-child': { mb: 0 } }}>
                                        <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 0.4 }}>
                                            <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.8 }}>
                                                <Box sx={{ width: 8, height: 8, borderRadius: '50%', bgcolor: cfg.color }} />
                                                <Typography variant="caption" fontWeight={500}>{cfg.label}</Typography>
                                            </Box>
                                            <Typography variant="caption" fontWeight={700}>{count}</Typography>
                                        </Box>
                                        <LinearProgress
                                            variant="determinate"
                                            value={pct}
                                            sx={{ height: 5, borderRadius: 3, bgcolor: alpha(cfg.color, 0.12), '& .MuiLinearProgress-bar': { bgcolor: cfg.color, borderRadius: 3 } }}
                                        />
                                    </Box>
                                );
                            })}
                        </CardContent>
                    </Card>
                </Grid>
            </Grid>

            <Grid container spacing={2.5}>
                {/* Orçamentos recentes */}
                <Grid size={{ xs: 12, lg: 7 }}>
                    <Card variant="outlined" sx={{ borderRadius: 2 }}>
                        <CardHeader
                            title="Orçamentos Recentes"
                            titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }}
                            sx={{ pb: 0 }}
                        />
                        <Divider />
                        <Table size="small">
                            <TableHead sx={{ bgcolor: '#F8FAFC' }}>
                                <TableRow>
                                    <TableCell sx={{ fontWeight: 600 }}>#</TableCell>
                                    <TableCell sx={{ fontWeight: 600 }}>Cliente</TableCell>
                                    <TableCell sx={{ fontWeight: 600 }}>Consultor</TableCell>
                                    <TableCell sx={{ fontWeight: 600 }} align="right">Valor</TableCell>
                                    <TableCell sx={{ fontWeight: 600 }}>Status</TableCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {recentes.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={5} align="center" sx={{ py: 4, color: 'text.secondary' }}>Nenhum orçamento ainda.</TableCell>
                                    </TableRow>
                                ) : recentes.map((o) => (
                                    <TableRow
                                        key={o.id}
                                        hover
                                        sx={{ cursor: 'pointer' }}
                                        onClick={() => router.visit(route('admin.orcamentos.show', o.id))}
                                    >
                                        <TableCell>
                                            <Typography variant="caption" color="primary" fontWeight={600}>#{o.id}</Typography>
                                        </TableCell>
                                        <TableCell>
                                            <Typography variant="body2" fontWeight={500}>{nomeCliente(o)}</Typography>
                                        </TableCell>
                                        <TableCell>
                                            <Typography variant="body2" color="text.secondary">{o.consultor?.name ?? '—'}</Typography>
                                        </TableCell>
                                        <TableCell align="right">
                                            <Typography variant="body2" fontWeight={600}>{fmtMoney(o.preco_total)}</Typography>
                                        </TableCell>
                                        <TableCell>
                                            <OrcamentoStatusChip status={o.status} />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </Card>
                </Grid>

                {/* Top consultores */}
                <Grid size={{ xs: 12, lg: 5 }}>
                    <Card variant="outlined" sx={{ borderRadius: 2, height: '100%' }}>
                        <CardHeader
                            title="Top Consultores"
                            subheader="Valor aprovado no mês"
                            titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }}
                            subheaderTypographyProps={{ variant: 'caption' }}
                            sx={{ pb: 0 }}
                        />
                        <Divider />
                        <CardContent sx={{ pt: 1.5 }}>
                            {topConsultores.length === 0 ? (
                                <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                                    Nenhum consultor cadastrado ainda.
                                </Typography>
                            ) : topConsultores.map((c, i) => {
                                const initials = c.name.split(' ').map((n) => n[0]).join('').slice(0, 2).toUpperCase();
                                const valor = (c.valor_mes as number | null) ?? 0;
                                const qtd = (c.qtd_mes as number | null) ?? 0;
                                const maxValor = (topConsultores[0]?.valor_mes as number | null) ?? 1;
                                const pct = maxValor > 0 ? (valor / maxValor) * 100 : 0;

                                return (
                                    <Box key={c.id} sx={{ mb: i < topConsultores.length - 1 ? 2 : 0 }}>
                                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, mb: 0.5 }}>
                                            <Avatar sx={{ width: 32, height: 32, fontSize: '0.75rem', fontWeight: 700, bgcolor: AVATAR_COLORS[i % AVATAR_COLORS.length] }}>
                                                {initials}
                                            </Avatar>
                                            <Box sx={{ flex: 1, minWidth: 0 }}>
                                                <Box sx={{ display: 'flex', justifyContent: 'space-between' }}>
                                                    <Typography variant="body2" fontWeight={600} noWrap>{c.name}</Typography>
                                                    <Typography variant="caption" fontWeight={700} color="primary">{fmtMoney(valor)}</Typography>
                                                </Box>
                                                <Typography variant="caption" color="text.secondary">{qtd} orçamentos</Typography>
                                            </Box>
                                        </Box>
                                        <LinearProgress
                                            variant="determinate"
                                            value={pct}
                                            sx={{ height: 4, borderRadius: 2, bgcolor: alpha(AVATAR_COLORS[i % AVATAR_COLORS.length], 0.12), '& .MuiLinearProgress-bar': { bgcolor: AVATAR_COLORS[i % AVATAR_COLORS.length], borderRadius: 2 } }}
                                        />
                                    </Box>
                                );
                            })}
                        </CardContent>
                    </Card>
                </Grid>
            </Grid>
        </AppLayout>
    );
}
