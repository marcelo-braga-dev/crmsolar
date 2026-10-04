import React from 'react';
import {
    Box, Button, Card, CardContent, Divider, FormControlLabel,
    Grid, MenuItem, Switch, TextField, Typography,
} from '@mui/material';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';

interface Categoria {
    id: number; nome: string; slug: string;
    eh_componente_kit: boolean; exige_potencia: boolean; icone?: string;
}
interface Marca { id: number; nome: string; }
interface Fornecedor { id: number; nome: string; }
interface Produto {
    id?: number; categoria_id: number; marca_id?: number; fornecedor_id?: number;
    nome: string; modelo?: string; sku?: string; descricao?: string;
    potencia?: number; unidade_potencia?: string; tensao?: number; unidade: string;
    preco_custo: number; garantia?: string; imagem_url?: string; ficha_tecnica_url?: string;
    atributos?: Record<string, unknown>; ativo: boolean;
}

interface FormData {
    categoria_id: number | string;
    marca_id: number | string;
    fornecedor_id: number | string;
    nome: string;
    modelo: string;
    sku: string;
    descricao: string;
    potencia: string;
    unidade_potencia: string;
    tensao: string;
    unidade: string;
    preco_custo: number | string;
    garantia: string;
    imagem_url: string;
    ficha_tecnica_url: string;
    ativo: boolean;
}

interface Props {
    produto?: Produto;
    categorias: Categoria[];
    marcas: Marca[];
    fornecedores: Fornecedor[];
    categoriaInicial?: number | null;
}

const UNIDADES_POTENCIA = ['Wp', 'kWp', 'W', 'kW', 'VA', 'kVA'];

