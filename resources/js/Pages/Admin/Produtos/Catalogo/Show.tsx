import React from 'react';
import {
    Box, Button, Card, CardContent, Chip, Divider,
    Grid, Table, TableBody, TableCell, TableHead, TableRow, Typography,
} from '@mui/material';
import EditRoundedIcon from '@mui/icons-material/EditRounded';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { BoolChip } from '@/Components/UI/StatusChip';

interface Kit { id: number; nome: string; potencia_kwp: number; fornecedor: { nome: string }; }
interface Produto {
    id: number; nome: string; modelo?: string; sku?: string; descricao?: string;
    potencia?: number; unidade_potencia?: string; tensao?: number; unidade: string;
    preco_custo: number; garantia?: string; imagem_url?: string; ficha_tecnica_url?: string;
    atributos?: Record<string, unknown>; ativo: boolean; ativo_fornecedor: boolean;
    categoria: { nome: string; slug: string };
    marca?: { nome: string };
    fornecedor?: { nome: string };
    kits: Kit[];
}

export default function CatalogoShow({ produto }: { produto: Produto }) {
    const formatPreco = (v: number) =>
        new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v);

    const atributos = produto.atributos ? Object.entries(produto.atributos) : [];

    return (
        <AppLayout title={produto.nome}>
            <Head title={produto.nome} />

            <PageHeader
                title={produto.nome}
                breadcrumbs={[
                    { label: 'Admin' },
                    { label: 'Catálogo', href: route('admin.produtos.catalogo.index') },
                    { label: produto.nome },
                ]}
                action={
                    <Button
                        variant="contained"
                        startIcon={<EditRoundedIcon />}
                        component={Link}
                        href={route('admin.produtos.catalogo.edit', produto.id)}
                    >
                        Editar
                    </Button>
                }
            />

            <Grid container spacing={3}>
                <Grid size={{ xs: 12, md: 8 }}>
                    <Card variant="outlined" sx={{ borderRadius: 2 }}>
                        <CardContent sx={{ p: 3 }}>
                            <Box sx={{ display: 'flex', gap: 2, mb: 2, flexWrap: 'wrap' }}>
                                <Chip label={produto.categoria.nome} color="primary" size="small" />
                                <BoolChip value={produto.ativo && produto.ativo_fornecedor} />
                            </Box>

                            <Typography variant="h5" fontWeight={700}>{produto.nome}</Typography>
                            {produto.modelo && <Typography color="text.secondary">{produto.modelo}</Typography>}
                            {produto.sku && <Chip label={`SKU: ${produto.sku}`} size="small" sx={{ mt: 1 }} />}

                            {produto.descricao && (
                                <>
                                    <Divider sx={{ my: 2 }} />
                                    <Typography variant="body2" color="text.secondary">{produto.descricao}</Typography>
                                </>
                            )}

                            <Divider sx={{ my: 2 }} />
                            <Grid container spacing={3}>
                                {produto.potencia && (
                                    <Grid size={{ xs: 6, md: 3 }}>
                                        <Typography variant="caption" color="text.secondary">Potência</Typography>
                                        <Typography fontWeight={600}>{produto.potencia} {produto.unidade_potencia}</Typography>
                                    </Grid>
                                )}
                                {produto.tensao && (
                                    <Grid size={{ xs: 6, md: 3 }}>
                                        <Typography variant="caption" color="text.secondary">Tensão</Typography>
                                        <Typography fontWeight={600}>{produto.tensao} V</Typography>
                                    </Grid>
                                )}
                                <Grid size={{ xs: 6, md: 3 }}>
                                    <Typography variant="caption" color="text.secondary">Unidade</Typography>
                                    <Typography fontWeight={600}>{produto.unidade}</Typography>
                                </Grid>
                                {produto.garantia && (
                                    <Grid size={{ xs: 6, md: 3 }}>
                                        <Typography variant="caption" color="text.secondary">Garantia</Typography>
                                        <Typography fontWeight={600}>{produto.garantia}</Typography>
                                    </Grid>
                                )}
                                <Grid size={{ xs: 6, md: 3 }}>
                                    <Typography variant="caption" color="text.secondary">Preço de Custo</Typography>
                                    <Typography fontWeight={700} color="primary.main">{formatPreco(produto.preco_custo)}</Typography>
                                </Grid>
                            </Grid>

                            {atributos.length > 0 && (
                                <>
                                    <Divider sx={{ my: 2 }} />
                                    <Typography variant="subtitle2" fontWeight={600} sx={{ mb: 1 }}>Atributos</Typography>
                                    <Grid container spacing={1}>
                                        {atributos.map(([k, v]) => (
                                            <Grid key={k} size={{ xs: 6, md: 4 }}>
                                                <Typography variant="caption" color="text.secondary" sx={{ textTransform: 'capitalize' }}>
                                                    {k.replace(/_/g, ' ')}
                                                </Typography>
                                                <Typography variant="body2" fontWeight={500}>{String(v)}</Typography>
                                            </Grid>
                                        ))}
                                    </Grid>
                                </>
                            )}
                        </CardContent>
                    </Card>

                    {/* Kits que usam este produto */}
                    {produto.kits.length > 0 && (
                        <Card variant="outlined" sx={{ borderRadius: 2, mt: 3 }}>
                            <CardContent sx={{ p: 3 }}>
                                <Typography variant="subtitle1" fontWeight={600} sx={{ mb: 2 }}>
                                    Kits que utilizam este produto ({produto.kits.length})
                                </Typography>
                                <Table size="small">
                                    <TableHead sx={{ bgcolor: '#F8FAFC' }}>
                                        <TableRow>
                                            <TableCell sx={{ fontWeight: 600 }}>Kit</TableCell>
                                            <TableCell sx={{ fontWeight: 600 }}>Potência</TableCell>
                                            <TableCell sx={{ fontWeight: 600 }}>Fornecedor</TableCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {produto.kits.map((kit) => (
                                            <TableRow key={kit.id} hover>
                                                <TableCell>
                                                    <Link href={route('admin.produtos.kits.show', kit.id)} style={{ textDecoration: 'none', color: 'inherit' }}>
                                                        {kit.nome}
                                                    </Link>
                                                </TableCell>
                                                <TableCell>{kit.potencia_kwp} kWp</TableCell>
                                                <TableCell>{kit.fornecedor.nome}</TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    )}
                </Grid>

                <Grid size={{ xs: 12, md: 4 }}>
                    <Card variant="outlined" sx={{ borderRadius: 2 }}>
                        <CardContent sx={{ p: 3 }}>
                            <Typography variant="subtitle2" color="text.secondary">Marca</Typography>
                            <Typography fontWeight={500} sx={{ mb: 2 }}>{produto.marca?.nome ?? '—'}</Typography>
                            <Typography variant="subtitle2" color="text.secondary">Fornecedor</Typography>
                            <Typography fontWeight={500} sx={{ mb: 2 }}>{produto.fornecedor?.nome ?? '—'}</Typography>
                            {produto.imagem_url && (
                                <>
                                    <Typography variant="subtitle2" color="text.secondary">Imagem</Typography>
                                    <Button href={produto.imagem_url} target="_blank" size="small">Abrir imagem</Button>
                                </>
                            )}
                            {produto.ficha_tecnica_url && (
                                <>
                                    <Typography variant="subtitle2" color="text.secondary" sx={{ mt: 1 }}>Ficha Técnica</Typography>
                                    <Button href={produto.ficha_tecnica_url} target="_blank" size="small">Abrir PDF</Button>
                                </>
                            )}
                        </CardContent>
                    </Card>
                </Grid>
            </Grid>
        </AppLayout>
    );
}
