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
import ManageAccountsRoundedIcon from '@mui/icons-material/ManageAccountsRounded';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { ConfirmDialog } from '@/Components/UI/ConfirmDialog';
import { BoolChip } from '@/Components/UI/StatusChip';
import { PageProps, PaginatedData } from '@/types';
import { rotuloPaginacao } from '@/Components/UI/TablePagination';

interface Consultor {
    id: number;
    name: string;
    email: string;
    tipo: 'consultor' | 'admin_consultor';
    cpf?: string;
    celular?: string;
    comissao_percentual?: string;
    status: boolean;
    created_at: string;
    clientes_count: number;
    orcamentos_count: number;
}

interface Props extends PageProps {
    consultores: PaginatedData<Consultor>;
    filters: { search?: string; status?: string };
}

export default function ConsultoresIndex({ consultores, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [deleteTarget, setDeleteTarget] = useState<Consultor | null>(null);

    function applyFilters(overrides: object = {}) {
        router.get(route('admin.usuarios.consultores.index'), {
            search, status, ...overrides,
        }, { preserveState: true, replace: true });
    }

    function handleDelete() {
        if (!deleteTarget) return;
        router.delete(route('admin.usuarios.consultores.destroy', deleteTarget.id), {
            onSuccess: () => setDeleteTarget(null),
        });
    }

    return (
        <AppLayout>
            <Head title="Consultores" />

            <PageHeader
                title="Consultores"
                breadcrumbs={[{ label: 'Usuários' }, { label: 'Consultores' }]}
                action={
                    <Button
                        component={Link}
                        href={route('admin.usuarios.consultores.create')}
                        variant="contained"
                        startIcon={<AddRoundedIcon />}
                    >
                        Novo Consultor
                    </Button>
                }
            />

            <Card sx={{ mb: 3, p: 2 }}>
                <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap', alignItems: 'center' }}>
                    <TextField
                        size="small"
                        placeholder="Buscar nome, e-mail, CPF..."
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
                        sx={{ minWidth: 280 }}
                    />
                    <TextField
                        select
                        size="small"
                        label="Status"
                        value={status}
                        onChange={(e) => { setStatus(e.target.value); applyFilters({ status: e.target.value }); }}
                        sx={{ minWidth: 160 }}
                    >
                        <MenuItem value="">Todos</MenuItem>
                        <MenuItem value="1">Ativo</MenuItem>
                        <MenuItem value="0">Inativo</MenuItem>
                    </TextField>
                    <Button variant="contained" size="small" onClick={() => applyFilters()}>
                        Buscar
                    </Button>
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
                            <TableCell>Consultor</TableCell>
                            <TableCell>Contato</TableCell>
                            <TableCell>Tipo</TableCell>
                            <TableCell align="right">Comissão</TableCell>
                            <TableCell align="right">Clientes</TableCell>
                            <TableCell align="right">Orçamentos</TableCell>
                            <TableCell>Status</TableCell>
                            <TableCell align="right">Ações</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {consultores.data.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={8} align="center" sx={{ py: 6 }}>
                                    <ManageAccountsRoundedIcon sx={{ fontSize: 48, color: 'text.disabled', mb: 1 }} />
                                    <Typography color="text.secondary">Nenhum consultor encontrado</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                        {consultores.data.map((v) => (
                            <TableRow key={v.id} hover>
                                <TableCell>
                                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                                        <Avatar sx={{ width: 36, height: 36, fontSize: '0.9rem', bgcolor: 'primary.light' }}>
                                            {v.name.charAt(0).toUpperCase()}
                                        </Avatar>
                                        <Box>
                                            <Typography variant="body2" fontWeight={600}>{v.name}</Typography>
                                            <Typography variant="caption" color="text.secondary">{v.cpf ?? '—'}</Typography>
                                        </Box>
                                    </Box>
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2">{v.email}</Typography>
                                    <Typography variant="caption" color="text.secondary">{v.celular ?? '—'}</Typography>
                                </TableCell>
                                <TableCell>
                                    <Chip
                                        label={v.tipo === 'admin_consultor' ? 'Admin/Consultor' : 'Consultor'}
                                        size="small"
                                        color={v.tipo === 'admin_consultor' ? 'secondary' : 'default'}
                                        variant="outlined"
                                    />
                                </TableCell>
                                <TableCell align="right">
                                    <Typography variant="body2">
                                        {v.comissao_percentual ? `${parseFloat(v.comissao_percentual).toFixed(2)}%` : '—'}
                                    </Typography>
                                </TableCell>
                                <TableCell align="right">
                                    <Typography variant="body2">{v.clientes_count}</Typography>
                                </TableCell>
                                <TableCell align="right">
                                    <Typography variant="body2">{v.orcamentos_count}</Typography>
                                </TableCell>
                                <TableCell>
                                    <BoolChip value={v.status} />
                                </TableCell>
                                <TableCell align="right">
                                    <Tooltip title="Editar">
                                        <IconButton size="small" component={Link} href={route('admin.usuarios.consultores.edit', v.id)}>
                                            <EditRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                    <Tooltip title="Excluir">
                                        <IconButton size="small" color="error" onClick={() => setDeleteTarget(v)}>
                                            <DeleteRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>

                {consultores.last_page > 1 && (
                    <Box sx={{ display: 'flex', justifyContent: 'center', gap: 1, p: 2 }}>
                        {consultores.links.map((link, i) => (
                            <Button
                                key={i}
                                size="small"
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
                        {consultores.from}–{consultores.to} de {consultores.total} consultores
                    </Typography>
                </Box>
            </Card>

            <ConfirmDialog
                open={!!deleteTarget}
                title="Excluir Consultor"
                message={`Deseja excluir o consultor "${deleteTarget?.name}"? Esta ação não pode ser desfeita.`}
                confirmLabel="Excluir"
                onConfirm={handleDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </AppLayout>
    );
}
