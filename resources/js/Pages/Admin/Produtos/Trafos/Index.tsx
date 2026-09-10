import React, { useState } from 'react';
import {
    Box,
    Button,
    Card,
    CardContent,
    Chip,
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
import AddRoundedIcon from '@mui/icons-material/AddRounded';
import EditRoundedIcon from '@mui/icons-material/EditRounded';
import DeleteRoundedIcon from '@mui/icons-material/DeleteRounded';
import SearchRoundedIcon from '@mui/icons-material/SearchRounded';
import PowerRoundedIcon from '@mui/icons-material/PowerRounded';
import CheckCircleRoundedIcon from '@mui/icons-material/CheckCircleRounded';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { TablePagination } from '@/Components/UI/TablePagination';
import { ConfirmDialog } from '@/Components/UI/ConfirmDialog';
import { PageProps, PaginatedData } from '@/types';

interface Trafo {
    id: number;
    nome: string;
    modelo?: string;
    sku?: string;
    potencia?: number;
    tensao?: string;
    preco_custo?: number;
    ativo: boolean;
    fornecedor?: { id: number; nome: string };
}

interface Props extends PageProps {
    trafos: PaginatedData<Trafo>;
    filters: { search?: string; fornecedor_id?: string; ativo?: string };
    fornecedores: { id: number; nome: string }[];
    stats: { total: number; ativos: number };
}

export default function TrafosIndex({ trafos, filters, fornecedores, stats }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [fornecedorId, setFornecedorId] = useState(filters.fornecedor_id ?? '');
    const [ativoFilter, setAtivoFilter] = useState(filters.ativo ?? '');
    const [deleteTarget, setDeleteTarget] = useState<Trafo | null>(null);

    function applyFilters(overrides: object = {}) {
        router.get(route('admin.produtos.trafos.index'), {
            search, fornecedor_id: fornecedorId, ativo: ativoFilter, ...overrides,
        }, { preserveState: true, replace: true });
    }

    function handleDelete() {
        if (!deleteTarget) return;
        router.delete(route('admin.produtos.trafos.destroy', deleteTarget.id), {
            onSuccess: () => setDeleteTarget(null),
        });
    }

    const fmtMoney = (v?: number) => v != null
        ? v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
        : '—';

    return (
        <AppLayout>
            <Head title="Transformadores" />

            <PageHeader
                title="Transformadores"
                breadcrumbs={[{ label: 'Produtos' }, { label: 'Transformadores' }]}
                action={
                    <Button component={Link} href={route('admin.produtos.trafos.create')} variant="contained" startIcon={<AddRoundedIcon />}>
                        Novo Trafo
                    </Button>
                }
            />

            <Grid container spacing={2} sx={{ mb: 3 }}>
                {[
                    { label: 'Total', value: stats.total, icon: <PowerRoundedIcon fontSize="small" />, color: '#8b5cf6' },
                    { label: 'Ativos', value: stats.ativos, icon: <CheckCircleRoundedIcon fontSize="small" />, color: '#22c55e' },
                ].map((s) => (
                    <Grid key={s.label} size={{ xs: 6, sm: 3 }}>
                        <Card>
                            <CardContent sx={{ p: '16px !important' }}>
                                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                                    <Box sx={{ width: 40, height: 40, borderRadius: 2, bgcolor: alpha(s.color, 0.12), display: 'flex', alignItems: 'center', justifyContent: 'center', color: s.color }}>
                                        {s.icon}
                                    </Box>
                                    <Box>
                                        <Typography variant="h5" fontWeight={700}>{s.value}</Typography>
                                        <Typography variant="caption" color="text.secondary">{s.label}</Typography>
                                    </Box>
                                </Box>
                            </CardContent>
                        </Card>
                    </Grid>
                ))}
            </Grid>

            <Card sx={{ mb: 3, p: 2 }}>
                <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap', alignItems: 'center' }}>
                    <TextField size="small" placeholder="Buscar nome, modelo, SKU..." value={search}
                        onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                        InputProps={{ startAdornment: <InputAdornment position="start"><SearchRoundedIcon fontSize="small" /></InputAdornment> }}
                        sx={{ minWidth: 260 }} />
                    <TextField select size="small" label="Fornecedor" value={fornecedorId}
                        onChange={(e) => { setFornecedorId(e.target.value); applyFilters({ fornecedor_id: e.target.value }); }} sx={{ minWidth: 180 }}>
                        <MenuItem value="">Todos</MenuItem>
                        {fornecedores.map((f) => <MenuItem key={f.id} value={String(f.id)}>{f.nome}</MenuItem>)}
                    </TextField>
                    <TextField select size="small" label="Status" value={ativoFilter}
                        onChange={(e) => { setAtivoFilter(e.target.value); applyFilters({ ativo: e.target.value }); }} sx={{ minWidth: 130 }}>
                        <MenuItem value="">Todos</MenuItem>
                        <MenuItem value="1">Ativos</MenuItem>
                        <MenuItem value="0">Inativos</MenuItem>
                    </TextField>
                    <Button variant="contained" size="small" onClick={() => applyFilters()}>Buscar</Button>
                    {(search || fornecedorId || ativoFilter) && (
                        <Button size="small" onClick={() => { setSearch(''); setFornecedorId(''); setAtivoFilter(''); applyFilters({ search: '', fornecedor_id: '', ativo: '' }); }}>Limpar</Button>
                    )}
                </Box>
            </Card>

            <Card>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell>Produto</TableCell>
                            <TableCell>Fornecedor</TableCell>
                            <TableCell>Potência</TableCell>
                            <TableCell>Tensão</TableCell>
                            <TableCell>Preço Custo</TableCell>
                            <TableCell>Status</TableCell>
                            <TableCell align="right">Ações</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {trafos.data.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={7} align="center" sx={{ py: 6 }}>
                                    <PowerRoundedIcon sx={{ fontSize: 48, color: 'text.disabled', mb: 1 }} />
                                    <Typography color="text.secondary">Nenhum transformador encontrado</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                        {trafos.data.map((t) => (
                            <TableRow key={t.id} hover>
                                <TableCell>
                                    <Typography variant="body2" fontWeight={600}>{t.nome}</Typography>
                                    {t.modelo && <Typography variant="caption" color="text.secondary">{t.modelo}</Typography>}
                                    {t.sku && <Typography variant="caption" color="text.disabled" sx={{ display: 'block' }}>SKU: {t.sku}</Typography>}
                                </TableCell>
                                <TableCell><Typography variant="body2">{t.fornecedor?.nome ?? '—'}</Typography></TableCell>
                                <TableCell><Typography variant="body2">{t.potencia != null ? `${t.potencia} kVA` : '—'}</Typography></TableCell>
                                <TableCell><Typography variant="body2">{t.tensao ?? '—'}</Typography></TableCell>
                                <TableCell><Typography variant="body2">{fmtMoney(t.preco_custo)}</Typography></TableCell>
                                <TableCell>
                                    <Chip label={t.ativo ? 'Ativo' : 'Inativo'} size="small" color={t.ativo ? 'success' : 'default'} variant={t.ativo ? 'filled' : 'outlined'} />
                                </TableCell>
                                <TableCell align="right">
                                    <Tooltip title="Editar">
                                        <IconButton size="small" component={Link} href={route('admin.produtos.trafos.edit', t.id)}>
                                            <EditRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                    <Tooltip title="Excluir">
                                        <IconButton size="small" color="error" onClick={() => setDeleteTarget(t)}>
                                            <DeleteRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
                <TablePagination {...trafos} label="transformadores" />
            </Card>

            <ConfirmDialog
                open={!!deleteTarget}
                title="Excluir Transformador"
                message={`Deseja excluir o transformador "${deleteTarget?.nome}"?`}
                confirmLabel="Excluir"
                onConfirm={handleDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </AppLayout>
    );
}
