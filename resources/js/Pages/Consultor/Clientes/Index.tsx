import React, { useState } from 'react';
import {
    Avatar,
    Box,
    Button,
    Card,
    Chip,
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
import AddRoundedIcon from '@mui/icons-material/AddRounded';
import SearchRoundedIcon from '@mui/icons-material/SearchRounded';
import EditRoundedIcon from '@mui/icons-material/EditRounded';
import DeleteRoundedIcon from '@mui/icons-material/DeleteRounded';
import VisibilityRoundedIcon from '@mui/icons-material/VisibilityRounded';
import PeopleRoundedIcon from '@mui/icons-material/PeopleRounded';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { ClienteStatusChip } from '@/Components/UI/StatusChip';
import { PageProps, PaginatedData } from '@/types';

interface Cliente {
    id: number;
    tipo_pessoa: 'pf' | 'pj';
    nome?: string;
    razao_social?: string;
    email?: string;
    telefone?: string;
    celular?: string;
    status: string;
    created_at: string;
    cidade?: { id: number; cidade: string; estado: string };
}

interface Props extends PageProps {
    clientes: PaginatedData<Cliente>;
    filters: { search?: string; status?: string };
}

export default function ClientesIndex({ clientes, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');

    function applyFilters(overrides: object = {}) {
        router.get(route('consultor.clientes.index'), {
            search, status, ...overrides,
        }, { preserveState: true, replace: true });
    }

    function handleDelete(id: number) {
        if (!confirm('Confirmar exclusão do cliente?')) return;
        router.delete(route('consultor.clientes.destroy', id));
    }

    function nomeDisplay(c: Cliente) {
        return c.tipo_pessoa === 'pj' ? c.razao_social : c.nome;
    }

    return (
        <AppLayout>
            <Head title="Meus Clientes" />

            <PageHeader
                title="Meus Clientes"
                breadcrumbs={[{ label: 'Clientes' }]}
                action={
                    <Button
                        component={Link}
                        href={route('consultor.clientes.create')}
                        variant="contained"
                        startIcon={<AddRoundedIcon />}
                    >
                        Novo Cliente
                    </Button>
                }
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
                        sx={{ minWidth: 180 }}
                    >
                        <MenuItem value="">Todos</MenuItem>
                        <MenuItem value="novo">Novo</MenuItem>
                        <MenuItem value="orcamento_gerado">Orçamento Gerado</MenuItem>
                        <MenuItem value="visita_agendada">Visita Agendada</MenuItem>
                        <MenuItem value="finalizado">Finalizado</MenuItem>
                    </TextField>
                    <Button variant="contained" onClick={() => applyFilters()} size="small">Buscar</Button>
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
                            <TableCell>Cliente</TableCell>
                            <TableCell>Contato</TableCell>
                            <TableCell>Cidade</TableCell>
                            <TableCell>Status</TableCell>
                            <TableCell>Cadastro</TableCell>
                            <TableCell align="right">Ações</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {clientes.data.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={6} align="center" sx={{ py: 6 }}>
                                    <PeopleRoundedIcon sx={{ fontSize: 48, color: 'text.disabled', mb: 1 }} />
                                    <Typography color="text.secondary">Nenhum cliente encontrado</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                        {clientes.data.map((c) => (
                            <TableRow key={c.id} hover>
                                <TableCell>
                                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                                        <Avatar sx={{ width: 36, height: 36, fontSize: '0.9rem', bgcolor: 'primary.light' }}>
                                            {(nomeDisplay(c) ?? '?').charAt(0).toUpperCase()}
                                        </Avatar>
                                        <Box>
                                            <Typography variant="body2" fontWeight={600}>
                                                {nomeDisplay(c) ?? '—'}
                                            </Typography>
                                            <Chip
                                                label={c.tipo_pessoa === 'pj' ? 'PJ' : 'PF'}
                                                size="small"
                                                sx={{ height: 18, fontSize: '0.65rem', mt: 0.3 }}
                                            />
                                        </Box>
                                    </Box>
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2">{c.email ?? '—'}</Typography>
                                    <Typography variant="caption" color="text.secondary">
                                        {c.celular ?? c.telefone ?? '—'}
                                    </Typography>
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2">
                                        {c.cidade ? `${c.cidade.cidade} - ${c.cidade.estado}` : '—'}
                                    </Typography>
                                </TableCell>
                                <TableCell><ClienteStatusChip status={c.status} /></TableCell>
                                <TableCell>
                                    <Typography variant="body2">
                                        {new Date(c.created_at).toLocaleDateString('pt-BR')}
                                    </Typography>
                                </TableCell>
                                <TableCell align="right">
                                    <Tooltip title="Ver detalhes">
                                        <IconButton size="small" component={Link} href={route('consultor.clientes.show', c.id)}>
                                            <VisibilityRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                    <Tooltip title="Editar">
                                        <IconButton size="small" component={Link} href={route('consultor.clientes.edit', c.id)}>
                                            <EditRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                    <Tooltip title="Excluir">
                                        <IconButton size="small" color="error" onClick={() => handleDelete(c.id)}>
                                            <DeleteRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>

                {clientes.last_page > 1 && (
                    <Box sx={{ display: 'flex', justifyContent: 'center', gap: 1, p: 2 }}>
                        {clientes.links.map((link, i) => (
                            <Button
                                key={i}
                                size="small"
                                variant={link.active ? 'contained' : 'outlined'}
                                disabled={!link.url}
                                onClick={() => link.url && router.visit(link.url)}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                                sx={{ minWidth: 36 }}
                            />
                        ))}
                    </Box>
                )}

                <Box sx={{ px: 2, pb: 1.5 }}>
                    <Typography variant="caption" color="text.secondary">
                        {clientes.from}–{clientes.to} de {clientes.total} clientes
                    </Typography>
                </Box>
            </Card>
        </AppLayout>
    );
}
