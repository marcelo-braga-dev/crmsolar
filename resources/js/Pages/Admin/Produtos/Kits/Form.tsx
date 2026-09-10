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
    Typography,
} from '@mui/material';
import SaveRoundedIcon from '@mui/icons-material/SaveRounded';
import ArrowBackRoundedIcon from '@mui/icons-material/ArrowBackRounded';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { PageProps } from '@/types';

interface Kit {
    id?: number;
    fornecedor_id?: number;
    estrutura_id?: number;
    nome: string;
    modelo?: string;
    sku?: string;
    potencia_kwp?: number;
    tensao?: string;
    inclui_trafo: boolean;
    preco_custo?: number;
    margem_padrao?: number;
    ativo: boolean;
    ativo_fornecedor: boolean;
    observacoes?: string;
}

interface Fornecedor { id: number; nome: string }
interface Estrutura { id: number; nome: string }

interface Props extends PageProps {
    kit?: Kit;
    fornecedores: Fornecedor[];
    estruturas: Estrutura[];
}

export default function KitsForm({ kit, fornecedores, estruturas }: Props) {
    const isEdit = !!kit?.id;

    const { data, setData, post, put, processing, errors } = useForm({
        fornecedor_id: kit?.fornecedor_id ?? '',
        estrutura_id: kit?.estrutura_id ?? '',
        nome: kit?.nome ?? '',
        modelo: kit?.modelo ?? '',
        sku: kit?.sku ?? '',
        potencia_kwp: kit?.potencia_kwp ?? '',
        tensao: kit?.tensao ?? '',
        inclui_trafo: kit?.inclui_trafo ?? false,
        preco_custo: kit?.preco_custo ?? '',
        margem_padrao: kit?.margem_padrao ?? '',
        ativo: kit?.ativo ?? true,
        ativo_fornecedor: kit?.ativo_fornecedor ?? true,
        observacoes: kit?.observacoes ?? '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (isEdit) {
            put(route('admin.produtos.kits.update', kit!.id));
        } else {
            post(route('admin.produtos.kits.store'));
        }
    }

    return (
        <AppLayout>
            <Head title={isEdit ? 'Editar Kit' : 'Novo Kit'} />

            <PageHeader
                title={isEdit ? 'Editar Kit Solar' : 'Novo Kit Solar'}
                breadcrumbs={[
                    { label: 'Produtos' },
                    { label: 'Kits Solares', href: route('admin.produtos.kits.index') },
                    { label: isEdit ? 'Editar' : 'Novo' },
                ]}
                action={
                    <Button component={Link} href={route('admin.produtos.kits.index')} startIcon={<ArrowBackRoundedIcon />}>
                        Voltar
                    </Button>
                }
            />

            <form onSubmit={handleSubmit}>
                <Grid container spacing={3}>
                    {/* Identificação */}
                    <Grid size={{ xs: 12, md: 8 }}>
                        <Card>
                            <CardHeader title="Identificação" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                            <Divider />
                            <CardContent>
                                <Grid container spacing={3}>
                                    <Grid size={{ xs: 12 }}>
                                        <TextField
                                            label="Nome do Kit *"
                                            fullWidth
                                            value={data.nome}
                                            onChange={(e) => setData('nome', e.target.value)}
                                            error={!!errors.nome}
                                            helperText={errors.nome}
                                        />
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <TextField
                                            label="Modelo"
                                            fullWidth
                                            value={data.modelo}
                                            onChange={(e) => setData('modelo', e.target.value)}
                                            error={!!errors.modelo}
                                            helperText={errors.modelo}
                                        />
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <TextField
                                            label="SKU / Código"
                                            fullWidth
                                            value={data.sku}
                                            onChange={(e) => setData('sku', e.target.value)}
                                            error={!!errors.sku}
                                            helperText={errors.sku}
                                        />
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <TextField
                                            select
                                            label="Fornecedor"
                                            fullWidth
                                            value={data.fornecedor_id}
                                            onChange={(e) => setData('fornecedor_id', e.target.value)}
                                            error={!!errors.fornecedor_id}
                                            helperText={errors.fornecedor_id}
                                        >
                                            <MenuItem value="">Nenhum</MenuItem>
                                            {fornecedores.map((f) => (
                                                <MenuItem key={f.id} value={f.id}>{f.nome}</MenuItem>
                                            ))}
                                        </TextField>
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <TextField
                                            select
                                            label="Tipo de Estrutura"
                                            fullWidth
                                            value={data.estrutura_id}
                                            onChange={(e) => setData('estrutura_id', e.target.value)}
                                            error={!!errors.estrutura_id}
                                            helperText={errors.estrutura_id}
                                        >
                                            <MenuItem value="">Nenhum</MenuItem>
                                            {estruturas.map((e) => (
                                                <MenuItem key={e.id} value={e.id}>{e.nome}</MenuItem>
                                            ))}
                                        </TextField>
                                    </Grid>
                                    <Grid size={{ xs: 12 }}>
                                        <TextField
                                            label="Observações"
                                            fullWidth
                                            multiline
                                            rows={3}
                                            value={data.observacoes}
                                            onChange={(e) => setData('observacoes', e.target.value)}
                                        />
                                    </Grid>
                                </Grid>
                            </CardContent>
                        </Card>
                    </Grid>

                    {/* Especificações */}
                    <Grid size={{ xs: 12, md: 4 }}>
                        <Card sx={{ mb: 2 }}>
                            <CardHeader title="Especificações" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                            <Divider />
                            <CardContent>
                                <Grid container spacing={3}>
                                    <Grid size={{ xs: 12 }}>
                                        <TextField
                                            label="Potência (kWp) *"
                                            type="number"
                                            fullWidth
                                            inputProps={{ step: '0.01', min: '0.1' }}
                                            value={data.potencia_kwp}
                                            onChange={(e) => setData('potencia_kwp', e.target.value)}
                                            error={!!errors.potencia_kwp}
                                            helperText={errors.potencia_kwp}
                                        />
                                    </Grid>
                                    <Grid size={{ xs: 12 }}>
                                        <TextField
                                            label="Tensão"
                                            fullWidth
                                            value={data.tensao}
                                            onChange={(e) => setData('tensao', e.target.value)}
                                            placeholder="Ex: 220V / 380V"
                                        />
                                    </Grid>
                                    <Grid size={{ xs: 12 }}>
                                        <FormControlLabel
                                            control={
                                                <Switch
                                                    checked={!!data.inclui_trafo}
                                                    onChange={(e) => setData('inclui_trafo', e.target.checked)}
                                                />
                                            }
                                            label="Inclui Transformador"
                                        />
                                    </Grid>
                                </Grid>
                            </CardContent>
                        </Card>

                        <Card sx={{ mb: 2 }}>
                            <CardHeader title="Precificação" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                            <Divider />
                            <CardContent>
                                <Grid container spacing={3}>
                                    <Grid size={{ xs: 12 }}>
                                        <TextField
                                            label="Preço de Custo (R$)"
                                            type="number"
                                            fullWidth
                                            inputProps={{ step: '0.01', min: '0' }}
                                            value={data.preco_custo}
                                            onChange={(e) => setData('preco_custo', e.target.value)}
                                            error={!!errors.preco_custo}
                                            helperText={errors.preco_custo}
                                        />
                                    </Grid>
                                    <Grid size={{ xs: 12 }}>
                                        <TextField
                                            label="Margem Padrão (%)"
                                            type="number"
                                            fullWidth
                                            inputProps={{ step: '0.1', min: '0', max: '100' }}
                                            value={data.margem_padrao}
                                            onChange={(e) => setData('margem_padrao', e.target.value)}
                                            error={!!errors.margem_padrao}
                                            helperText={errors.margem_padrao}
                                        />
                                    </Grid>
                                </Grid>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader title="Disponibilidade" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                            <Divider />
                            <CardContent>
                                <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1 }}>
                                    <FormControlLabel
                                        control={
                                            <Switch
                                                checked={!!data.ativo}
                                                onChange={(e) => setData('ativo', e.target.checked)}
                                            />
                                        }
                                        label="Kit ativo"
                                    />
                                    <FormControlLabel
                                        control={
                                            <Switch
                                                checked={!!data.ativo_fornecedor}
                                                onChange={(e) => setData('ativo_fornecedor', e.target.checked)}
                                            />
                                        }
                                        label="Disponível no fornecedor"
                                    />
                                </Box>
                            </CardContent>
                        </Card>
                    </Grid>

                    {/* Botões */}
                    <Grid size={{ xs: 12 }}>
                        <Box sx={{ display: 'flex', gap: 2, justifyContent: 'flex-end' }}>
                            <Button
                                component={Link}
                                href={route('admin.produtos.kits.index')}
                                disabled={processing}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                variant="contained"
                                startIcon={<SaveRoundedIcon />}
                                disabled={processing}
                            >
                                {isEdit ? 'Salvar Alterações' : 'Criar Kit'}
                            </Button>
                        </Box>
                    </Grid>
                </Grid>
            </form>
        </AppLayout>
    );
}
