import React, { useState } from 'react';
import {
    Box,
    Button,
    Card,
    CardContent,
    Grid,
    IconButton,
    InputAdornment,
    MenuItem,
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableRow,
    TextField,
    Tooltip,
    Typography,
    alpha,
} from '@mui/material';
import SearchRoundedIcon from '@mui/icons-material/SearchRounded';
import VisibilityRoundedIcon from '@mui/icons-material/VisibilityRounded';
import ReceiptLongRoundedIcon from '@mui/icons-material/ReceiptLongRounded';
import CheckCircleRoundedIcon from '@mui/icons-material/CheckCircleRounded';
import PendingRoundedIcon from '@mui/icons-material/PendingRounded';
import BuildRoundedIcon from '@mui/icons-material/BuildRounded';
import AttachMoneyRoundedIcon from '@mui/icons-material/AttachMoneyRounded';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { OrcamentoStatusChip } from '@/Components/UI/StatusChip';
import { TablePagination } from '@/Components/UI/TablePagination';
import { PageProps, PaginatedData, OrcamentoStatus } from '@/types';

interface Orcamento {
    id: number;
    status: OrcamentoStatus;
    preco_total: number;
    geracao_estimada: number;
    created_at: string;
    cliente?: { id: number; nome?: string; razao_social?: string; tipo_pessoa: string };
    consultor?: { id: number; name: string };
    cidade?: { id: number; cidade: string; estado: string };
}

interface Stats {
    total: number;
    novos: number;
    aprovados: number;
    instalando: number;
    valor_total: number;
}

interface Props extends PageProps {
    orcamentos: PaginatedData<Orcamento>;
    filters: { search?: string; status?: string; consultor_id?: string };
    consultores: { id: number; name: string }[];
    stats: Stats;
}

