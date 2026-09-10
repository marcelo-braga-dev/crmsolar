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
    tensao?: string;
    preco_custo?: number;
    garantia?: number;
    ativo: boolean;
}

interface Props extends PageProps {
    trafo?: Produto;
    marcas: { id: number; nome: string }[];
    fornecedores: { id: number; nome: string }[];
}

export default function TrafosForm({ trafo, marcas, fornecedores }: Props) {
    const isEdit = !!trafo?.id;

    const { data, setData, post, put, processing, errors } = useForm({
        marca_id: trafo?.marca_id ?? '',
        fornecedor_id: trafo?.fornecedor_id ?? '',
        nome: trafo?.nome ?? '',
        modelo: trafo?.modelo ?? '',
        sku: trafo?.sku ?? '',
        descricao: trafo?.descricao ?? '',
        potencia: trafo?.potencia ?? '',
        tensao: trafo?.tensao ?? '',
        preco_custo: trafo?.preco_custo ?? '',
        garantia: trafo?.garantia ?? '',
        ativo: trafo?.ativo ?? true,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (isEdit) {
            put(route('admin.produtos.trafos.update', trafo!.id));
        } else {
            post(route('admin.produtos.trafos.store'));
        }
    }

    return (
        <AppLayout>
            <Head title={isEdit ? 'Editar Transformador' : 'Novo Transformador'} />

            <PageHeader
                title={isEdit ? 'Editar Transformador' : 'Novo Transformador'}
                breadcrumbs={[
                    { label: 'Produtos' },
                    { label: 'Transformadores', href: route('admin.produtos.trafos.index') },
                    { label: isEdit ? 'Editar' : 'Novo' },
                ]}
                action={
                    <Button component={Link} href={route('admin.produtos.trafos.index')} startIcon={<ArrowBackRoundedIcon />}>
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
                            <CardHeader title="Especificações" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                            <Divider />
                            <CardContent>
                                <Grid container spacing={3}>
                                    <Grid size={{ xs: 12 }}>
                                        <TextField label="Potência (kVA)" type="number" fullWidth inputProps={{ step: '0.1', min: '0' }} value={data.potencia} onChange={(e) => setData('potencia', e.target.value)} error={!!errors.potencia} helperText={errors.potencia} />
                                    </Grid>
                                    <Grid size={{ xs: 12 }}>
                                        <TextField label="Tensão" fullWidth value={data.tensao} onChange={(e) => setData('tensao', e.target.value)} placeholder="Ex: 220V / 380V" />
                                    </Grid>
                                    <Grid size={{ xs: 12 }}>
                                        <TextField label="Preço de Custo (R$)" type="number" fullWidth inputProps={{ step: '0.01', min: '0' }} value={data.preco_custo} onChange={(e) => setData('preco_custo', e.target.value)} />
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
                            <Button component={Link} href={route('admin.produtos.trafos.index')} disabled={processing}>Cancelar</Button>
                            <Button type="submit" variant="contained" startIcon={<SaveRoundedIcon />} disabled={processing}>
                                {isEdit ? 'Salvar Alterações' : 'Criar Transformador'}
                            </Button>
                        </Box>
                    </Grid>
                </Grid>
            </form>
        </AppLayout>
    );
}
