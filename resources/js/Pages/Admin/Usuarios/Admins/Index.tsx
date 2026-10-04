import React, { useState } from 'react';
import {
    Avatar,
    Box,
    Button,
    Card,
    Chip,
    IconButton,
    InputAdornment,
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
import AdminPanelSettingsRoundedIcon from '@mui/icons-material/AdminPanelSettingsRounded';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { ConfirmDialog } from '@/Components/UI/ConfirmDialog';
import { BoolChip } from '@/Components/UI/StatusChip';
import { PageProps, PaginatedData } from '@/types';
import { rotuloPaginacao } from '@/Components/UI/TablePagination';

interface Admin {
    id: number;
    name: string;
    email: string;
    cpf?: string;
    celular?: string;
    status: boolean;
    created_at: string;
}

interface Props extends PageProps {
    admins: PaginatedData<Admin>;
    filters: { search?: string };
    current_id: number;
}

export default function AdminsIndex({ admins, filters, current_id }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [deleteTarget, setDeleteTarget] = useState<Admin | null>(null);

    function applyFilters(overrides: object = {}) {
        router.get(route('admin.usuarios.admins.index'), { search, ...overrides }, { preserveState: true, replace: true });
    }

    function handleDelete() {
        if (!deleteTarget) return;
        router.delete(route('admin.usuarios.admins.destroy', deleteTarget.id), {
            onSuccess: () => setDeleteTarget(null),
        });
    }

    return (
        <AppLayout>
            <Head title="Administradores" />

            <PageHeader
                title="Administradores"
                breadcrumbs={[{ label: 'Usuários' }, { label: 'Admins' }]}
                action={
                    <Button
                        component={Link}
                        href={route('admin.usuarios.admins.create')}
                        variant="contained"
                        startIcon={<AddRoundedIcon />}
                    >
                        Novo Admin
                    </Button>
                }
            />

            <Card sx={{ mb: 3, p: 2 }}>
                <Box sx={{ display: 'flex', gap: 2, alignItems: 'center' }}>
                    <TextField
                        size="small"
                        placeholder="Buscar nome ou e-mail..."
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
                    <Button variant="contained" size="small" onClick={() => applyFilters()}>
                        Buscar
                    </Button>
                    {search && (
                        <Button size="small" onClick={() => { setSearch(''); applyFilters({ search: '' }); }}>
                            Limpar
                        </Button>
                    )}
                </Box>
            </Card>

            <Card>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell>Administrador</TableCell>
                            <TableCell>E-mail</TableCell>
                            <TableCell>Celular</TableCell>
                            <TableCell>Status</TableCell>
                            <TableCell>Cadastro</TableCell>
                            <TableCell align="right">Ações</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {admins.data.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={6} align="center" sx={{ py: 6 }}>
                                    <AdminPanelSettingsRoundedIcon sx={{ fontSize: 48, color: 'text.disabled', mb: 1 }} />
                                    <Typography color="text.secondary">Nenhum administrador encontrado</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                        {admins.data.map((a) => (
                            <TableRow key={a.id} hover>
                                <TableCell>
                                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                                        <Avatar sx={{ width: 36, height: 36, fontSize: '0.9rem', bgcolor: 'error.light' }}>
                                            {a.name.charAt(0).toUpperCase()}
                                        </Avatar>
                                        <Box>
                                            <Typography variant="body2" fontWeight={600}>{a.name}</Typography>
                                            {a.id === current_id && (
                                                <Chip label="Você" size="small" color="primary" sx={{ height: 18, fontSize: '0.65rem', mt: 0.3 }} />
                                            )}
                                        </Box>
                                    </Box>
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2">{a.email}</Typography>
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2">{a.celular ?? '—'}</Typography>
                                </TableCell>
                                <TableCell>
                                    <BoolChip value={a.status} />
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2">
                                        {new Date(a.created_at).toLocaleDateString('pt-BR')}
                                    </Typography>
                                </TableCell>
                                <TableCell align="right">
                                    <Tooltip title="Editar">
                                        <IconButton size="small" component={Link} href={route('admin.usuarios.admins.edit', a.id)}>
                                            <EditRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                    <Tooltip title={a.id === current_id ? 'Não é possível excluir sua própria conta' : 'Excluir'}>
                                        <span>
                                            <IconButton
                                                size="small"
                                                color="error"
                                                onClick={() => setDeleteTarget(a)}
                                                disabled={a.id === current_id}
                                            >
                                                <DeleteRoundedIcon fontSize="small" />
                                            </IconButton>
                                        </span>
                                    </Tooltip>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>

                {admins.last_page > 1 && (
                    <Box sx={{ display: 'flex', justifyContent: 'center', gap: 1, p: 2 }}>
                        {admins.links.map((link, i) => (
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
                        {admins.from}–{admins.to} de {admins.total} administradores
                    </Typography>
                </Box>
            </Card>

            <ConfirmDialog
                open={!!deleteTarget}
                title="Excluir Administrador"
                message={`Deseja excluir o administrador "${deleteTarget?.name}"? Esta ação não pode ser desfeita.`}
                confirmLabel="Excluir"
                onConfirm={handleDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </AppLayout>
    );
}
