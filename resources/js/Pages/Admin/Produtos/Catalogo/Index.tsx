import React, { useState } from 'react';
import {
    Box, Button, Chip, IconButton, InputAdornment, MenuItem,
    Select, Table, TableBody, TableCell, TableHead, TableRow,
    TextField, Tooltip, Typography,
} from '@mui/material';
import AddRoundedIcon from '@mui/icons-material/AddRounded';
import SearchRoundedIcon from '@mui/icons-material/SearchRounded';
import EditRoundedIcon from '@mui/icons-material/EditRounded';
import VisibilityRoundedIcon from '@mui/icons-material/VisibilityRounded';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { BoolChip } from '@/Components/UI/StatusChip';
import { PaginatedData } from '@/types';

interface Produto {
    id: number;
    nome: string;
    modelo?: string;
    sku?: string;
    potencia?: number;
    unidade_potencia?: string;
    preco_custo: number;
    ativo: boolean;
    ativo_fornecedor: boolean;
    categoria: { id: number; nome: string; slug: string };
    marca?: { nome: string };
    fornecedor?: { nome: string };
}

interface Categoria { id: number; nome: string; slug: string; }
interface Fornecedor { id: number; nome: string; }
interface Marca { id: number; nome: string; }

interface Props {
    produtos: PaginatedData<Produto>;
    categorias: Categoria[];
    marcas: Marca[];
    fornecedores: Fornecedor[];
    filters: { search?: string; categoria_id?: string; fornecedor_id?: string; ativo?: string };
}

