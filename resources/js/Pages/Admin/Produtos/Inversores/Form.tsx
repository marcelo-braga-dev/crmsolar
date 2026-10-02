import React from 'react';
import {
    Box,
    Button,
    Card,
    CardContent,
    CardHeader,
    Divider,
    FormControlLabel,
    Grid,
    MenuItem,
    Switch,
    TextField,
} from '@mui/material';
import SaveRoundedIcon from '@mui/icons-material/SaveRounded';
import ArrowBackRoundedIcon from '@mui/icons-material/ArrowBackRounded';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { PageProps } from '@/types';

interface Produto {
    id?: number;
    marca_id?: number;
    fornecedor_id?: number;
    nome: string;
    modelo?: string;
    sku?: string;
    descricao?: string;
    potencia?: number;
    unidade_potencia?: string;
    tensao?: string;
    preco_custo?: number;
    garantia?: number;
    ativo: boolean;
}

interface Props extends PageProps {
    inversor?: Produto;
    marcas: { id: number; nome: string }[];
    fornecedores: { id: number; nome: string }[];
}

export default function InversoresForm({ inversor, marcas, fornecedores }: Props) {
    const isEdit = !!inversor?.id;

    const { data, setData, post, put, processing, errors } = useForm({
        marca_id: inversor?.marca_id ?? '',
        fornecedor_id: inversor?.fornecedor_id ?? '',
        nome: inversor?.nome ?? '',
        modelo: inversor?.modelo ?? '',
        sku: inversor?.sku ?? '',
        descricao: inversor?.descricao ?? '',
        potencia: inversor?.potencia ?? '',
        unidade_potencia: inversor?.unidade_potencia ?? 'kWp',
        tensao: inversor?.tensao ?? '',
        preco_custo: inversor?.preco_custo ?? '',
        garantia: inversor?.garantia ?? '',
        ativo: inversor?.ativo ?? true,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (isEdit) {
            put(route('admin.produtos.inversores.update', inversor!.id));
        } else {
            post(route('admin.produtos.inversores.store'));
        }
    }

    return (
        <AppLayout>
            <Head title={isEdit ? 'Editar Inversor' : 'Novo Inversor'} />

            <PageHeader
                title={isEdit ? 'Editar Inversor' : 'Novo Inversor'}
                breadcrumbs={[
                    { label: 'Produtos' },
                    { label: 'Inversores', href: route('admin.produtos.inversores.index') },
                    { label: isEdit ? 'Editar' : 'Novo' },
                ]}
                action={
                    <Button component={Link} href={route('admin.produtos.inversores.index')} startIcon={<ArrowBackRoundedIcon />}>
                        Voltar
                    </Button>
                }
            />

            <form onSubmit={handleSubmit}>
                <Grid container spacing={3}>
                    <Grid size={{ xs: 12, md: 8 }}>
                        <Card>
                            <CardHeader title="Dados do Produto" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                            <Divider />
                            <CardContent>
                                <Grid container spacing={3}>
                                    <Grid size={{ xs: 12 }}>
                                        <TextField label="Nome *" fullWidth value={data.nome} onChange={(e) => setData('nome', e.target.value)} error={!!errors.nome} helperText={errors.nome} />
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <TextField label="Modelo" fullWidth value={data.modelo} onChange={(e) => setData('modelo', e.target.value)} />
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <TextField label="SKU / Código" fullWidth value={data.sku} onChange={(e) => setData('sku', e.target.value)} />
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <TextField select label="Marca" fullWidth value={data.marca_id} onChange={(e) => setData('marca_id', e.target.value)}>
                                            <MenuItem value="">Nenhuma</MenuItem>
                                            {marcas.map((m) => <MenuItem key={m.id} value={m.id}>{m.nome}</MenuItem>)}
                                        </TextField>
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <TextField select label="Fornecedor" fullWidth value={data.fornecedor_id} onChange={(e) => setData('fornecedor_id', e.target.value)}>
                                            <MenuItem value="">Nenhum</MenuItem>
                                            {fornecedores.map((f) => <MenuItem key={f.id} value={f.id}>{f.nome}</MenuItem>)}
                                        </TextField>
                                    </Grid>
                                    <Grid size={{ xs: 12 }}>
                                        <TextField label="Descrição" fullWidth multiline rows={3} value={data.descricao} onChange={(e) => setData('descricao', e.target.value)} />
                                    </Grid>
                                </Grid>
                            </CardContent>
                        </Card>
                    </Grid>

                    <Grid size={{ xs: 12, md: 4 }}>
                        <Card sx={{ mb: 2 }}>
                            <CardHeader title="Especificações Técnicas" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                            <Divider />
                            <CardContent>
                                <Grid container spacing={3}>
                                    <Grid size={{ xs: 7 }}>
                                        <TextField label="Potência" type="number" fullWidth inputProps={{ step: '0.01', min: '0' }} value={data.potencia} onChange={(e) => setData('potencia', e.target.value)} error={!!errors.potencia} helperText={errors.potencia} />
                                    </Grid>
                                    <Grid size={{ xs: 5 }}>
                                        <TextField select label="Unidade" fullWidth value={data.unidade_potencia} onChange={(e) => setData('unidade_potencia', e.target.value)}>
                                            <MenuItem value="kWp">kWp</MenuItem>
                                            <MenuItem value="kW">kW</MenuItem>
                                            <MenuItem value="W">W</MenuItem>
                                            <MenuItem value="kVA">kVA</MenuItem>
                                        </TextField>
                                    </Grid>
                                    <Grid size={{ xs: 12 }}>
                                        <TextField label="Tensão (V)" type="number" fullWidth inputProps={{ min: '0' }} value={data.tensao} onChange={(e) => setData('tensao', e.target.value)} placeholder="Ex: 220" error={!!errors.tensao} helperText={errors.tensao} />
                                    </Grid>
                                    <Grid size={{ xs: 12 }}>
                                        <TextField label="Preço de Custo (R$)" type="number" fullWidth inputProps={{ step: '0.01', min: '0' }} value={data.preco_custo} onChange={(e) => setData('preco_custo', e.target.value)} error={!!errors.preco_custo} helperText={errors.preco_custo} />
                                    </Grid>
                                    <Grid size={{ xs: 12 }}>
                                        <TextField label="Garantia (anos)" type="number" fullWidth inputProps={{ min: '0' }} value={data.garantia} onChange={(e) => setData('garantia', e.target.value)} />
                                    </Grid>
                                    <Grid size={{ xs: 12 }}>
                                        <FormControlLabel
                                            control={<Switch checked={!!data.ativo} onChange={(e) => setData('ativo', e.target.checked)} />}
                                            label="Produto ativo"
                                        />
                                    </Grid>
                                </Grid>
                            </CardContent>
                        </Card>
                    </Grid>

                    <Grid size={{ xs: 12 }}>
                        <Box sx={{ display: 'flex', gap: 2, justifyContent: 'flex-end' }}>
                            <Button component={Link} href={route('admin.produtos.inversores.index')} disabled={processing}>Cancelar</Button>
                            <Button type="submit" variant="contained" startIcon={<SaveRoundedIcon />} disabled={processing}>
                                {isEdit ? 'Salvar Alterações' : 'Criar Inversor'}
                            </Button>
                        </Box>
                    </Grid>
                </Grid>
            </form>
        </AppLayout>
    );
}
