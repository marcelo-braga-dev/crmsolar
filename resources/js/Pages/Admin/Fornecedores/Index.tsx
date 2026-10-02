import React, { useState } from 'react';
import {
    Avatar,
    Box,
    Button,
    Card,
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
import VisibilityRoundedIcon from '@mui/icons-material/VisibilityRounded';
import SolarPowerRoundedIcon from '@mui/icons-material/SolarPowerRounded';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { ConfirmDialog } from '@/Components/UI/ConfirmDialog';
import { BoolChip } from '@/Components/UI/StatusChip';
import { PageProps, PaginatedData } from '@/types';

interface Fornecedor {
    id: number;
    nome: string;
    cnpj?: string;
    email?: string;
    telefone?: string;
    celular?: string;
    representante?: string;
    margem_padrao?: string;
    ativo: boolean;
    kits_count: number;
}

interface Props extends PageProps {
    fornecedores: PaginatedData<Fornecedor>;
    filters: { search?: string };
}

export default function FornecedoresIndex({ fornecedores, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [deleteTarget, setDeleteTarget] = useState<Fornecedor | null>(null);

    function applyFilters(overrides: object = {}) {
        router.get(route('admin.fornecedores.index'), { search, ...overrides }, { preserveState: true, replace: true });
    }

    function handleDelete() {
        if (!deleteTarget) return;
        router.delete(route('admin.fornecedores.destroy', deleteTarget.id), {
            onSuccess: () => setDeleteTarget(null),
        });
    }

    return (
        <AppLayout>
            <Head title="Fornecedores" />

            <PageHeader
                title="Fornecedores"
                breadcrumbs={[{ label: 'Fornecedores' }]}
                action={
                    <Button
                        component={Link}
                        href={route('admin.fornecedores.create')}
                        variant="contained"
                        startIcon={<AddRoundedIcon />}
                    >
                        Novo Fornecedor
                    </Button>
                }
            />

            <Card sx={{ mb: 3, p: 2 }}>
                <Box sx={{ display: 'flex', gap: 2, alignItems: 'center' }}>
                    <TextField
                        size="small"
                        placeholder="Buscar nome, CNPJ, e-mail..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                        InputProps={{ startAdornment: <InputAdornment position="start"><SearchRoundedIcon fontSize="small" /></InputAdornment> }}
                        sx={{ minWidth: 280 }}
                    />
                    <Button variant="contained" size="small" onClick={() => applyFilters()}>Buscar</Button>
                    {search && <Button size="small" onClick={() => { setSearch(''); applyFilters({ search: '' }); }}>Limpar</Button>}
                </Box>
            </Card>

            <Card>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell>Fornecedor</TableCell>
                            <TableCell>Contato</TableCell>
                            <TableCell>Representante</TableCell>
                            <TableCell align="right">Margem Adicional</TableCell>
                            <TableCell align="right">Kits</TableCell>
                            <TableCell>Status</TableCell>
                            <TableCell align="right">Ações</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {fornecedores.data.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={7} align="center" sx={{ py: 6 }}>
                                    <SolarPowerRoundedIcon sx={{ fontSize: 48, color: 'text.disabled', mb: 1 }} />
                                    <Typography color="text.secondary">Nenhum fornecedor encontrado</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                        {fornecedores.data.map((f) => (
                            <TableRow key={f.id} hover>
                                <TableCell>
                                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                                        <Avatar sx={{ width: 36, height: 36, fontSize: '0.9rem', bgcolor: 'warning.light' }}>
                                            {f.nome.charAt(0).toUpperCase()}
                                        </Avatar>
                                        <Box>
                                            <Typography variant="body2" fontWeight={600}>{f.nome}</Typography>
                                            <Typography variant="caption" color="text.secondary">{f.cnpj ?? '—'}</Typography>
                                        </Box>
                                    </Box>
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2">{f.email ?? '—'}</Typography>
                                    <Typography variant="caption" color="text.secondary">{f.celular ?? f.telefone ?? '—'}</Typography>
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2">{f.representante ?? '—'}</Typography>
                                </TableCell>
                                <TableCell align="right">
                                    <Typography variant="body2">
                                        {f.margem_padrao ? `${parseFloat(f.margem_padrao).toFixed(2)}%` : '—'}
                                    </Typography>
                                </TableCell>
                                <TableCell align="right">
                                    <Typography variant="body2">{f.kits_count}</Typography>
                                </TableCell>
                                <TableCell><BoolChip value={f.ativo} /></TableCell>
                                <TableCell align="right">
                                    <Tooltip title="Ver detalhes">
                                        <IconButton size="small" component={Link} href={route('admin.fornecedores.show', f.id)}>
                                            <VisibilityRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                    <Tooltip title="Editar">
                                        <IconButton size="small" component={Link} href={route('admin.fornecedores.edit', f.id)}>
                                            <EditRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                    <Tooltip title="Excluir">
                                        <IconButton size="small" color="error" onClick={() => setDeleteTarget(f)}>
                                            <DeleteRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>

                {fornecedores.last_page > 1 && (
                    <Box sx={{ display: 'flex', justifyContent: 'center', gap: 1, p: 2 }}>
                        {fornecedores.links.map((link, i) => (
                            <Button key={i} size="small" variant={link.active ? 'contained' : 'outlined'}
                                disabled={!link.url} onClick={() => link.url && router.visit(link.url)}
                                dangerouslySetInnerHTML={{ __html: link.label }} sx={{ minWidth: 36 }} />
                        ))}
                    </Box>
                )}
                <Box sx={{ px: 2, pb: 1.5 }}>
                    <Typography variant="caption" color="text.secondary">
                        {fornecedores.from}–{fornecedores.to} de {fornecedores.total} fornecedores
                    </Typography>
                </Box>
            </Card>

            <ConfirmDialog
                open={!!deleteTarget}
                title="Excluir Fornecedor"
                message={`Deseja excluir o fornecedor "${deleteTarget?.nome}"?`}
                confirmLabel="Excluir"
                onConfirm={handleDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </AppLayout>
    );
}
