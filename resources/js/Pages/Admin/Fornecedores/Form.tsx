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
    Switch,
    TextField,
} from '@mui/material';
import SaveRoundedIcon from '@mui/icons-material/SaveRounded';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { PageProps } from '@/types';

interface FornecedorData {
    id?: number;
    nome: string;
    cnpj?: string;
    email?: string;
    telefone?: string;
    celular?: string;
    representante?: string;
    site?: string;
    margem_padrao?: string;
    anotacoes?: string;
    ativo: boolean;
}

interface FormData {
    nome: string;
    cnpj: string;
    email: string;
    telefone: string;
    celular: string;
    representante: string;
    site: string;
    margem_padrao: string;
    anotacoes: string;
    ativo: boolean;
}

interface Props extends PageProps {
    fornecedor?: FornecedorData;
}

export default function FornecedoresForm({ fornecedor }: Props) {
    const editing = !!fornecedor?.id;

    const { data, setData, post, put, processing, errors } = useForm<FormData>({
        nome: fornecedor?.nome ?? '',
        cnpj: fornecedor?.cnpj ?? '',
        email: fornecedor?.email ?? '',
        telefone: fornecedor?.telefone ?? '',
        celular: fornecedor?.celular ?? '',
        representante: fornecedor?.representante ?? '',
        site: fornecedor?.site ?? '',
        margem_padrao: fornecedor?.margem_padrao ?? '',
        anotacoes: fornecedor?.anotacoes ?? '',
        ativo: fornecedor?.ativo ?? true,
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (editing) {
            put(route('admin.fornecedores.update', fornecedor!.id));
        } else {
            post(route('admin.fornecedores.store'));
        }
    }

    return (
        <AppLayout>
            <Head title={editing ? 'Editar Fornecedor' : 'Novo Fornecedor'} />

            <PageHeader
                title={editing ? 'Editar Fornecedor' : 'Novo Fornecedor'}
                breadcrumbs={[
                    { label: 'Fornecedores', href: route('admin.fornecedores.index') },
                    { label: editing ? 'Editar' : 'Novo' },
                ]}
            />

            <Box component="form" onSubmit={submit}>
                <Card sx={{ mb: 3 }}>
                    <CardHeader title="Dados do Fornecedor" />
                    <Divider />
                    <CardContent>
                        <Grid container spacing={3}>
                            <Grid size={{ xs: 12, sm: 6 }}>
                                <TextField fullWidth size="small" label="Nome *"
                                    value={data.nome} onChange={(e) => setData('nome', e.target.value)}
                                    error={!!errors.nome} helperText={errors.nome} autoFocus />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 3 }}>
                                <TextField fullWidth size="small" label="CNPJ"
                                    value={data.cnpj} onChange={(e) => setData('cnpj', e.target.value)}
                                    placeholder="00.000.000/0000-00" />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 3 }}>
                                <TextField fullWidth size="small" label="Margem adicional (%)"
                                    type="number" value={data.margem_padrao}
                                    onChange={(e) => setData('margem_padrao', e.target.value)}
                                    inputProps={{ step: '0.01', min: '0', max: '100' }}
                                    error={!!errors.margem_padrao} helperText={errors.margem_padrao} />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 4 }}>
                                <TextField fullWidth size="small" label="E-mail" type="email"
                                    value={data.email} onChange={(e) => setData('email', e.target.value)}
                                    error={!!errors.email} helperText={errors.email} />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 4 }}>
                                <TextField fullWidth size="small" label="Telefone"
                                    value={data.telefone} onChange={(e) => setData('telefone', e.target.value)} />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 4 }}>
                                <TextField fullWidth size="small" label="Celular"
                                    value={data.celular} onChange={(e) => setData('celular', e.target.value)} />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 6 }}>
                                <TextField fullWidth size="small" label="Representante"
                                    value={data.representante} onChange={(e) => setData('representante', e.target.value)} />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 6 }}>
                                <TextField fullWidth size="small" label="Site"
                                    value={data.site} onChange={(e) => setData('site', e.target.value)}
                                    placeholder="https://..."
                                    error={!!errors.site} helperText={errors.site} />
                            </Grid>
                            <Grid size={{ xs: 12 }}>
                                <TextField fullWidth size="small" label="Anotações" multiline rows={3}
                                    value={data.anotacoes} onChange={(e) => setData('anotacoes', e.target.value)} />
                            </Grid>
                            <Grid size={{ xs: 12 }}>
                                <FormControlLabel
                                    control={<Switch checked={data.ativo} onChange={(e) => setData('ativo', e.target.checked)} />}
                                    label="Fornecedor ativo"
                                />
                            </Grid>
                        </Grid>
                    </CardContent>
                </Card>

                <Box sx={{ display: 'flex', gap: 2, justifyContent: 'flex-end' }}>
                    <Button component={Link} href={route('admin.fornecedores.index')} variant="outlined">
                        Cancelar
                    </Button>
                    <Button type="submit" variant="contained" startIcon={<SaveRoundedIcon />} disabled={processing}>
                        {editing ? 'Salvar alterações' : 'Criar fornecedor'}
                    </Button>
                </Box>
            </Box>
        </AppLayout>
    );
}
