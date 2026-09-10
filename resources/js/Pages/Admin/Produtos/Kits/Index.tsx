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
import VisibilityRoundedIcon from '@mui/icons-material/VisibilityRounded';
import SearchRoundedIcon from '@mui/icons-material/SearchRounded';
import SolarPowerRoundedIcon from '@mui/icons-material/SolarPowerRounded';
import ElectricBoltRoundedIcon from '@mui/icons-material/ElectricBoltRounded';
import CheckCircleRoundedIcon from '@mui/icons-material/CheckCircleRounded';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { TablePagination } from '@/Components/UI/TablePagination';
import { ConfirmDialog } from '@/Components/UI/ConfirmDialog';
import { PageProps, PaginatedData } from '@/types';

interface Kit {
    id: number;
    nome: string;
    modelo?: string;
    sku?: string;
    potencia_kwp: number;
    tensao?: string;
    inclui_trafo: boolean;
    preco_custo?: number;
    margem_padrao?: number;
    ativo: boolean;
    ativo_fornecedor: boolean;
    fornecedor?: { id: number; nome: string };
    estrutura?: { id: number; nome: string };
}

interface Fornecedor { id: number; nome: string }
interface Estrutura { id: number; nome: string }
interface Stats { total: number; ativos: number; potencia_media: number }

interface Props extends PageProps {
    kits: PaginatedData<Kit>;
    filters: { search?: string; fornecedor_id?: string; estrutura_id?: string; ativo?: string };
    fornecedores: Fornecedor[];
    estruturas: Estrutura[];
    stats: Stats;
}

