import React, { useState } from 'react';
import {
    Box,
    Button,
    Card,
    Chip,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    FormControl,
    FormControlLabel,
    Grid,
    IconButton,
    InputAdornment,
    InputLabel,
    MenuItem,
    Select,
    Switch,
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
import ElectricBoltRoundedIcon from '@mui/icons-material/ElectricBoltRounded';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { ConfirmDialog } from '@/Components/UI/ConfirmDialog';
import { BoolChip } from '@/Components/UI/StatusChip';
import { PageProps, PaginatedData } from '@/types';
import { rotuloPaginacao } from '@/Components/UI/TablePagination';

interface Concessionaria {
    id: number;
    nome: string;
    estado: string;
    tarifa_convencional: string;
    tarifa_ponta?: string;
    tarifa_intermediaria?: string;
    tarifa_fora_ponta?: string;
    ativo: boolean;
}

interface FormState {
    nome: string;
    estado: string;
    tarifa_convencional: string;
    tarifa_ponta: string;
    tarifa_intermediaria: string;
    tarifa_fora_ponta: string;
    ativo: boolean;
}

interface Props extends PageProps {
    concessionarias: PaginatedData<Concessionaria>;
    estados: string[];
    filters: { search?: string; estado?: string };
}

const ESTADOS_BR = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'];

export default function ConcessionariasIndex({ concessionarias, estados, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [estado, setEstado] = useState(filters.estado ?? '');
    const [modalOpen, setModalOpen] = useState(false);
    const [editTarget, setEditTarget] = useState<Concessionaria | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<Concessionaria | null>(null);

    const { data, setData, post, put, processing, reset, errors } = useForm<FormState>({
        nome: '', estado: '', tarifa_convencional: '', tarifa_ponta: '',
        tarifa_intermediaria: '', tarifa_fora_ponta: '', ativo: true,
    });

    function applyFilters(overrides: object = {}) {
        router.get(route('admin.configuracoes.concessionarias.index'), { search, estado, ...overrides }, { preserveState: true, replace: true });
    }

    function openCreate() {
        setEditTarget(null);
        reset();
        setData({ nome: '', estado: '', tarifa_convencional: '', tarifa_ponta: '', tarifa_intermediaria: '', tarifa_fora_ponta: '', ativo: true });
        setModalOpen(true);
    }

    function openEdit(c: Concessionaria) {
        setEditTarget(c);
        setData({
            nome: c.nome,
            estado: c.estado,
            tarifa_convencional: c.tarifa_convencional,
            tarifa_ponta: c.tarifa_ponta ?? '',
            tarifa_intermediaria: c.tarifa_intermediaria ?? '',
            tarifa_fora_ponta: c.tarifa_fora_ponta ?? '',
            ativo: c.ativo,
        });
        setModalOpen(true);
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (editTarget) {
            put(route('admin.configuracoes.concessionarias.update', editTarget.id), { onSuccess: () => setModalOpen(false) });
        } else {
            post(route('admin.configuracoes.concessionarias.store'), { onSuccess: () => setModalOpen(false) });
        }
    }

    function handleDelete() {
        if (!deleteTarget) return;
        router.delete(route('admin.configuracoes.concessionarias.destroy', deleteTarget.id), { onSuccess: () => setDeleteTarget(null) });
    }

    function tarifa(val?: string) {
        return val ? `R$ ${parseFloat(val).toFixed(5)}` : '—';
    }

    return (
        <AppLayout>
            <Head title="Concessionárias" />

            <PageHeader
                title="Concessionárias"
                breadcrumbs={[{ label: 'Configurações' }, { label: 'Concessionárias' }]}
                action={
                    <Button variant="contained" startIcon={<AddRoundedIcon />} onClick={openCreate}>
                        Nova Concessionária
                    </Button>
                }
            />

            <Card sx={{ mb: 3, p: 2 }}>
                <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap', alignItems: 'center' }}>
                    <TextField
                        size="small"
                        placeholder="Buscar pelo nome..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                        InputProps={{ startAdornment: <InputAdornment position="start"><SearchRoundedIcon fontSize="small" /></InputAdornment> }}
                        sx={{ minWidth: 260 }}
                    />
                    <TextField
                        select size="small" label="Estado" value={estado}
                        onChange={(e) => { setEstado(e.target.value); applyFilters({ estado: e.target.value }); }}
                        sx={{ minWidth: 120 }}
                    >
                        <MenuItem value="">Todos</MenuItem>
                        {estados.map((uf) => <MenuItem key={uf} value={uf}>{uf}</MenuItem>)}
                    </TextField>
                    <Button variant="contained" size="small" onClick={() => applyFilters()}>Buscar</Button>
                    {(search || estado) && (
                        <Button size="small" onClick={() => { setSearch(''); setEstado(''); applyFilters({ search: '', estado: '' }); }}>Limpar</Button>
                    )}
                </Box>
            </Card>

            <Card>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell>Concessionária</TableCell>
                            <TableCell>UF</TableCell>
                            <TableCell align="right">Tarifa Conv.</TableCell>
                            <TableCell align="right">Tarifa Ponta</TableCell>
                            <TableCell align="right">Fora Ponta</TableCell>
                            <TableCell>Status</TableCell>
                            <TableCell align="right">Ações</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {concessionarias.data.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={7} align="center" sx={{ py: 6 }}>
                                    <ElectricBoltRoundedIcon sx={{ fontSize: 48, color: 'text.disabled', mb: 1 }} />
                                    <Typography color="text.secondary">Nenhuma concessionária encontrada</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                        {concessionarias.data.map((c) => (
                            <TableRow key={c.id} hover>
                                <TableCell>
                                    <Typography variant="body2" fontWeight={600}>{c.nome}</Typography>
                                </TableCell>
                                <TableCell>
                                    <Chip label={c.estado} size="small" variant="outlined" />
                                </TableCell>
                                <TableCell align="right">
                                    <Typography variant="body2" fontFamily="monospace">{tarifa(c.tarifa_convencional)}</Typography>
                                </TableCell>
                                <TableCell align="right">
                                    <Typography variant="body2" fontFamily="monospace">{tarifa(c.tarifa_ponta)}</Typography>
                                </TableCell>
                                <TableCell align="right">
                                    <Typography variant="body2" fontFamily="monospace">{tarifa(c.tarifa_fora_ponta)}</Typography>
                                </TableCell>
                                <TableCell><BoolChip value={c.ativo} /></TableCell>
                                <TableCell align="right">
                                    <Tooltip title="Editar">
                                        <IconButton size="small" onClick={() => openEdit(c)}>
                                            <EditRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                    <Tooltip title="Excluir">
                                        <IconButton size="small" color="error" onClick={() => setDeleteTarget(c)}>
                                            <DeleteRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>

                {concessionarias.last_page > 1 && (
                    <Box sx={{ display: 'flex', justifyContent: 'center', gap: 1, p: 2 }}>
                        {concessionarias.links.map((link, i) => (
                            <Button key={i} size="small" variant={link.active ? 'contained' : 'outlined'}
                                disabled={!link.url} onClick={() => link.url && router.visit(link.url)} sx={{ minWidth: 36 }}
                            >{rotuloPaginacao(link.label)}</Button>
                        ))}
                    </Box>
                )}
                <Box sx={{ px: 2, pb: 1.5 }}>
                    <Typography variant="caption" color="text.secondary">
                        {concessionarias.from}–{concessionarias.to} de {concessionarias.total} concessionárias
                    </Typography>
                </Box>
            </Card>

            <Dialog open={modalOpen} onClose={() => setModalOpen(false)} maxWidth="md" fullWidth>
                <form onSubmit={handleSubmit}>
                    <DialogTitle>{editTarget ? 'Editar Concessionária' : 'Nova Concessionária'}</DialogTitle>
                    <DialogContent sx={{ pt: '16px !important' }}>
                        <Grid container spacing={3}>
                            <Grid size={{ xs: 12, sm: 8 }}>
                                <TextField label="Nome *" value={data.nome} onChange={(e) => setData('nome', e.target.value)}
                                    error={!!errors.nome} helperText={errors.nome} fullWidth autoFocus />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 4 }}>
                                <FormControl fullWidth error={!!errors.estado}>
                                    <InputLabel>Estado *</InputLabel>
                                    <Select value={data.estado} label="Estado *" onChange={(e) => setData('estado', e.target.value)}>
                                        {ESTADOS_BR.map((uf) => <MenuItem key={uf} value={uf}>{uf}</MenuItem>)}
                                    </Select>
                                </FormControl>
                            </Grid>
                            <Grid size={{ xs: 12, sm: 3 }}>
                                <TextField label="Tarifa Convencional *" type="number" value={data.tarifa_convencional}
                                    onChange={(e) => setData('tarifa_convencional', e.target.value)}
                                    error={!!errors.tarifa_convencional} helperText={errors.tarifa_convencional}
                                    inputProps={{ step: '0.00001', min: '0' }} fullWidth />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 3 }}>
                                <TextField label="Tarifa Ponta" type="number" value={data.tarifa_ponta}
                                    onChange={(e) => setData('tarifa_ponta', e.target.value)}
                                    inputProps={{ step: '0.00001', min: '0' }} fullWidth />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 3 }}>
                                <TextField label="Tarifa Intermediária" type="number" value={data.tarifa_intermediaria}
                                    onChange={(e) => setData('tarifa_intermediaria', e.target.value)}
                                    inputProps={{ step: '0.00001', min: '0' }} fullWidth />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 3 }}>
                                <TextField label="Tarifa Fora Ponta" type="number" value={data.tarifa_fora_ponta}
                                    onChange={(e) => setData('tarifa_fora_ponta', e.target.value)}
                                    inputProps={{ step: '0.00001', min: '0' }} fullWidth />
                            </Grid>
                            <Grid size={{ xs: 12 }}>
                                <FormControlLabel
                                    control={<Switch checked={data.ativo} onChange={(e) => setData('ativo', e.target.checked)} />}
                                    label="Concessionária ativa"
                                />
                            </Grid>
                        </Grid>
                    </DialogContent>
                    <DialogActions sx={{ px: 3, pb: 2 }}>
                        <Button onClick={() => setModalOpen(false)}>Cancelar</Button>
                        <Button type="submit" variant="contained" disabled={processing}>
                            {editTarget ? 'Salvar' : 'Criar'}
                        </Button>
                    </DialogActions>
                </form>
            </Dialog>

            <ConfirmDialog
                open={!!deleteTarget}
                title="Excluir Concessionária"
                message={`Deseja excluir "${deleteTarget?.nome}"?`}
                confirmLabel="Excluir"
                onConfirm={handleDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </AppLayout>
    );
}