export default function CatalogoForm({ produto, categorias, marcas, fornecedores, categoriaInicial }: Props) {
    const editing = Boolean(produto?.id);

    const { data, setData, post, put, processing, errors } = useForm<FormData>({
        categoria_id: produto?.categoria_id ?? categoriaInicial ?? '',
        marca_id: produto?.marca_id ?? '',
        fornecedor_id: produto?.fornecedor_id ?? '',
        nome: produto?.nome ?? '',
        modelo: produto?.modelo ?? '',
        sku: produto?.sku ?? '',
        descricao: produto?.descricao ?? '',
        potencia: produto?.potencia ? String(produto.potencia) : '',
        unidade_potencia: produto?.unidade_potencia ?? 'Wp',
        tensao: produto?.tensao ? String(produto.tensao) : '',
        unidade: produto?.unidade ?? 'un',
        preco_custo: produto?.preco_custo ?? 0,
        garantia: produto?.garantia ?? '',
        imagem_url: produto?.imagem_url ?? '',
        ficha_tecnica_url: produto?.ficha_tecnica_url ?? '',
        ativo: produto?.ativo ?? true,
    });

    const categoriaAtual = categorias.find((c) => c.id === Number(data.categoria_id));
    const exigePotencia = categoriaAtual?.exige_potencia ?? false;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editing && produto?.id) {
            put(route('admin.produtos.catalogo.update', produto.id));
        } else {
            post(route('admin.produtos.catalogo.store'));
        }
    };

    return (
        <AppLayout title={editing ? 'Editar Produto' : 'Novo Produto'}>
            <Head title={editing ? 'Editar Produto' : 'Novo Produto'} />

            <PageHeader
                title={editing ? 'Editar Produto' : 'Novo Produto no Catálogo'}
                breadcrumbs={[
                    { label: 'Admin' },
                    { label: 'Catálogo', href: route('admin.produtos.catalogo.index') },
                    { label: editing ? 'Editar' : 'Novo' },
                ]}
            />

            <Box component="form" onSubmit={handleSubmit} noValidate>
                <Grid container spacing={3}>
                    {/* Identificação */}
                    <Grid size={{ xs: 12, lg: 8 }}>
                        <Card variant="outlined" sx={{ borderRadius: 2 }}>
                            <CardContent sx={{ p: 3 }}>
                                <Typography variant="subtitle1" fontWeight={600} sx={{ mb: 2 }}>
                                    Identificação
                                </Typography>
                                <Grid container spacing={3}>
                                    <Grid size={{ xs: 12, md: 6 }}>
                                        <TextField
                                            select
                                            label="Categoria *"
                                            fullWidth
                                            size="small"
                                            value={data.categoria_id || ''}
                                            onChange={(e) => setData('categoria_id', Number(e.target.value))}
                                            error={Boolean(errors.categoria_id)}
                                            helperText={errors.categoria_id}
                                        >
                                            <MenuItem value="">Selecione...</MenuItem>
                                            {categorias.map((c) => (
                                                <MenuItem key={c.id} value={c.id}>{c.nome}</MenuItem>
                                            ))}
                                        </TextField>
                                    </Grid>
                                    <Grid size={{ xs: 12, md: 6 }}>
                                        <TextField
                                            select
                                            label="Marca"
                                            fullWidth
                                            size="small"
                                            value={data.marca_id || ''}
                                            onChange={(e) => setData('marca_id', Number(e.target.value) || '')}
                                        >
                                            <MenuItem value="">Sem marca</MenuItem>
                                            {marcas.map((m) => (
                                                <MenuItem key={m.id} value={m.id}>{m.nome}</MenuItem>
                                            ))}
                                        </TextField>
                                    </Grid>
                                    <Grid size={{ xs: 12 }}>
                                        <TextField
                                            label="Nome *"
                                            fullWidth
                                            size="small"
                                            value={data.nome}
                                            onChange={(e) => setData('nome', e.target.value)}
                                            error={Boolean(errors.nome)}
                                            helperText={errors.nome}
                                        />
                                    </Grid>
                                    <Grid size={{ xs: 12, md: 6 }}>
                                        <TextField
                                            label="Modelo"
                                            fullWidth
                                            size="small"
                                            value={data.modelo}
                                            onChange={(e) => setData('modelo', e.target.value)}
                                        />
                                    </Grid>
                                    <Grid size={{ xs: 12, md: 6 }}>
                                        <TextField
                                            label="SKU / Código"
                                            fullWidth
                                            size="small"
                                            value={data.sku}
                                            onChange={(e) => setData('sku', e.target.value)}
                                            error={Boolean(errors.sku)}
                                            helperText={errors.sku}
                                        />
                                    </Grid>
                                    <Grid size={{ xs: 12 }}>
                                        <TextField
                                            label="Descrição"
                                            fullWidth
                                            multiline
                                            rows={3}
                                            size="small"
                                            value={data.descricao}
                                            onChange={(e) => setData('descricao', e.target.value)}
                                        />
                                    </Grid>
                                </Grid>
                            </CardContent>
                        </Card>

                        {/* Especificações técnicas */}
                        <Card variant="outlined" sx={{ borderRadius: 2, mt: 3 }}>
                            <CardContent sx={{ p: 3 }}>
                                <Typography variant="subtitle1" fontWeight={600} sx={{ mb: 2 }}>
                                    Especificações Técnicas
                                </Typography>
                                <Grid container spacing={3}>
                                    {exigePotencia && (
                                        <>
                                            <Grid size={{ xs: 12, md: 4 }}>
                                                <TextField
                                                    label="Potência"
                                                    type="number"
                                                    fullWidth
                                                    size="small"
                                                    value={data.potencia ?? ''}
                                                    onChange={(e) => setData('potencia', e.target.value)}
                                                    error={Boolean(errors.potencia)}
                                                    helperText={errors.potencia}
                                                    inputProps={{ step: 0.001 }}
                                                />
                                            </Grid>
                                            <Grid size={{ xs: 12, md: 4 }}>
                                                <TextField
                                                    select
                                                    label="Unidade de Potência"
                                                    fullWidth
                                                    size="small"
                                                    value={data.unidade_potencia}
                                                    onChange={(e) => setData('unidade_potencia', e.target.value)}
                                                >
                                                    {UNIDADES_POTENCIA.map((u) => (
                                                        <MenuItem key={u} value={u}>{u}</MenuItem>
                                                    ))}
                                                </TextField>
                                            </Grid>
                                        </>
                                    )}
                                    <Grid size={{ xs: 12, md: 4 }}>
                                        <TextField
                                            label="Tensão (V)"
                                            type="number"
                                            fullWidth
                                            size="small"
                                            value={data.tensao ?? ''}
                                            onChange={(e) => setData('tensao', e.target.value)}
                                        />
                                    </Grid>
                                    <Grid size={{ xs: 12, md: 4 }}>
                                        <TextField
                                            label="Unidade de venda"
                                            fullWidth
                                            size="small"
                                            value={data.unidade}
                                            onChange={(e) => setData('unidade', e.target.value)}
                                            placeholder="un, m, m², kg..."
                                        />
                                    </Grid>
                                    <Grid size={{ xs: 12, md: 6 }}>
                                        <TextField
                                            label="Garantia"
                                            fullWidth
                                            size="small"
                                            value={data.garantia}
                                            onChange={(e) => setData('garantia', e.target.value)}
                                            placeholder="Ex: 10 anos de produto"
                                        />
                                    </Grid>
                                </Grid>
                            </CardContent>
                        </Card>
                    </Grid>

                    {/* Sidebar */}
                    <Grid size={{ xs: 12, lg: 4 }}>
                        <Card variant="outlined" sx={{ borderRadius: 2 }}>
                            <CardContent sx={{ p: 3 }}>
                                <Typography variant="subtitle1" fontWeight={600} sx={{ mb: 2 }}>
                                    Comercial
                                </Typography>
                                <Grid container spacing={3}>
                                    <Grid size={{ xs: 12 }}>
                                        <TextField
                                            select
                                            label="Fornecedor"
                                            fullWidth
                                            size="small"
                                            value={data.fornecedor_id || ''}
                                            onChange={(e) => setData('fornecedor_id', Number(e.target.value) || '')}
                                        >
                                            <MenuItem value="">Sem fornecedor</MenuItem>
                                            {fornecedores.map((f) => (
                                                <MenuItem key={f.id} value={f.id}>{f.nome}</MenuItem>
                                            ))}
                                        </TextField>
                                    </Grid>
                                    <Grid size={{ xs: 12 }}>
                                        <TextField
                                            label="Preço de Custo (R$) *"
                                            type="number"
                                            fullWidth
                                            size="small"
                                            value={data.preco_custo}
                                            onChange={(e) => setData('preco_custo', Number(e.target.value))}
                                            error={Boolean(errors.preco_custo)}
                                            helperText={errors.preco_custo}
                                            inputProps={{ step: 0.01 }}
                                        />
                                    </Grid>
                                </Grid>

                                <Divider sx={{ my: 2 }} />

                                <Typography variant="subtitle1" fontWeight={600} sx={{ mb: 2 }}>
                                    Links
                                </Typography>
                                <Grid container spacing={3}>
                                    <Grid size={{ xs: 12 }}>
                                        <TextField
                                            label="URL da Imagem"
                                            fullWidth
                                            size="small"
                                            value={data.imagem_url}
                                            onChange={(e) => setData('imagem_url', e.target.value)}
                                            error={Boolean(errors.imagem_url)}
                                            helperText={errors.imagem_url}
                                        />
                                    </Grid>
                                    <Grid size={{ xs: 12 }}>
                                        <TextField
                                            label="URL da Ficha Técnica"
                                            fullWidth
                                            size="small"
                                            value={data.ficha_tecnica_url}
                                            onChange={(e) => setData('ficha_tecnica_url', e.target.value)}
                                            error={Boolean(errors.ficha_tecnica_url)}
                                            helperText={errors.ficha_tecnica_url}
                                        />
                                    </Grid>
                                </Grid>

                                <Divider sx={{ my: 2 }} />

                                <FormControlLabel
                                    control={
                                        <Switch
                                            checked={data.ativo}
                                            onChange={(e) => setData('ativo', e.target.checked)}
                                        />
                                    }
                                    label="Produto ativo"
                                />
                            </CardContent>
                        </Card>

                        <Box sx={{ display: 'flex', gap: 2, mt: 2 }}>
                            <Button
                                variant="outlined"
                                fullWidth
                                href={route('admin.produtos.catalogo.index')}
                                component="a"
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                variant="contained"
                                fullWidth
                                disabled={processing}
                            >
                                {editing ? 'Salvar' : 'Cadastrar'}
                            </Button>
                        </Box>
                    </Grid>
                </Grid>
            </Box>
        </AppLayout>
    );
}
