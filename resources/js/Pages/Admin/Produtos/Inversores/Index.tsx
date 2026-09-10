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
import MemoryRoundedIcon from '@mui/icons-material/MemoryRounded';
import CheckCircleRoundedIcon from '@mui/icons-material/CheckCircleRounded';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { TablePagination } from '@/Components/UI/TablePagination';
import { ConfirmDialog } from '@/Components/UI/ConfirmDialog';
import { PageProps, PaginatedData } from '@/types';

interface Inversor {
    id: number;
    nome: string;
    modelo?: string;
    sku?: string;
    potencia?: number;
    unidade_potencia?: string;
    tensao?: string;
    preco_custo?: number;
    garantia?: number;
    ativo: boolean;
    marca?: { id: number; nome: string };
    fornecedor?: { id: number; nome: string };
}

interface Marca { id: number; nome: string }
interface Fornecedor { id: number; nome: string }
interface Stats { total: number; ativos: number }

interface Props extends PageProps {
    inversores: PaginatedData<Inversor>;
    filters: { search?: string; marca_id?: string; fornecedor_id?: string; ativo?: string };
    marcas: Marca[];
    fornecedores: Fornecedor[];
    stats: Stats;
}

export default function InversoresIndex({ inversores, filters, marcas, fornecedores, stats }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [marcaId, setMarcaId] = useState(filters.marca_id ?? '');
    const [fornecedorId, setFornecedorId] = useState(filters.fornecedor_id ?? '');
    const [ativoFilter, setAtivoFilter] = useState(filters.ativo ?? '');
    const [deleteTarget, setDeleteTarget] = useState<Inversor | null>(null);

    function applyFilters(overrides: object = {}) {
        router.get(route('admin.produtos.inversores.index'), {
            search, marca_id: marcaId, fornecedor_id: fornecedorId, ativo: ativoFilter, ...overrides,
        }, { preserveState: true, replace: true });
    }

    function handleDelete() {
        if (!deleteTarget) return;
        router.delete(route('admin.produtos.inversores.destroy', deleteTarget.id), {
            onSuccess: () => setDeleteTarget(null),
        });
    }

    const fmtMoney = (v?: number) => v != null
        ? v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
        : '—';

    return (
        <AppLayout>
            <Head title="Inversores" />

            <PageHeader
                title="Inversores"
                breadcrumbs={[{ label: 'Produtos' }, { label: 'Inversores' }]}
                action={
                    <Button component={Link} href={route('admin.produtos.inversores.create')} variant="contained" startIcon={<AddRoundedIcon />}>
                        Novo Inversor
                    </Button>
                }
            />

            <Grid container spacing={2} sx={{ mb: 3 }}>
                {[
                    { label: 'Total', value: stats.total, icon: <MemoryRoundedIcon fontSize="small" />, color: '#6366f1' },
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
                    <TextField
                        size="small" placeholder="Buscar nome, modelo, SKU..." value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                        InputProps={{ startAdornment: <InputAdornment position="start"><SearchRoundedIcon fontSize="small" /></InputAdornment> }}
                        sx={{ minWidth: 260 }}
                    />
                    <TextField select size="small" label="Marca" value={marcaId}
                        onChange={(e) => { setMarcaId(e.target.value); applyFilters({ marca_id: e.target.value }); }}
                        sx={{ minWidth: 160 }}>
                        <MenuItem value="">Todas</MenuItem>
                        {marcas.map((m) => <MenuItem key={m.id} value={String(m.id)}>{m.nome}</MenuItem>)}
                    </TextField>
                    <TextField select size="small" label="Fornecedor" value={fornecedorId}
                        onChange={(e) => { setFornecedorId(e.target.value); applyFilters({ fornecedor_id: e.target.value }); }}
                        sx={{ minWidth: 180 }}>
                        <MenuItem value="">Todos</MenuItem>
                        {fornecedores.map((f) => <MenuItem key={f.id} value={String(f.id)}>{f.nome}</MenuItem>)}
                    </TextField>
                    <TextField select size="small" label="Status" value={ativoFilter}
                        onChange={(e) => { setAtivoFilter(e.target.value); applyFilters({ ativo: e.target.value }); }}
                        sx={{ minWidth: 130 }}>
                        <MenuItem value="">Todos</MenuItem>
                        <MenuItem value="1">Ativos</MenuItem>
                        <MenuItem value="0">Inativos</MenuItem>
                    </TextField>
                    <Button variant="contained" size="small" onClick={() => applyFilters()}>Buscar</Button>
                    {(search || marcaId || fornecedorId || ativoFilter) && (
                        <Button size="small" onClick={() => { setSearch(''); setMarcaId(''); setFornecedorId(''); setAtivoFilter(''); applyFilters({ search: '', marca_id: '', fornecedor_id: '', ativo: '' }); }}>Limpar</Button>
                    )}
                </Box>
            </Card>

            <Card>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell>Produto</TableCell>
                            <TableCell>Marca</TableCell>
                            <TableCell>Potência</TableCell>
                            <TableCell>Tensão</TableCell>
                            <TableCell>Preço Custo</TableCell>
                            <TableCell>Garantia</TableCell>
                            <TableCell>Status</TableCell>
                            <TableCell align="right">Ações</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {inversores.data.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={8} align="center" sx={{ py: 6 }}>
                                    <MemoryRoundedIcon sx={{ fontSize: 48, color: 'text.disabled', mb: 1 }} />
                                    <Typography color="text.secondary">Nenhum inversor encontrado</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                        {inversores.data.map((inv) => (
                            <TableRow key={inv.id} hover>
                                <TableCell>
                                    <Typography variant="body2" fontWeight={600}>{inv.nome}</Typography>
                                    {inv.modelo && <Typography variant="caption" color="text.secondary">{inv.modelo}</Typography>}
                                    {inv.sku && <Typography variant="caption" color="text.disabled" sx={{ display: 'block' }}>SKU: {inv.sku}</Typography>}
                                </TableCell>
                                <TableCell><Typography variant="body2">{inv.marca?.nome ?? '—'}</Typography></TableCell>
                                <TableCell>
                                    <Typography variant="body2">
                                        {inv.potencia != null ? `${inv.potencia} ${inv.unidade_potencia ?? 'kWp'}` : '—'}
                                    </Typography>
                                </TableCell>
                                <TableCell><Typography variant="body2">{inv.tensao ?? '—'}</Typography></TableCell>
                                <TableCell><Typography variant="body2">{fmtMoney(inv.preco_custo)}</Typography></TableCell>
                                <TableCell>
                                    <Typography variant="body2">{inv.garantia != null ? `${inv.garantia} anos` : '—'}</Typography>
                                </TableCell>
                                <TableCell>
                                    <Chip label={inv.ativo ? 'Ativo' : 'Inativo'} size="small" color={inv.ativo ? 'success' : 'default'} variant={inv.ativo ? 'filled' : 'outlined'} />
                                </TableCell>
                                <TableCell align="right">
                                    <Tooltip title="Editar">
                                        <IconButton size="small" component={Link} href={route('admin.produtos.inversores.edit', inv.id)}>
                                            <EditRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                    <Tooltip title="Excluir">
                                        <IconButton size="small" color="error" onClick={() => setDeleteTarget(inv)}>
                                            <DeleteRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
                <TablePagination {...inversores} label="inversores" />
            </Card>

            <ConfirmDialog
                open={!!deleteTarget}
                title="Excluir Inversor"
                message={`Deseja excluir o inversor "${deleteTarget?.nome}"?`}
                confirmLabel="Excluir"
                onConfirm={handleDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </AppLayout>
    );
}