export default function KitsIndex({ kits, filters, fornecedores, estruturas, stats }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [fornecedorId, setFornecedorId] = useState(filters.fornecedor_id ?? '');
    const [estruturaId, setEstruturaId] = useState(filters.estrutura_id ?? '');
    const [ativoFilter, setAtivoFilter] = useState(filters.ativo ?? '');
    const [deleteTarget, setDeleteTarget] = useState<Kit | null>(null);

    function applyFilters(overrides: object = {}) {
        router.get(route('admin.produtos.kits.index'), {
            search, fornecedor_id: fornecedorId, estrutura_id: estruturaId,
            ativo: ativoFilter, ...overrides,
        }, { preserveState: true, replace: true });
    }

    function handleDelete() {
        if (!deleteTarget) return;
        router.delete(route('admin.produtos.kits.destroy', deleteTarget.id), {
            onSuccess: () => setDeleteTarget(null),
        });
    }

    const fmtMoney = (v?: number) => v != null
        ? v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
        : '—';

    return (
        <AppLayout>
            <Head title="Kits Solares" />

            <PageHeader
                title="Kits Solares"
                breadcrumbs={[{ label: 'Produtos' }, { label: 'Kits Solares' }]}
                action={
                    <Button
                        component={Link}
                        href={route('admin.produtos.kits.create')}
                        variant="contained"
                        startIcon={<AddRoundedIcon />}
                    >
                        Novo Kit
                    </Button>
                }
            />

            {/* Stats */}
            <Grid container spacing={2} sx={{ mb: 3 }}>
                {[
                    { label: 'Total de Kits', value: stats.total, icon: <SolarPowerRoundedIcon fontSize="small" />, color: '#6366f1' },
                    { label: 'Kits Ativos', value: stats.ativos, icon: <CheckCircleRoundedIcon fontSize="small" />, color: '#22c55e' },
                    { label: 'Potência Média', value: `${stats.potencia_media} kWp`, icon: <ElectricBoltRoundedIcon fontSize="small" />, color: '#f59e0b' },
                ].map((s) => (
                    <Grid key={s.label} size={{ xs: 6, sm: 4 }}>
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

            {/* Filtros */}
            <Card sx={{ mb: 3, p: 2 }}>
                <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap', alignItems: 'center' }}>
                    <TextField
                        size="small"
                        placeholder="Buscar nome, modelo, SKU..."
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
                        select size="small" label="Fornecedor" value={fornecedorId}
                        onChange={(e) => { setFornecedorId(e.target.value); applyFilters({ fornecedor_id: e.target.value }); }}
                        sx={{ minWidth: 180 }}
                    >
                        <MenuItem value="">Todos</MenuItem>
                        {fornecedores.map((f) => (
                            <MenuItem key={f.id} value={String(f.id)}>{f.nome}</MenuItem>
                        ))}
                    </TextField>
                    <TextField
                        select size="small" label="Estrutura" value={estruturaId}
                        onChange={(e) => { setEstruturaId(e.target.value); applyFilters({ estrutura_id: e.target.value }); }}
                        sx={{ minWidth: 160 }}
                    >
                        <MenuItem value="">Todas</MenuItem>
                        {estruturas.map((e) => (
                            <MenuItem key={e.id} value={String(e.id)}>{e.nome}</MenuItem>
                        ))}
                    </TextField>
                    <TextField
                        select size="small" label="Status" value={ativoFilter}
                        onChange={(e) => { setAtivoFilter(e.target.value); applyFilters({ ativo: e.target.value }); }}
                        sx={{ minWidth: 130 }}
                    >
                        <MenuItem value="">Todos</MenuItem>
                        <MenuItem value="1">Ativos</MenuItem>
                        <MenuItem value="0">Inativos</MenuItem>
                    </TextField>
                    <Button variant="contained" size="small" onClick={() => applyFilters()}>Buscar</Button>
                    {(search || fornecedorId || estruturaId || ativoFilter) && (
                        <Button size="small" onClick={() => {
                            setSearch(''); setFornecedorId(''); setEstruturaId(''); setAtivoFilter('');
                            applyFilters({ search: '', fornecedor_id: '', estrutura_id: '', ativo: '' });
                        }}>Limpar</Button>
                    )}
                </Box>
            </Card>

            {/* Tabela */}
            <Card>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell>Kit</TableCell>
                            <TableCell>Potência</TableCell>
                            <TableCell>Fornecedor</TableCell>
                            <TableCell>Estrutura</TableCell>
                            <TableCell>Preço Custo</TableCell>
                            <TableCell>Margem</TableCell>
                            <TableCell>Status</TableCell>
                            <TableCell align="right">Ações</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {kits.data.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={8} align="center" sx={{ py: 6 }}>
                                    <SolarPowerRoundedIcon sx={{ fontSize: 48, color: 'text.disabled', mb: 1 }} />
                                    <Typography color="text.secondary">Nenhum kit encontrado</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                        {kits.data.map((k) => (
                            <TableRow key={k.id} hover>
                                <TableCell>
                                    <Typography variant="body2" fontWeight={600}>{k.nome}</Typography>
                                    {k.modelo && (
                                        <Typography variant="caption" color="text.secondary">{k.modelo}</Typography>
                                    )}
                                    {k.sku && (
                                        <Typography variant="caption" color="text.disabled" sx={{ display: 'block' }}>
                                            SKU: {k.sku}
                                        </Typography>
                                    )}
                                </TableCell>
                                <TableCell>
                                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.5 }}>
                                        <ElectricBoltRoundedIcon sx={{ fontSize: 14, color: '#f59e0b' }} />
                                        <Typography variant="body2" fontWeight={600}>
                                            {k.potencia_kwp} kWp
                                        </Typography>
                                    </Box>
                                    {k.inclui_trafo && (
                                        <Chip label="c/ Trafo" size="small" color="info" variant="outlined" sx={{ mt: 0.3, height: 18, fontSize: '0.65rem' }} />
                                    )}
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2">{k.fornecedor?.nome ?? '—'}</Typography>
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2">{k.estrutura?.nome ?? '—'}</Typography>
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2">{fmtMoney(k.preco_custo)}</Typography>
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2">
                                        {k.margem_padrao != null ? `${k.margem_padrao}%` : '—'}
                                    </Typography>
                                </TableCell>
                                <TableCell>
                                    <Chip
                                        label={k.ativo ? 'Ativo' : 'Inativo'}
                                        size="small"
                                        color={k.ativo ? 'success' : 'default'}
                                        variant={k.ativo ? 'filled' : 'outlined'}
                                    />
                                    {!k.ativo_fornecedor && (
                                        <Chip label="Forn. inativo" size="small" color="warning" variant="outlined" sx={{ ml: 0.5 }} />
                                    )}
                                </TableCell>
                                <TableCell align="right">
                                    <Tooltip title="Ver detalhes">
                                        <IconButton size="small" component={Link} href={route('admin.produtos.kits.show', k.id)}>
                                            <VisibilityRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                    <Tooltip title="Editar">
                                        <IconButton size="small" component={Link} href={route('admin.produtos.kits.edit', k.id)}>
                                            <EditRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                    <Tooltip title="Excluir">
                                        <IconButton size="small" color="error" onClick={() => setDeleteTarget(k)}>
                                            <DeleteRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
                <TablePagination {...kits} label="kits" />
            </Card>

            <ConfirmDialog
                open={!!deleteTarget}
                title="Excluir Kit"
                message={`Deseja excluir o kit "${deleteTarget?.nome}"? Esta ação não pode ser desfeita.`}
                confirmLabel="Excluir"
                onConfirm={handleDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </AppLayout>
    );
}
