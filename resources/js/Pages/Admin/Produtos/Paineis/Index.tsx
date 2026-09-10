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
import SolarPowerRoundedIcon from '@mui/icons-material/SolarPowerRounded';
import CheckCircleRoundedIcon from '@mui/icons-material/CheckCircleRounded';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { TablePagination } from '@/Components/UI/TablePagination';
import { ConfirmDialog } from '@/Components/UI/ConfirmDialog';
import { PageProps, PaginatedData } from '@/types';

interface Painel {
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

interface Props extends PageProps {
    paineis: PaginatedData<Painel>;
    filters: { search?: string; marca_id?: string; fornecedor_id?: string; ativo?: string };
    marcas: { id: number; nome: string }[];
    fornecedores: { id: number; nome: string }[];
    stats: { total: number; ativos: number };
}

export default function PaineisIndex({ paineis, filters, marcas, fornecedores, stats }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [marcaId, setMarcaId] = useState(filters.marca_id ?? '');
    const [fornecedorId, setFornecedorId] = useState(filters.fornecedor_id ?? '');
    const [ativoFilter, setAtivoFilter] = useState(filters.ativo ?? '');
    const [deleteTarget, setDeleteTarget] = useState<Painel | null>(null);

    function applyFilters(overrides: object = {}) {
        router.get(route('admin.produtos.paineis.index'), {
            search, marca_id: marcaId, fornecedor_id: fornecedorId, ativo: ativoFilter, ...overrides,
        }, { preserveState: true, replace: true });
    }

    function handleDelete() {
        if (!deleteTarget) return;
        router.delete(route('admin.produtos.paineis.destroy', deleteTarget.id), {
            onSuccess: () => setDeleteTarget(null),
        });
    }

    const fmtMoney = (v?: number) => v != null
        ? v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
        : '—';

    return (
        <AppLayout>
            <Head title="Painéis Solares" />

            <PageHeader
                title="Painéis Solares"
                breadcrumbs={[{ label: 'Produtos' }, { label: 'Painéis Solares' }]}
                action={
                    <Button component={Link} href={route('admin.produtos.paineis.create')} variant="contained" startIcon={<AddRoundedIcon />}>
                        Novo Painel
                    </Button>
                }
            />

            <Grid container spacing={2} sx={{ mb: 3 }}>
                {[
                    { label: 'Total de Painéis', value: stats.total, icon: <SolarPowerRoundedIcon fontSize="small" />, color: '#f59e0b' },
                    { label: 'Painéis Ativos', value: stats.ativos, icon: <CheckCircleRoundedIcon fontSize="small" />, color: '#22c55e' },
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
                    <TextField select size="small" label="Marca" value={marcaId}
                        onChange={(e) => { setMarcaId(e.target.value); applyFilters({ marca_id: e.target.value }); }} sx={{ minWidth: 160 }}>
                        <MenuItem value="">Todas</MenuItem>
                        {marcas.map((m) => <MenuItem key={m.id} value={String(m.id)}>{m.nome}</MenuItem>)}
                    </TextField>
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
                        {paineis.data.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={8} align="center" sx={{ py: 6 }}>
                                    <SolarPowerRoundedIcon sx={{ fontSize: 48, color: 'text.disabled', mb: 1 }} />
                                    <Typography color="text.secondary">Nenhum painel encontrado</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                        {paineis.data.map((p) => (
                            <TableRow key={p.id} hover>
                                <TableCell>
                                    <Typography variant="body2" fontWeight={600}>{p.nome}</Typography>
                                    {p.modelo && <Typography variant="caption" color="text.secondary">{p.modelo}</Typography>}
                                    {p.sku && <Typography variant="caption" color="text.disabled" sx={{ display: 'block' }}>SKU: {p.sku}</Typography>}
                                </TableCell>
                                <TableCell><Typography variant="body2">{p.marca?.nome ?? '—'}</Typography></TableCell>
                                <TableCell>
                                    <Typography variant="body2">
                                        {p.potencia != null ? `${p.potencia} ${p.unidade_potencia ?? 'Wp'}` : '—'}
                                    </Typography>
                                </TableCell>
                                <TableCell><Typography variant="body2">{p.tensao ?? '—'}</Typography></TableCell>
                                <TableCell><Typography variant="body2">{fmtMoney(p.preco_custo)}</Typography></TableCell>
                                <TableCell><Typography variant="body2">{p.garantia != null ? `${p.garantia} anos` : '—'}</Typography></TableCell>
                                <TableCell>
                                    <Chip label={p.ativo ? 'Ativo' : 'Inativo'} size="small" color={p.ativo ? 'success' : 'default'} variant={p.ativo ? 'filled' : 'outlined'} />
                                </TableCell>
                                <TableCell align="right">
                                    <Tooltip title="Editar">
                                        <IconButton size="small" component={Link} href={route('admin.produtos.paineis.edit', p.id)}>
                                            <EditRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                    <Tooltip title="Excluir">
                                        <IconButton size="small" color="error" onClick={() => setDeleteTarget(p)}>
                                            <DeleteRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
                <TablePagination {...paineis} label="painéis" />
            </Card>

            <ConfirmDialog
                open={!!deleteTarget}
                title="Excluir Painel"
                message={`Deseja excluir o painel "${deleteTarget?.nome}"?`}
                confirmLabel="Excluir"
                onConfirm={handleDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </AppLayout>
    );
}