export default function OrcamentosIndex({ orcamentos, filters, consultores, stats }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [consultorId, setConsultorId] = useState(filters.consultor_id ?? '');

    function applyFilters(overrides: object = {}) {
        router.get(route('admin.orcamentos.index'), {
            search, status, consultor_id: consultorId, ...overrides,
        }, { preserveState: true, replace: true });
    }

    const fmtMoney = (v: number) => v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

    const nomeCliente = (o: Orcamento) => {
        if (!o.cliente) return '—';
        return o.cliente.tipo_pessoa === 'pj' ? o.cliente.razao_social : o.cliente.nome;
    };

    const statItems = [
        { label: 'Total de Orçamentos', value: stats.total, icon: <ReceiptLongRoundedIcon fontSize="small" />, color: '#6366f1' },
        { label: 'Novos / Para Aprovação', value: stats.novos, icon: <PendingRoundedIcon fontSize="small" />, color: '#f59e0b' },
        { label: 'Aprovados', value: stats.aprovados, icon: <CheckCircleRoundedIcon fontSize="small" />, color: '#22c55e' },
        { label: 'Em Instalação', value: stats.instalando, icon: <BuildRoundedIcon fontSize="small" />, color: '#3b82f6' },
        { label: 'Faturamento Confirmado', value: fmtMoney(stats.valor_total), icon: <AttachMoneyRoundedIcon fontSize="small" />, color: '#10b981' },
    ];

    return (
        <AppLayout>
            <Head title="Orçamentos" />

            <PageHeader title="Orçamentos" breadcrumbs={[{ label: 'Orçamentos' }]} />

            {/* Stats */}
            <Grid container spacing={2} sx={{ mb: 3 }}>
                {statItems.map((s) => (
                    <Grid key={s.label} size={{ xs: 6, sm: 4, md: 'auto' }} sx={{ flexGrow: 1 }}>
                        <Card sx={{ height: '100%' }}>
                            <CardContent sx={{ p: '16px !important' }}>
                                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                                    <Box sx={{
                                        width: 40, height: 40, borderRadius: 2,
                                        bgcolor: alpha(s.color, 0.12),
                                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                                        color: s.color, flexShrink: 0,
                                    }}>
                                        {s.icon}
                                    </Box>
                                    <Box>
                                        <Typography variant="h6" fontWeight={700} sx={{ lineHeight: 1.2 }}>
                                            {s.value}
                                        </Typography>
                                        <Typography variant="caption" color="text.secondary">
                                            {s.label}
                                        </Typography>
                                    </Box>
                                </Box>
                            </CardContent>
                        </Card>
                    </Grid>
                ))}
            </Grid>

            {/* Filtros */}
            <Card sx={{ mb: 3, p: 2 }}>
                <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap', alignItems: 'center' }}>
                    <TextField
                        size="small" placeholder="Buscar ID, cliente..." value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                        InputProps={{ startAdornment: <InputAdornment position="start"><SearchRoundedIcon fontSize="small" /></InputAdornment> }}
                        sx={{ minWidth: 240 }}
                    />
                    <TextField
                        select size="small" label="Status" value={status}
                        onChange={(e) => { setStatus(e.target.value); applyFilters({ status: e.target.value }); }}
                        sx={{ minWidth: 180 }}
                    >
                        <MenuItem value="">Todos</MenuItem>
                        <MenuItem value="novo">Novo</MenuItem>
                        <MenuItem value="aprovando">Para Aprovação</MenuItem>
                        <MenuItem value="aprovado">Aprovado</MenuItem>
                        <MenuItem value="aprovacao_reprovada">Reprovado</MenuItem>
                        <MenuItem value="instalando">Em Instalação</MenuItem>
                        <MenuItem value="finalizado">Finalizado</MenuItem>
                    </TextField>
                    <TextField
                        select size="small" label="Vendedor" value={consultorId}
                        onChange={(e) => { setConsultorId(e.target.value); applyFilters({ consultor_id: e.target.value }); }}
                        sx={{ minWidth: 180 }}
                    >
                        <MenuItem value="">Todos</MenuItem>
                        {consultores.map((c) => <MenuItem key={c.id} value={String(c.id)}>{c.name}</MenuItem>)}
                    </TextField>
                    <Button variant="contained" size="small" onClick={() => applyFilters()}>Buscar</Button>
                    {(search || status || consultorId) && (
                        <Button size="small" onClick={() => { setSearch(''); setStatus(''); setConsultorId(''); applyFilters({ search: '', status: '', consultor_id: '' }); }}>
                            Limpar
                        </Button>
                    )}
                </Box>
            </Card>

            {/* Tabela */}
            <Card>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell>#</TableCell>
                            <TableCell>Cliente</TableCell>
                            <TableCell>Cidade</TableCell>
                            <TableCell>Vendedor</TableCell>
                            <TableCell>Geração Est.</TableCell>
                            <TableCell>Valor</TableCell>
                            <TableCell>Status</TableCell>
                            <TableCell>Data</TableCell>
                            <TableCell align="right">Ações</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {orcamentos.data.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={9} align="center" sx={{ py: 6 }}>
                                    <ReceiptLongRoundedIcon sx={{ fontSize: 48, color: 'text.disabled', mb: 1 }} />
                                    <Typography color="text.secondary">Nenhum orçamento encontrado</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                        {orcamentos.data.map((o) => (
                            <TableRow key={o.id} hover>
                                <TableCell>
                                    <Typography variant="body2" fontWeight={700} color="primary.main">
                                        #{o.id}
                                    </Typography>
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2" fontWeight={600}>
                                        {nomeCliente(o) ?? '—'}
                                    </Typography>
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2">
                                        {o.cidade ? `${o.cidade.cidade} - ${o.cidade.estado}` : '—'}
                                    </Typography>
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2">{o.consultor?.name ?? '—'}</Typography>
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2">
                                        {o.geracao_estimada ? `${o.geracao_estimada} kWh/mês` : '—'}
                                    </Typography>
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2" fontWeight={600}>
                                        {fmtMoney(o.preco_total)}
                                    </Typography>
                                </TableCell>
                                <TableCell>
                                    <OrcamentoStatusChip status={o.status} />
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2">
                                        {new Date(o.created_at).toLocaleDateString('pt-BR')}
                                    </Typography>
                                </TableCell>
                                <TableCell align="right">
                                    <Tooltip title="Ver detalhes">
                                        <IconButton size="small" component={Link} href={route('admin.orcamentos.show', o.id)}>
                                            <VisibilityRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
                <TablePagination {...orcamentos} label="orçamentos" />
            </Card>
        </AppLayout>
    );
}
