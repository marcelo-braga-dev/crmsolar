import React, { useState } from 'react';
import {
    Box, Button, Card, CardContent, Chip, Grid, IconButton, InputAdornment,
    MenuItem, Table, TableBody, TableCell, TableHead, TableRow, TextField,
    Tooltip, Typography, alpha,
} from '@mui/material';
import AddRoundedIcon from '@mui/icons-material/AddRounded';
import SearchRoundedIcon from '@mui/icons-material/SearchRounded';
import EditRoundedIcon from '@mui/icons-material/EditRounded';
import VisibilityRoundedIcon from '@mui/icons-material/VisibilityRounded';
import DeleteRoundedIcon from '@mui/icons-material/DeleteRounded';
import ArticleRoundedIcon from '@mui/icons-material/ArticleRounded';
import SendRoundedIcon from '@mui/icons-material/SendRounded';
import CheckCircleRoundedIcon from '@mui/icons-material/CheckCircleRounded';
import AttachMoneyRoundedIcon from '@mui/icons-material/AttachMoneyRounded';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { TablePagination } from '@/Components/UI/TablePagination';
import { PageProps, PaginatedData } from '@/types';
import { formatarMoeda, type Numerico } from '@/utils/formatar';

type Status = 'rascunho' | 'enviada' | 'aceita' | 'recusada' | 'expirada';

interface Proposta {
    id: number; titulo: string; valor: number; validade: string; status: Status; created_at: string;
    cliente?: { id: number; tipo_pessoa: string; nome?: string; razao_social?: string };
}
interface Stats { total: number; enviadas: number; aceitas: number; valor_aceito: number }
interface Props extends PageProps { propostas: PaginatedData<Proposta>; filters: { search?: string; status?: string }; stats: Stats }

const STATUS_MAP: Record<Status, { label: string; color: 'default' | 'info' | 'success' | 'error' | 'warning' }> = {
    rascunho: { label: 'Rascunho',  color: 'default'  },
    enviada:  { label: 'Enviada',   color: 'info'     },
    aceita:   { label: 'Aceita',    color: 'success'  },
    recusada: { label: 'Recusada',  color: 'error'    },
    expirada: { label: 'Expirada',  color: 'warning'  },
};

