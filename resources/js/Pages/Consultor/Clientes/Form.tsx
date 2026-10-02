import React from 'react';
import {
    Box, Button, Card, CardContent, CardHeader, Divider,
    FormControl, Grid, InputLabel, MenuItem,
    Select, TextField, ToggleButton, ToggleButtonGroup, Typography,
} from '@mui/material';
import SaveRoundedIcon from '@mui/icons-material/SaveRounded';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { maskCpf, maskCnpj, maskPhone } from '@/utils/masks';
import { EnderecoFields } from '@/Components/UI/EnderecoFields';
import { PageProps } from '@/types';

interface ClienteData {
    id?: number; cidade_id: string; tipo_pessoa: 'pf' | 'pj';
    nome: string; razao_social: string; cpf: string; cnpj: string; rg: string;
    data_nascimento: string; email: string; telefone: string; celular: string;
    cep: string; rua: string; numero: string; complemento: string; bairro: string;
    status: string; anotacoes: string;
    cidade?: { id: number; cidade: string; estado: string; sigla: string };
}

interface Props extends PageProps { cliente?: ClienteData }

export default function ClientesForm({ cliente }: Props) {
    const editing = !!cliente?.id;

    const { data, setData, post, put, processing, errors } = useForm({
        cidade_id: String(cliente?.cidade_id ?? ''),
        tipo_pessoa: (cliente?.tipo_pessoa ?? 'pf') as 'pf' | 'pj',
        nome: cliente?.nome ?? '',
        razao_social: cliente?.razao_social ?? '',
        cpf: cliente?.cpf ?? '',
        cnpj: cliente?.cnpj ?? '',
        rg: cliente?.rg ?? '',
        data_nascimento: cliente?.data_nascimento ?? '',
        email: cliente?.email ?? '',
        telefone: cliente?.telefone ?? '',
        celular: cliente?.celular ?? '',
        cep: cliente?.cep ?? '',
        rua: cliente?.rua ?? '',
        numero: cliente?.numero ?? '',
        complemento: cliente?.complemento ?? '',
        bairro: cliente?.bairro ?? '',
        status: cliente?.status ?? 'novo',
        anotacoes: cliente?.anotacoes ?? '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (editing) put(route('consultor.clientes.update', cliente!.id));
        else post(route('consultor.clientes.store'));
    }

    return (
        <AppLayout>
            <Head title={editing ? 'Editar Cliente' : 'Novo Cliente'} />
            <PageHeader
                title={editing ? 'Editar Cliente' : 'Novo Cliente'}
                breadcrumbs={[{ label: 'Clientes', href: route('consultor.clientes.index') }, { label: editing ? 'Editar' : 'Novo' }]}
            />

            <Box component="form" onSubmit={submit}>
                {/* Identificação */}
                <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                    <CardHeader title="Identificação" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                    <Divider />
                    <CardContent>
                        <Grid container spacing={3}>
                            <Grid size={{ xs: 12, sm: 4 }}>
                                <FormControl fullWidth size="small">
                                    <InputLabel>Status</InputLabel>
                                    <Select value={data.status} label="Status" onChange={(e) => setData('status', e.target.value)}>
                                        <MenuItem value="novo">Novo</MenuItem>
                                        <MenuItem value="orcamento_gerado">Orçamento Gerado</MenuItem>
                                        <MenuItem value="visita_agendada">Visita Agendada</MenuItem>
                                        <MenuItem value="finalizado">Finalizado</MenuItem>
                                    </Select>
                                </FormControl>
                            </Grid>
                            <Grid size={{ xs: 12 }}>
                                <Typography variant="body2" color="text.secondary" mb={1}>Tipo de pessoa *</Typography>
                                <ToggleButtonGroup exclusive value={data.tipo_pessoa} onChange={(_, v) => v && setData('tipo_pessoa', v)} size="small">
                                    <ToggleButton value="pf">Pessoa Física</ToggleButton>
                                    <ToggleButton value="pj">Pessoa Jurídica</ToggleButton>
                                </ToggleButtonGroup>
                            </Grid>
                            {data.tipo_pessoa === 'pf' ? (
                                <>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <TextField fullWidth size="small" label="Nome completo" value={data.nome} onChange={(e) => setData('nome', e.target.value)} error={!!errors.nome} helperText={errors.nome} />
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 3 }}>
                                        <TextField fullWidth size="small" label="CPF" value={data.cpf} placeholder="000.000.000-00" inputProps={{ maxLength: 14 }}
                                            onChange={(e) => setData('cpf', maskCpf(e.target.value))} />
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 3 }}>
                                        <TextField fullWidth size="small" label="RG" value={data.rg} onChange={(e) => setData('rg', e.target.value)} />
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 4 }}>
                                        <TextField fullWidth size="small" label="Data de nascimento" type="date" value={data.data_nascimento} onChange={(e) => setData('data_nascimento', e.target.value)} InputLabelProps={{ shrink: true }} />
                                    </Grid>
                                </>
                            ) : (
                                <>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <TextField fullWidth size="small" label="Razão Social" value={data.razao_social} onChange={(e) => setData('razao_social', e.target.value)} error={!!errors.razao_social} helperText={errors.razao_social} />
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 4 }}>
                                        <TextField fullWidth size="small" label="CNPJ" value={data.cnpj} placeholder="00.000.000/0000-00" inputProps={{ maxLength: 18 }}
                                            onChange={(e) => setData('cnpj', maskCnpj(e.target.value))} />
                                    </Grid>
                                </>
                            )}
                        </Grid>
                    </CardContent>
                </Card>

                {/* Contato */}
                <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                    <CardHeader title="Contato" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                    <Divider />
                    <CardContent>
                        <Grid container spacing={3}>
                            <Grid size={{ xs: 12, sm: 4 }}>
                                <TextField fullWidth size="small" label="E-mail" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} error={!!errors.email} helperText={errors.email} />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 4 }}>
                                <TextField fullWidth size="small" label="Telefone" value={data.telefone} placeholder="(00) 0000-0000" inputProps={{ maxLength: 15 }}
                                    onChange={(e) => setData('telefone', maskPhone(e.target.value))} />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 4 }}>
                                <TextField fullWidth size="small" label="Celular" value={data.celular} placeholder="(00) 00000-0000" inputProps={{ maxLength: 16 }}
                                    onChange={(e) => setData('celular', maskPhone(e.target.value))} />
                            </Grid>
                        </Grid>
                    </CardContent>
                </Card>

                {/* Endereço */}
                <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                    <CardHeader title="Endereço" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                    <Divider />
                    <CardContent>
                        <EnderecoFields
                            data={data}
                            setCampo={(campo, valor) => setData(campo, valor)}
                            errors={errors}
                            siglaInicial={cliente?.cidade?.sigla}
                        />
                    </CardContent>
                </Card>

                {/* Anotações */}
                <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                    <CardHeader title="Anotações" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                    <Divider />
                    <CardContent>
                        <TextField fullWidth multiline rows={4} size="small" label="Anotações" value={data.anotacoes} onChange={(e) => setData('anotacoes', e.target.value)} />
                    </CardContent>
                </Card>

                <Box sx={{ display: 'flex', gap: 2, justifyContent: 'flex-end' }}>
                    <Button component={Link} href={route('consultor.clientes.index')} variant="outlined">Cancelar</Button>
                    <Button type="submit" variant="contained" startIcon={<SaveRoundedIcon />} disabled={processing}>
                        {editing ? 'Salvar alterações' : 'Criar cliente'}
                    </Button>
                </Box>
            </Box>
        </AppLayout>
    );
}
