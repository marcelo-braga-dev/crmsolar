import React, { useState } from 'react';
import {
    Avatar,
    Box,
    Button,
    Card,
    CardContent,
    Chip,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    Grid,
    IconButton,
    InputAdornment,
    MenuItem,
    Switch,
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
import AddRoundedIcon from '@mui/icons-material/AddRounded';
import EditRoundedIcon from '@mui/icons-material/EditRounded';
import DeleteRoundedIcon from '@mui/icons-material/DeleteRounded';
import SearchRoundedIcon from '@mui/icons-material/SearchRounded';
import LabelRoundedIcon from '@mui/icons-material/LabelRounded';
import CheckCircleRoundedIcon from '@mui/icons-material/CheckCircleRounded';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { TablePagination } from '@/Components/UI/TablePagination';
import { ConfirmDialog } from '@/Components/UI/ConfirmDialog';
import { PageProps, PaginatedData } from '@/types';

interface Marca {
    id: number;
    nome: string;
    url_logo?: string;
    ativo: boolean;
    produtos_count: number;
    updated_at: string;
}

interface Stats { total: number; ativas: number }

interface Props extends PageProps {
    marcas: PaginatedData<Marca>;
    filters: { search?: string; ativo?: string };
    stats: Stats;
}

type FormState = { nome: string; url_logo: string; ativo: boolean };

export default function MarcasIndex({ marcas, filters, stats }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [ativoFilter, setAtivoFilter] = useState(filters.ativo ?? '');

    const [modalOpen, setModalOpen] = useState(false);
    const [editTarget, setEditTarget] = useState<Marca | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<Marca | null>(null);

    const { data, setData, post, put, processing, reset, errors } = useForm<FormState>({
        nome: '',
        url_logo: '',
        ativo: true,
    });

    function applyFilters(overrides: object = {}) {
        router.get(route('admin.produtos.marcas.index'), {
            search, ativo: ativoFilter, ...overrides,
        }, { preserveState: true, replace: true });
    }

    function openCreate() {
        setEditTarget(null);
        reset();
        setData({ nome: '', url_logo: '', ativo: true });
        setModalOpen(true);
    }

    function openEdit(m: Marca) {
        setEditTarget(m);
        setData({ nome: m.nome, url_logo: m.url_logo ?? '', ativo: m.ativo });
        setModalOpen(true);
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (editTarget) {
            put(route('admin.produtos.marcas.update', editTarget.id), {
                onSuccess: () => setModalOpen(false),
            });
        } else {
            post(route('admin.produtos.marcas.store'), {
                onSuccess: () => setModalOpen(false),
            });
        }
    }

    function handleDelete() {
        if (!deleteTarget) return;
        router.delete(route('admin.produtos.marcas.destroy', deleteTarget.id), {
            onSuccess: () => setDeleteTarget(null),
        });
    }

    const initials = (nome: string) => nome.slice(0, 2).toUpperCase();

    return (
        <AppLayout>
            <Head title="Marcas" />

            <PageHeader
                title="Marcas"
                breadcrumbs={[{ label: 'Produtos' }, { label: 'Marcas' }]}
                action={
                    <Button variant="contained" startIcon={<AddRoundedIcon />} onClick={openCreate}>
                        Nova Marca
                    </Button>
                }
            />

            {/* Stats */}
            <Grid container spacing={2} sx={{ mb: 3 }}>
                <Grid size={{ xs: 6, sm: 3 }}>
                    <Card>
                        <CardContent sx={{ p: '16px !important' }}>
                            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                                <Box sx={{ width: 40, height: 40, borderRadius: 2, bgcolor: alpha('#6366f1', 0.12), display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#6366f1' }}>
                                    <LabelRoundedIcon fontSize="small" />
                                </Box>
                                <Box>
                                    <Typography variant="h5" fontWeight={700}>{stats.total}</Typography>
                                    <Typography variant="caption" color="text.secondary">Total de Marcas</Typography>
                                </Box>
                            </Box>
                        </CardContent>
                    </Card>
                </Grid>
                <Grid size={{ xs: 6, sm: 3 }}>
                    <Card>
                        <CardContent sx={{ p: '16px !important' }}>
                            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                                <Box sx={{ width: 40, height: 40, borderRadius: 2, bgcolor: alpha('#22c55e', 0.12), display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#22c55e' }}>
                                    <CheckCircleRoundedIcon fontSize="small" />
                                </Box>
                                <Box>
                                    <Typography variant="h5" fontWeight={700}>{stats.ativas}</Typography>
                                    <Typography variant="caption" color="text.secondary">Marcas Ativas</Typography>
                                </Box>
                            </Box>
                        </CardContent>
                    </Card>
                </Grid>
            </Grid>

            {/* Filtros */}
            <Card sx={{ mb: 3, p: 2 }}>
                <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap', alignItems: 'center' }}>
                    <TextField
                        size="small"
                        placeholder="Buscar por nome..."
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
                        sx={{ minWidth: 240 }}
                    />
                    <TextField
                        select size="small" label="Status" value={ativoFilter}
                        onChange={(e) => { setAtivoFilter(e.target.value); applyFilters({ ativo: e.target.value }); }}
                        sx={{ minWidth: 140 }}
                    >
                        <MenuItem value="">Todos</MenuItem>
                        <MenuItem value="1">Ativas</MenuItem>
                        <MenuItem value="0">Inativas</MenuItem>
                    </TextField>
                    <Button variant="contained" size="small" onClick={() => applyFilters()}>Buscar</Button>
                    {(search || ativoFilter) && (
                        <Button size="small" onClick={() => { setSearch(''); setAtivoFilter(''); applyFilters({ search: '', ativo: '' }); }}>
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
                            <TableCell>Marca</TableCell>
                            <TableCell>Produtos</TableCell>
                            <TableCell>Status</TableCell>
                            <TableCell>Atualizado</TableCell>
                            <TableCell align="right">Ações</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {marcas.data.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={5} align="center" sx={{ py: 6 }}>
                                    <LabelRoundedIcon sx={{ fontSize: 48, color: 'text.disabled', mb: 1 }} />
                                    <Typography color="text.secondary">Nenhuma marca encontrada</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                        {marcas.data.map((m) => (
                            <TableRow key={m.id} hover>
                                <TableCell>
                                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                                        {m.url_logo ? (
                                            <Avatar src={m.url_logo} sx={{ width: 36, height: 36 }} variant="rounded" />
                                        ) : (
                                            <Avatar sx={{ width: 36, height: 36, fontSize: '0.8rem', bgcolor: 'primary.light' }} variant="rounded">
                                                {initials(m.nome)}
                                            </Avatar>
                                        )}
                                        <Typography variant="body2" fontWeight={600}>{m.nome}</Typography>
                                    </Box>
                                </TableCell>
                                <TableCell>
                                    <Chip label={m.produtos_count} size="small" variant="outlined" />
                                </TableCell>
                                <TableCell>
                                    <Chip
                                        label={m.ativo ? 'Ativa' : 'Inativa'}
                                        size="small"
                                        color={m.ativo ? 'success' : 'default'}
                                        variant={m.ativo ? 'filled' : 'outlined'}
                                    />
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2" color="text.secondary">
                                        {new Date(m.updated_at).toLocaleDateString('pt-BR')}
                                    </Typography>
                                </TableCell>
                                <TableCell align="right">
                                    <Tooltip title="Editar">
                                        <IconButton size="small" onClick={() => openEdit(m)}>
                                            <EditRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                    <Tooltip title="Excluir">
                                        <IconButton size="small" color="error" onClick={() => setDeleteTarget(m)}>
                                            <DeleteRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
                <TablePagination {...marcas} label="marcas" />
            </Card>

            {/* Modal criar/editar */}
            <Dialog open={modalOpen} onClose={() => setModalOpen(false)} maxWidth="sm" fullWidth>
                <form onSubmit={handleSubmit}>
                    <DialogTitle>{editTarget ? 'Editar Marca' : 'Nova Marca'}</DialogTitle>
                    <DialogContent sx={{ pt: '16px !important', display: 'flex', flexDirection: 'column', gap: 2 }}>
                        <TextField
                            label="Nome da Marca *"
                            value={data.nome}
                            onChange={(e) => setData('nome', e.target.value)}
                            error={!!errors.nome}
                            helperText={errors.nome}
                            fullWidth
                            autoFocus
                        />
                        <TextField
                            label="URL do Logo"
                            value={data.url_logo}
                            onChange={(e) => setData('url_logo', e.target.value)}
                            error={!!errors.url_logo}
                            helperText={errors.url_logo ?? 'Link direto para imagem (png, svg, jpg)'}
                            fullWidth
                        />
                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                            <Switch checked={data.ativo} onChange={(e) => setData('ativo', e.target.checked)} />
                            <Typography variant="body2">Marca ativa</Typography>
                        </Box>
                    </DialogContent>
                    <DialogActions sx={{ px: 3, pb: 2 }}>
                        <Button onClick={() => setModalOpen(false)}>Cancelar</Button>
                        <Button type="submit" variant="contained" disabled={processing}>
                            {editTarget ? 'Salvar' : 'Criar'}
                        </Button>
                    </DialogActions>
                </form>
            </Dialog>

            {/* Confirm delete */}
            <ConfirmDialog
                open={!!deleteTarget}
                title="Excluir Marca"
                message={`Deseja excluir a marca "${deleteTarget?.nome}"? Esta ação não pode ser desfeita.`}
                confirmLabel="Excluir"
                onConfirm={handleDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </AppLayout>
    );
}
