import React, { useState } from 'react';
import {
    Box,
    Button,
    Card,
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
} from '@mui/material';
import SearchRoundedIcon from '@mui/icons-material/SearchRounded';
import VisibilityRoundedIcon from '@mui/icons-material/VisibilityRounded';
import TrendingUpRoundedIcon from '@mui/icons-material/TrendingUpRounded';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { LeadStatusChip } from '@/Components/UI/StatusChip';
import { PageProps, PaginatedData } from '@/types';
import { rotuloPaginacao } from '@/Components/UI/TablePagination';

interface Lead {
    id: number;
    nome?: string;
    email?: string;
    telefone?: string;
    cidade?: string;
    estado?: string;
    consumo_mensal?: number;
    origem?: string;
    status: string;
    created_at: string;
}

interface Props extends PageProps {
    leads: PaginatedData<Lead>;
    filters: { search?: string; status?: string };
}

export default function LeadsIndex({ leads, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');

    function applyFilters(overrides: object = {}) {
        router.get(route('consultor.leads.index'), {
            search, status, ...overrides,
        }, { preserveState: true, replace: true });
    }

    return (
        <AppLayout>
            <Head title="Meus Leads" />

            <PageHeader
                title="Meus Leads"
                breadcrumbs={[{ label: 'Leads' }]}
            />

            <Card sx={{ mb: 3, p: 2 }}>
                <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap', alignItems: 'center' }}>
                    <TextField
                        size="small"
                        placeholder="Buscar nome, e-mail, telefone..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                        InputProps={{
                            startAdornment: (
                                <InputAdornment position="start">
                                    <SearchRoundedIcon fontSize="small" />
                                </InputAdornment>
                            ),
                        }}
                        sx={{ minWidth: 260 }}
                    />
                    <TextField
                        select size="small" label="Status" value={status}
                        onChange={(e) => { setStatus(e.target.value); applyFilters({ status: e.target.value }); }}
                        sx={{ minWidth: 160 }}
                    >
                        <MenuItem value="">Todos</MenuItem>
                        <MenuItem value="novo">Novo</MenuItem>
                        <MenuItem value="contatado">Contatado</MenuItem>
                        <MenuItem value="encaminhado">Encaminhado</MenuItem>
                        <MenuItem value="convertido">Convertido</MenuItem>
                        <MenuItem value="perdido">Perdido</MenuItem>
                    </TextField>
                    <Button variant="contained" size="small" onClick={() => applyFilters()}>Buscar</Button>
                    {(search || status) && (
                        <Button size="small" onClick={() => { setSearch(''); setStatus(''); applyFilters({ search: '', status: '' }); }}>
                            Limpar
                        </Button>
                    )}
                </Box>
            </Card>

            <Card>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell>Nome</TableCell>
                            <TableCell>Contato</TableCell>
                            <TableCell>Localidade</TableCell>
                            <TableCell>Consumo</TableCell>
                            <TableCell>Origem</TableCell>
                            <TableCell>Status</TableCell>
                            <TableCell>Data</TableCell>
                            <TableCell align="right">Ações</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {leads.data.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={8} align="center" sx={{ py: 6 }}>
                                    <TrendingUpRoundedIcon sx={{ fontSize: 48, color: 'text.disabled', mb: 1 }} />
                                    <Typography color="text.secondary">Nenhum lead encontrado</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                        {leads.data.map((l) => (
                            <TableRow key={l.id} hover>
                                <TableCell>
                                    <Typography variant="body2" fontWeight={600}>{l.nome ?? '—'}</Typography>
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2">{l.email ?? '—'}</Typography>
                                    <Typography variant="caption" color="text.secondary">{l.telefone ?? '—'}</Typography>
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2">
                                        {[l.cidade, l.estado].filter(Boolean).join(' - ') || '—'}
                                    </Typography>
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2">
                                        {l.consumo_mensal ? `${l.consumo_mensal} kWh/mês` : '—'}
                                    </Typography>
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2">{l.origem ?? '—'}</Typography>
                                </TableCell>
                                <TableCell><LeadStatusChip status={l.status} /></TableCell>
                                <TableCell>
                                    <Typography variant="body2">
                                        {new Date(l.created_at).toLocaleDateString('pt-BR')}
                                    </Typography>
                                </TableCell>
                                <TableCell align="right">
                                    <Tooltip title="Ver detalhes">
                                        <IconButton size="small" component={Link} href={route('consultor.leads.show', l.id)}>
                                            <VisibilityRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>

                {leads.last_page > 1 && (
                    <Box sx={{ display: 'flex', justifyContent: 'center', gap: 1, p: 2 }}>
                        {leads.links.map((link, i) => (
                            <Button
                                key={i} size="small"
                                variant={link.active ? 'contained' : 'outlined'}
                                disabled={!link.url}
                                onClick={() => link.url && router.visit(link.url)}
                                sx={{ minWidth: 36 }}
                            >{rotuloPaginacao(link.label)}</Button>
                        ))}
                    </Box>
                )}

                <Box sx={{ px: 2, pb: 1.5 }}>
                    <Typography variant="caption" color="text.secondary">
                        {leads.from}–{leads.to} de {leads.total} leads
                    </Typography>
                </Box>
            </Card>
        </AppLayout>
    );
}
