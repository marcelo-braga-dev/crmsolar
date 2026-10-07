import React, { useState } from 'react';
import {
    Box, Button, Card, CardActionArea, CardContent, Grid, IconButton,
    InputAdornment, MenuItem, Table, TableBody, TableCell, TableHead,
    TableRow, TextField, Tooltip, Typography, alpha,
} from '@mui/material';
import AddRoundedIcon from '@mui/icons-material/AddRounded';
import SearchRoundedIcon from '@mui/icons-material/SearchRounded';
import VisibilityRoundedIcon from '@mui/icons-material/VisibilityRounded';
import ReceiptLongRoundedIcon from '@mui/icons-material/ReceiptLongRounded';
import CheckCircleRoundedIcon from '@mui/icons-material/CheckCircleRounded';
import PendingRoundedIcon from '@mui/icons-material/PendingRounded';
import AttachMoneyRoundedIcon from '@mui/icons-material/AttachMoneyRounded';
import ArrowForwardRoundedIcon from '@mui/icons-material/ArrowForwardRounded';
import ElectricBoltRoundedIcon from '@mui/icons-material/ElectricBoltRounded';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { OrcamentoStatusChip } from '@/Components/UI/StatusChip';
import { TablePagination } from '@/Components/UI/TablePagination';
import { PageProps, PaginatedData, OrcamentoStatus } from '@/types';
import { formatarMoeda, type Numerico } from '@/utils/formatar';

interface Orcamento {
    id: number; status: OrcamentoStatus; preco_total: number;
    geracao_estimada: number; created_at: string;
    cliente?: { id: number; nome?: string; razao_social?: string; tipo_pessoa: string };
    cidade?: { cidade: string; estado: string };
    grupo_tarifario?: string;
}
interface Stats { total: number; novos: number; aprovados: number; valor_total: number }
interface Props extends PageProps { orcamentos: PaginatedData<Orcamento>; filters: { search?: string; status?: string }; stats: Stats }

