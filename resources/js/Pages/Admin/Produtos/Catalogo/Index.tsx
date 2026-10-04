import React, { useState } from 'react';
import {
    Box, Button, Card, Chip, IconButton, InputAdornment, MenuItem,
    Select, Tab, Table, TableBody, TableCell, TableHead, TableRow, Tabs,
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
import { TablePagination } from '@/Components/UI/TablePagination';
import { CategoriasAba, Categoria } from '@/Components/Produtos/CategoriasAba';
import { MarcasAba, Marca } from '@/Components/Produtos/MarcasAba';
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

interface Fornecedor { id: number; nome: string; }

type Aba = 'produtos' | 'categorias' | 'marcas';

interface Filters { search?: string; categoria?: string; fornecedor_id?: string; ativo?: string }

interface Props {
    aba: Aba;
    produtos: PaginatedData<Produto>;
    totalProdutos: number;
    categorias: Categoria[];
    marcas: Marca[];
    fornecedores: Fornecedor[];
    filters: Filters;
}

export default function CatalogoIndex({ aba, produtos, totalProdutos, categorias, marcas, fornecedores, filters }: Props) {
    const trocarAba = (nova: Aba) => {
        router.get(route('admin.produtos.catalogo.index'), nova === 'produtos' ? {} : { aba: nova }, { preserveState: true, replace: true });
    };

    return (
        <AppLayout title="Catálogo de Produtos">
            <Head title="Catálogo de Produtos" />

            <PageHeader
                title="Catálogo de Produtos"
                subtitle="Painéis, inversores, transformadores e demais itens avulsos — com suas categorias e marcas"
                breadcrumbs={[{ label: 'Admin' }, { label: 'Produtos' }, { label: 'Catálogo' }]}
            />

            <Box sx={{ borderBottom: 1, borderColor: 'divider', mb: 3 }}>
                <Tabs value={aba} onChange={(_, nova) => trocarAba(nova)} variant="scrollable" allowScrollButtonsMobile>
                    <Tab value="produtos" label={`Produtos (${totalProdutos})`} />
                    <Tab value="categorias" label={`Categorias (${categorias.length})`} />
                    <Tab value="marcas" label={`Marcas (${marcas.length})`} />
                </Tabs>
            </Box>

            {aba === 'produtos' && (
                <ProdutosAba
                    produtos={produtos}
                    totalProdutos={totalProdutos}
                    categorias={categorias}
                    fornecedores={fornecedores}
                    filters={filters}
                />
            )}
            {aba === 'categorias' && <CategoriasAba categorias={categorias} />}
            {aba === 'marcas' && <MarcasAba marcas={marcas} />}
        </AppLayout>
    );
}

interface ProdutosAbaProps {
    produtos: PaginatedData<Produto>;
    totalProdutos: number;
    categorias: Categoria[];
    fornecedores: Fornecedor[];
    filters: Filters;
}

function ProdutosAba({ produtos, totalProdutos, categorias, fornecedores, filters }: ProdutosAbaProps) {
    const [search, setSearch] = useState(filters.search ?? '');

    const applyFilter = (extra: Filters = {}) => {
        // Filtros vazios ficam fora da URL (ex.: sem "&search=").
        const params = Object.fromEntries(
            Object.entries({ ...filters, search, ...extra }).filter(([, v]) => v !== '' && v != null),
        );
        router.get(route('admin.produtos.catalogo.index'), params, { preserveState: true, replace: true });
    };

    const formatPreco = (v: number) =>
        new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v);

    // Atalhos por categoria (substituem as antigas páginas Painéis / Inversores / Transformadores).
    const atalhos = categorias.filter((c) => c.ativo);
    const categoriaAtual = categorias.find((c) => c.slug === filters.categoria);

    return (
        <>
            <Box sx={{ display: 'flex', gap: 1, mb: 2, flexWrap: 'wrap' }}>
                <Chip
                    label={`Todos (${totalProdutos})`}
                    color={!filters.categoria ? 'primary' : 'default'}
                    variant={!filters.categoria ? 'filled' : 'outlined'}
                    onClick={() => applyFilter({ categoria: '' })}
                />
                {atalhos.map((c) => (
                    <Chip
                        key={c.id}
                        label={`${c.nome} (${c.produtos_count})`}
                        color={filters.categoria === c.slug ? 'primary' : 'default'}
                        variant={filters.categoria === c.slug ? 'filled' : 'outlined'}
                        onClick={() => applyFilter({ categoria: c.slug })}
                    />
                ))}
            </Box>

            <Box sx={{ display: 'flex', gap: 2, mb: 3, flexWrap: 'wrap', alignItems: 'center' }}>
                <TextField
                    placeholder="Buscar por nome, modelo ou SKU..."
                    size="small"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && applyFilter()}
                    InputProps={{ startAdornment: <InputAdornment position="start"><SearchRoundedIcon fontSize="small" /></InputAdornment> }}
                    sx={{ minWidth: 280, flex: { xs: 1, sm: 'none' } }}
                />
                <Select
                    size="small"
                    displayEmpty
                    value={filters.fornecedor_id ?? ''}
                    onChange={(e) => applyFilter({ fornecedor_id: e.target.value })}
                    sx={{ minWidth: 180 }}
                >
                    <MenuItem value="">Todos os fornecedores</MenuItem>
                    {fornecedores.map((f) => <MenuItem key={f.id} value={String(f.id)}>{f.nome}</MenuItem>)}
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
                <Box sx={{ flex: 1 }} />
                <Button
                    variant="contained"
                    startIcon={<AddRoundedIcon />}
                    component={Link}
                    href={route('admin.produtos.catalogo.create', categoriaAtual ? { categoria: categoriaAtual.slug } : {})}
                >
                    {categoriaAtual ? `Novo em ${categoriaAtual.nome}` : 'Novo Produto'}
                </Button>
            </Box>

            <Card>
                <Table size="small">
                    <TableHead>
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
                                    <Chip label={p.categoria.nome} size="small" variant="outlined" sx={{ fontSize: '0.75rem' }} />
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
                                    <Typography variant="body2" fontWeight={500}>{formatPreco(p.preco_custo)}</Typography>
                                </TableCell>
                                <TableCell>
                                    <BoolChip value={p.ativo && p.ativo_fornecedor} />
                                </TableCell>
                                <TableCell align="right" sx={{ whiteSpace: 'nowrap' }}>
                                    <Tooltip title="Ver detalhes">
                                        <IconButton size="small" component={Link} href={route('admin.produtos.catalogo.show', p.id)}>
                                            <VisibilityRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                    <Tooltip title="Editar">
                                        <IconButton size="small" component={Link} href={route('admin.produtos.catalogo.edit', p.id)}>
                                            <EditRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
                <TablePagination {...produtos} label="produtos" />
            </Card>
        </>
    );
}