export default function CatalogoIndex({ produtos, categorias, fornecedores, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    const applyFilter = (extra: object = {}) => {
        router.get(route('admin.produtos.catalogo.index'), {
            search,
            ...filters,
            ...extra,
        }, { preserveState: true, replace: true });
    };

    const formatPreco = (v: number) =>
        new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v);

    return (
        <AppLayout title="Catálogo de Produtos">
            <Head title="Catálogo de Produtos" />

            <PageHeader
                title="Catálogo de Produtos"
                breadcrumbs={[{ label: 'Admin' }, { label: 'Produtos' }, { label: 'Catálogo' }]}
                action={
                    <Button
                        variant="contained"
                        startIcon={<AddRoundedIcon />}
                        component={Link}
                        href={route('admin.produtos.catalogo.create')}
                    >
                        Novo Produto
                    </Button>
                }
            />

            {/* Filters */}
            <Box sx={{ display: 'flex', gap: 2, mb: 3, flexWrap: 'wrap' }}>
                <TextField
                    placeholder="Buscar por nome, modelo ou SKU..."
                    size="small"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && applyFilter()}
                    InputProps={{ startAdornment: <InputAdornment position="start"><SearchRoundedIcon fontSize="small" /></InputAdornment> }}
                    sx={{ minWidth: 300 }}
                />
                <Select
                    size="small"
                    displayEmpty
                    value={filters.categoria_id ?? ''}
                    onChange={(e) => applyFilter({ categoria_id: e.target.value })}
                    sx={{ minWidth: 180 }}
                >
                    <MenuItem value="">Todas as categorias</MenuItem>
                    {categorias.map((c) => <MenuItem key={c.id} value={c.id}>{c.nome}</MenuItem>)}
                </Select>
                <Select
                    size="small"
                    displayEmpty
                    value={filters.fornecedor_id ?? ''}
                    onChange={(e) => applyFilter({ fornecedor_id: e.target.value })}
                    sx={{ minWidth: 160 }}
                >
                    <MenuItem value="">Todos os fornecedores</MenuItem>
                    {fornecedores.map((f) => <MenuItem key={f.id} value={f.id}>{f.nome}</MenuItem>)}
                </Select>
                <Select
                    size="small"
                    displayEmpty
                    value={filters.ativo ?? ''}
                    onChange={(e) => applyFilter({ ativo: e.target.value })}
                    sx={{ minWidth: 120 }}
                >
                    <MenuItem value="">Todos</MenuItem>
                    <MenuItem value="1">Ativos</MenuItem>
                    <MenuItem value="0">Inativos</MenuItem>
                </Select>
            </Box>

            <Box sx={{ bgcolor: 'background.paper', borderRadius: 2, border: '1px solid #E2E8F0', overflow: 'hidden' }}>
                <Table size="small">
                    <TableHead sx={{ bgcolor: '#F8FAFC' }}>
                        <TableRow>
                            <TableCell sx={{ fontWeight: 600 }}>Produto</TableCell>
                            <TableCell sx={{ fontWeight: 600 }}>Categoria</TableCell>
                            <TableCell sx={{ fontWeight: 600 }}>Marca / Fornecedor</TableCell>
                            <TableCell sx={{ fontWeight: 600 }}>Potência</TableCell>
                            <TableCell sx={{ fontWeight: 600 }}>Preço Custo</TableCell>
                            <TableCell sx={{ fontWeight: 600 }}>Status</TableCell>
                            <TableCell sx={{ width: 80 }} />
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {produtos.data.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={7} align="center" sx={{ py: 4, color: 'text.secondary' }}>
                                    Nenhum produto encontrado
                                </TableCell>
                            </TableRow>
                        )}
                        {produtos.data.map((p) => (
                            <TableRow key={p.id} hover>
                                <TableCell>
                                    <Typography variant="body2" fontWeight={500}>{p.nome}</Typography>
                                    {p.modelo && <Typography variant="caption" color="text.secondary">{p.modelo}</Typography>}
                                    {p.sku && (
                                        <Chip label={p.sku} size="small" sx={{ ml: 1, fontSize: '0.7rem', height: 18 }} />
                                    )}
                                </TableCell>
                                <TableCell>
                                    <Chip
                                        label={p.categoria.nome}
                                        size="small"
                                        variant="outlined"
                                        sx={{ fontSize: '0.75rem' }}
                                    />
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2">{p.marca?.nome ?? '—'}</Typography>
                                    {p.fornecedor && (
                                        <Typography variant="caption" color="text.secondary">{p.fornecedor.nome}</Typography>
                                    )}
                                </TableCell>
                                <TableCell>
                                    {p.potencia ? (
                                        <Typography variant="body2" fontWeight={500}>
                                            {p.potencia} {p.unidade_potencia ?? ''}
                                        </Typography>
                                    ) : '—'}
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2" fontWeight={500}>
                                        {formatPreco(p.preco_custo)}
                                    </Typography>
                                </TableCell>
                                <TableCell>
                                    <BoolChip value={p.ativo && p.ativo_fornecedor} />
                                </TableCell>
                                <TableCell align="right">
                                    <Tooltip title="Ver detalhes">
                                        <IconButton
                                            size="small"
                                            component={Link}
                                            href={route('admin.produtos.catalogo.show', p.id)}
                                        >
                                            <VisibilityRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                    <Tooltip title="Editar">
                                        <IconButton
                                            size="small"
                                            component={Link}
                                            href={route('admin.produtos.catalogo.edit', p.id)}
                                        >
                                            <EditRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>

                {/* Pagination info */}
                {produtos.total > 0 && (
                    <Box sx={{ px: 2, py: 1.5, borderTop: '1px solid #E2E8F0', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <Typography variant="caption" color="text.secondary">
                            {produtos.from}–{produtos.to} de {produtos.total} produtos
                        </Typography>
                        <Box sx={{ display: 'flex', gap: 1 }}>
                            {produtos.links.filter(l => l.label !== '&laquo; Previous' && l.label !== 'Next &raquo;').map((link, i) => (
                                <Button
                                    key={i}
                                    size="small"
                                    variant={link.active ? 'contained' : 'outlined'}
                                    disabled={!link.url}
                                    onClick={() => link.url && router.visit(link.url)}
                                    sx={{ minWidth: 32, px: 1 }}
                                >
                                    {link.label}
                                </Button>
                            ))}
                        </Box>
                    </Box>
                )}
            </Box>
        </AppLayout>
    );
}