export default function ConsultorOrcamentosIndex({ orcamentos, filters, stats }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus]  = useState(filters.status ?? '');

    function applyFilters(overrides: object = {}) {
        router.get(route('consultor.orcamentos.index'), { search, status, ...overrides }, { preserveState: true, replace: true });
    }

    const fmtMoney = (v: Numerico) => formatarMoeda(v);
    const nomeCliente = (o: Orcamento) => o.cliente
        ? (o.cliente.tipo_pessoa === 'pj' ? o.cliente.razao_social : o.cliente.nome)
        : '—';

    const semOrcamentos = orcamentos.data.length === 0 && !filters.search && !filters.status;

    return (
        <AppLayout>
            <Head title="Meus Orçamentos" />
            <PageHeader title="Meus Orçamentos" breadcrumbs={[{ label: 'Orçamentos' }]} />

            {/* ── Card de CTA — sempre visível no topo ─────────────────────── */}
            <Card
                component={Link}
                href={route('consultor.orcamentos.selecionar_grupo')}
                variant="outlined"
                sx={{
                    mb: 3, borderRadius: 3, textDecoration: 'none',
                    border: '2px dashed', borderColor: 'primary.main',
                    bgcolor: alpha('#2563EB', 0.03),
                    transition: 'all 0.18s ease',
                    display: 'block',
                    '&:hover': {
                        bgcolor: alpha('#2563EB', 0.07),
                        borderColor: 'primary.dark',
                        transform: 'translateY(-1px)',
                        boxShadow: '0 4px 20px rgba(37,99,235,0.15)',
                    },
                }}
            >
                <CardContent sx={{ p: '20px !important' }}>
                    <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 2, flexWrap: 'wrap' }}>
                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
                            <Box sx={{
                                width: 52, height: 52, borderRadius: 3,
                                bgcolor: 'primary.main', display: 'flex',
                                alignItems: 'center', justifyContent: 'center', flexShrink: 0,
                            }}>
                                <ElectricBoltRoundedIcon sx={{ color: '#fff', fontSize: 28 }} />
                            </Box>
                            <Box>
                                <Typography variant="h6" fontWeight={700} color="primary.main">
                                    Gerar novo orçamento solar
                                </Typography>
                                <Typography variant="body2" color="text.secondary">
                                    Selecione o tipo de instalação do cliente e calcule a proposta completa com análise de retorno.
                                </Typography>
                            </Box>
                        </Box>
                        <Button
                            variant="contained"
                            endIcon={<ArrowForwardRoundedIcon />}
                            sx={{ borderRadius: 2.5, px: 3, fontWeight: 700, flexShrink: 0, pointerEvents: 'none' }}
                            tabIndex={-1}
                        >
                            Novo Orçamento
                        </Button>
                    </Box>
                </CardContent>
            </Card>

            {/* ── KPIs ────────────────────────────────────────────────────── */}
            <Grid container spacing={2} sx={{ mb: 3 }}>
                {[
                    { label: 'Total',         value: stats.total,                 icon: <ReceiptLongRoundedIcon fontSize="small" />, color: '#6366f1' },
                    { label: 'Em Andamento',  value: stats.novos,                 icon: <PendingRoundedIcon fontSize="small" />,    color: '#f59e0b' },
                    { label: 'Aprovados',     value: stats.aprovados,             icon: <CheckCircleRoundedIcon fontSize="small" />, color: '#22c55e' },
                    { label: 'Total Faturado',value: fmtMoney(stats.valor_total), icon: <AttachMoneyRoundedIcon fontSize="small" />, color: '#10b981' },
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

            {/* ── Estado vazio — sem nenhum orçamento ainda ───────────────── */}
            {semOrcamentos ? (
                <Card variant="outlined" sx={{ borderRadius: 3, textAlign: 'center', py: 8 }}>
                    <ReceiptLongRoundedIcon sx={{ fontSize: 64, color: 'text.disabled', mb: 2 }} />
                    <Typography variant="h6" fontWeight={600} gutterBottom>Nenhum orçamento ainda</Typography>
                    <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
                        Clique no botão acima para criar o primeiro orçamento solar.
                    </Typography>
                    <Button
                        component={Link}
                        href={route('consultor.orcamentos.selecionar_grupo')}
                        variant="contained"
                        size="large"
                        startIcon={<AddRoundedIcon />}
                        sx={{ borderRadius: 2.5, px: 4 }}
                    >
                        Criar Primeiro Orçamento
                    </Button>
                </Card>
            ) : (
                <>
                    {/* ── Filtros ─────────────────────────────────────────── */}
                    <Card variant="outlined" sx={{ mb: 3, p: 2, borderRadius: 2 }}>
                        <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap', alignItems: 'center' }}>
                            <TextField
                                size="small" placeholder="Buscar por ID ou cliente..."
                                value={search} onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                                InputProps={{ startAdornment: <InputAdornment position="start"><SearchRoundedIcon fontSize="small" /></InputAdornment> }}
                                sx={{ minWidth: 240 }}
                            />
                            <TextField select size="small" label="Status" value={status}
                                onChange={(e) => { setStatus(e.target.value); applyFilters({ status: e.target.value }); }}
                                sx={{ minWidth: 180 }}>
                                <MenuItem value="">Todos</MenuItem>
                                <MenuItem value="novo">Novo</MenuItem>
                                <MenuItem value="aprovando">Para Aprovação</MenuItem>
                                <MenuItem value="aprovado">Aprovado</MenuItem>
                                <MenuItem value="aprovacao_reprovada">Reprovado</MenuItem>
                                <MenuItem value="instalando">Em Instalação</MenuItem>
                                <MenuItem value="finalizado">Finalizado</MenuItem>
                            </TextField>
                            <Button variant="contained" size="small" onClick={() => applyFilters()}>Buscar</Button>
                            {(search || status) && (
                                <Button size="small" color="inherit" onClick={() => { setSearch(''); setStatus(''); applyFilters({ search: '', status: '' }); }}>
                                    Limpar filtros
                                </Button>
                            )}
                        </Box>
                    </Card>

                    {/* ── Tabela ──────────────────────────────────────────── */}
                    <Card variant="outlined" sx={{ borderRadius: 2 }}>
                        <Table>
                            <TableHead>
                                <TableRow>
                                    <TableCell>#</TableCell>
                                    <TableCell>Cliente</TableCell>
                                    <TableCell>Cidade</TableCell>
                                    <TableCell>Grupo</TableCell>
                                    <TableCell>Geração Est.</TableCell>
                                    <TableCell>Valor</TableCell>
                                    <TableCell>Status</TableCell>
                                    <TableCell>Data</TableCell>
                                    <TableCell align="right" />
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {orcamentos.data.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={9} align="center" sx={{ py: 5 }}>
                                            <Typography color="text.secondary">Nenhum orçamento encontrado para os filtros aplicados.</Typography>
                                        </TableCell>
                                    </TableRow>
                                )}
                                {orcamentos.data.map((o) => (
                                    <TableRow key={o.id} hover sx={{ cursor: 'pointer' }} onClick={() => router.visit(route('consultor.orcamentos.show', o.id))}>
                                        <TableCell>
                                            <Typography variant="body2" fontWeight={700} color="primary.main">#{o.id}</Typography>
                                        </TableCell>
                                        <TableCell>
                                            <Typography variant="body2" fontWeight={600}>{nomeCliente(o) ?? '—'}</Typography>
                                        </TableCell>
                                        <TableCell>
                                            <Typography variant="body2">{o.cidade ? `${o.cidade.cidade} — ${o.cidade.estado}` : '—'}</Typography>
                                        </TableCell>
                                        <TableCell>
                                            {o.grupo_tarifario ? (
                                                <Typography variant="caption" fontWeight={700}
                                                    sx={{ px: 1, py: 0.3, borderRadius: 1, bgcolor: alpha('#2563EB', 0.09), color: '#2563EB' }}>
                                                    {o.grupo_tarifario}
                                                </Typography>
                                            ) : <Typography variant="caption" color="text.disabled">—</Typography>}
                                        </TableCell>
                                        <TableCell>
                                            <Typography variant="body2">{o.geracao_estimada ? `${o.geracao_estimada} kWh/mês` : '—'}</Typography>
                                        </TableCell>
                                        <TableCell>
                                            <Typography variant="body2" fontWeight={600}>{fmtMoney(o.preco_total)}</Typography>
                                        </TableCell>
                                        <TableCell onClick={(e) => e.stopPropagation()}>
                                            <OrcamentoStatusChip status={o.status} />
                                        </TableCell>
                                        <TableCell>
                                            <Typography variant="body2">{new Date(o.created_at).toLocaleDateString('pt-BR')}</Typography>
                                        </TableCell>
                                        <TableCell align="right" onClick={(e) => e.stopPropagation()}>
                                            <Tooltip title="Ver detalhes">
                                                <IconButton size="small" component={Link} href={route('consultor.orcamentos.show', o.id)}>
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
                </>
            )}
        </AppLayout>
    );
}