export default function PropostasServicosIndex({ propostas, filters, stats }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');

    const fmtMoney = (v: Numerico) => formatarMoeda(v);
    const nomeCliente = (p: Proposta) => p.cliente
        ? (p.cliente.tipo_pessoa === 'pj' ? p.cliente.razao_social : p.cliente.nome) ?? '—'
        : '—';

    function applyFilters(overrides: object = {}) {
        router.get(route('consultor.proposta-servicos.index'), { search, status, ...overrides }, { preserveState: true, replace: true });
    }

    function confirmDelete(id: number) {
        if (!confirm('Excluir esta proposta de serviço? Esta ação não pode ser desfeita.')) return;
        router.delete(route('consultor.proposta-servicos.destroy', id));
    }

    const semPropostas = propostas.data.length === 0 && !filters.search && !filters.status;

    return (
        <AppLayout>
            <Head title="Propostas de Serviços" />
            <PageHeader
                title="Propostas de Serviços"
                subtitle="Modelos de proposta para serviços avulsos"
                breadcrumbs={[{ label: 'Propostas de Serviços' }]}
                action={
                    <Button component={Link} href={route('consultor.proposta-servicos.create')}
                        variant="contained" startIcon={<AddRoundedIcon />}>
                        Nova Proposta
                    </Button>
                }
            />

            {/* KPIs */}
            <Grid container spacing={3} sx={{ mb: 3 }}>
                {[
                    { label: 'Total', value: stats.total, icon: <ArticleRoundedIcon fontSize="small" />, color: '#6366f1' },
                    { label: 'Enviadas', value: stats.enviadas, icon: <SendRoundedIcon fontSize="small" />, color: '#0891B2' },
                    { label: 'Aceitas', value: stats.aceitas, icon: <CheckCircleRoundedIcon fontSize="small" />, color: '#10B981' },
                    { label: 'Valor Aceito', value: fmtMoney(stats.valor_aceito), icon: <AttachMoneyRoundedIcon fontSize="small" />, color: '#059669' },
                ].map((s) => (
                    <Grid key={s.label} size={{ xs: 6, sm: 3 }}>
                        <Card variant="outlined" sx={{ borderRadius: 2 }}>
                            <CardContent sx={{ p: '16px !important' }}>
                                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                                    <Box sx={{ width: 40, height: 40, borderRadius: 2, bgcolor: alpha(s.color, 0.12), display: 'flex', alignItems: 'center', justifyContent: 'center', color: s.color }}>
                                        {s.icon}
                                    </Box>
                                    <Box>
                                        <Typography variant="h6" fontWeight={700} sx={{ lineHeight: 1.2 }}>{s.value}</Typography>
                                        <Typography variant="caption" color="text.secondary">{s.label}</Typography>
                                    </Box>
                                </Box>
                            </CardContent>
                        </Card>
                    </Grid>
                ))}
            </Grid>

            {semPropostas ? (
                <Card variant="outlined" sx={{ borderRadius: 3, textAlign: 'center', py: 8 }}>
                    <ArticleRoundedIcon sx={{ fontSize: 64, color: 'text.disabled', mb: 2 }} />
                    <Typography variant="h6" fontWeight={600} gutterBottom>Nenhuma proposta de serviço ainda</Typography>
                    <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
                        Crie propostas personalizadas para apresentar serviços aos seus clientes.
                    </Typography>
                    <Button component={Link} href={route('consultor.proposta-servicos.create')}
                        variant="contained" size="large" startIcon={<AddRoundedIcon />} sx={{ borderRadius: 2.5, px: 4 }}>
                        Criar Primeira Proposta
                    </Button>
                </Card>
            ) : (
                <>
                    <Card variant="outlined" sx={{ mb: 3, p: 2, borderRadius: 2 }}>
                        <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap', alignItems: 'center' }}>
                            <TextField size="small" placeholder="Buscar por título ou cliente..."
                                value={search} onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                                InputProps={{ startAdornment: <InputAdornment position="start"><SearchRoundedIcon fontSize="small" /></InputAdornment> }}
                                sx={{ minWidth: 260 }} />
                            <TextField select size="small" label="Status" value={status}
                                onChange={(e) => { setStatus(e.target.value); applyFilters({ status: e.target.value }); }}
                                sx={{ minWidth: 160 }}>
                                <MenuItem value="">Todos</MenuItem>
                                {Object.entries(STATUS_MAP).map(([k, v]) => <MenuItem key={k} value={k}>{v.label}</MenuItem>)}
                            </TextField>
                            <Button variant="contained" size="small" onClick={() => applyFilters()}>Buscar</Button>
                            {(search || status) && (
                                <Button size="small" color="inherit" onClick={() => { setSearch(''); setStatus(''); applyFilters({ search: '', status: '' }); }}>
                                    Limpar
                                </Button>
                            )}
                        </Box>
                    </Card>

                    <Card variant="outlined" sx={{ borderRadius: 2 }}>
                        <Table>
                            <TableHead>
                                <TableRow>
                                    <TableCell>#</TableCell>
                                    <TableCell>Título</TableCell>
                                    <TableCell>Cliente</TableCell>
                                    <TableCell>Valor</TableCell>
                                    <TableCell>Validade</TableCell>
                                    <TableCell>Status</TableCell>
                                    <TableCell>Criada em</TableCell>
                                    <TableCell align="right" />
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {propostas.data.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={8} align="center" sx={{ py: 5 }}>
                                            <Typography color="text.secondary">Nenhuma proposta encontrada.</Typography>
                                        </TableCell>
                                    </TableRow>
                                )}
                                {propostas.data.map((p) => {
                                    const st = STATUS_MAP[p.status];
                                    const vencida = p.status === 'enviada' && new Date(p.validade) < new Date();
                                    return (
                                        <TableRow key={p.id} hover sx={{ cursor: 'pointer' }}
                                            onClick={() => router.visit(route('consultor.proposta-servicos.show', p.id))}>
                                            <TableCell>
                                                <Typography variant="body2" fontWeight={700} color="primary.main">#{p.id}</Typography>
                                            </TableCell>
                                            <TableCell>
                                                <Typography variant="body2" fontWeight={600}>{p.titulo}</Typography>
                                            </TableCell>
                                            <TableCell>
                                                <Typography variant="body2">{nomeCliente(p)}</Typography>
                                            </TableCell>
                                            <TableCell>
                                                <Typography variant="body2" fontWeight={600} color="success.main">{fmtMoney(p.valor)}</Typography>
                                            </TableCell>
                                            <TableCell>
                                                <Typography variant="body2" color={vencida ? 'error.main' : 'text.primary'}>
                                                    {new Date(p.validade).toLocaleDateString('pt-BR')}
                                                    {vencida && ' ⚠'}
                                                </Typography>
                                            </TableCell>
                                            <TableCell onClick={(e) => e.stopPropagation()}>
                                                <Chip label={st.label} color={st.color} size="small" />
                                            </TableCell>
                                            <TableCell>
                                                <Typography variant="body2">{new Date(p.created_at).toLocaleDateString('pt-BR')}</Typography>
                                            </TableCell>
                                            <TableCell align="right" onClick={(e) => e.stopPropagation()}>
                                                <Box sx={{ display: 'flex', gap: 0.5, justifyContent: 'flex-end' }}>
                                                    <Tooltip title="Ver">
                                                        <IconButton size="small" component={Link} href={route('consultor.proposta-servicos.show', p.id)}>
                                                            <VisibilityRoundedIcon fontSize="small" />
                                                        </IconButton>
                                                    </Tooltip>
                                                    <Tooltip title="Editar">
                                                        <IconButton size="small" component={Link} href={route('consultor.proposta-servicos.edit', p.id)}>
                                                            <EditRoundedIcon fontSize="small" />
                                                        </IconButton>
                                                    </Tooltip>
                                                    <Tooltip title="Excluir">
                                                        <IconButton size="small" color="error" onClick={() => confirmDelete(p.id)}>
                                                            <DeleteRoundedIcon fontSize="small" />
                                                        </IconButton>
                                                    </Tooltip>
                                                </Box>
                                            </TableCell>
                                        </TableRow>
                                    );
                                })}
                            </TableBody>
                        </Table>
                        <TablePagination {...propostas} label="propostas" />
                    </Card>
                </>
            )}
        </AppLayout>
    );
}
