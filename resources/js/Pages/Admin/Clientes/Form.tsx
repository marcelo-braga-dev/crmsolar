import React from 'react';
import {
    Box,
    Button,
    Card,
    CardContent,
    CardHeader,
    Divider,
    FormControl,
    FormHelperText,
    Grid,
    InputLabel,
    MenuItem,
    Select,
    TextField,
    ToggleButton,
    ToggleButtonGroup,
    Typography,
} from '@mui/material';
import SaveRoundedIcon from '@mui/icons-material/SaveRounded';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { PageProps } from '@/types';

interface Consultor {
    id: number;
    name: string;
}

interface ClienteData {
    id?: number;
    consultor_id: string;
    cidade_id: string;
    tipo_pessoa: 'pf' | 'pj';
    nome: string;
    razao_social: string;
    cpf: string;
    cnpj: string;
    rg: string;
    data_nascimento: string;
    email: string;
    telefone: string;
    celular: string;
    cep: string;
    rua: string;
    numero: string;
    complemento: string;
    bairro: string;
    status: string;
    anotacoes: string;
}

interface Props extends PageProps {
    cliente?: ClienteData;
    consultores: Consultor[];
}

export default function ClientesForm({ cliente, consultores }: Props) {
    const editing = !!cliente?.id;

    const { data, setData, post, put, processing, errors } = useForm<ClienteData>({
        consultor_id: String(cliente?.consultor_id ?? ''),
        cidade_id: String(cliente?.cidade_id ?? ''),
        tipo_pessoa: cliente?.tipo_pessoa ?? 'pf',
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
        if (editing) {
            put(route('admin.clientes.update', cliente!.id));
        } else {
            post(route('admin.clientes.store'));
        }
    }

    return (
        <AppLayout>
            <Head title={editing ? 'Editar Cliente' : 'Novo Cliente'} />

            <PageHeader
                title={editing ? 'Editar Cliente' : 'Novo Cliente'}
                breadcrumbs={[
                    { label: 'Clientes', href: route('admin.clientes.index') },
                    { label: editing ? 'Editar' : 'Novo' },
                ]}
            />

            <Box component="form" onSubmit={submit}>
                {/* Tipo e Responsável */}
                <Card sx={{ mb: 3 }}>
                    <CardHeader title="Identificação" />
                    <Divider />
                    <CardContent>
                        <Grid container spacing={3}>
                            <Grid size={{ xs: 12, sm: 6 }}>
                                <FormControl fullWidth size="small" error={!!errors.consultor_id}>
                                    <InputLabel>Vendedor responsável *</InputLabel>
                                    <Select
                                        value={data.consultor_id}
                                        label="Vendedor responsável *"
                                        onChange={(e) => setData('consultor_id', e.target.value)}
                                    >
                                        {consultores.map((c) => (
                                            <MenuItem key={c.id} value={String(c.id)}>{c.name}</MenuItem>
                                        ))}
                                    </Select>
                                    {errors.consultor_id && <FormHelperText>{errors.consultor_id}</FormHelperText>}
                                </FormControl>
                            </Grid>

                            <Grid size={{ xs: 12, sm: 6 }}>
                                <FormControl fullWidth size="small" error={!!errors.status}>
                                    <InputLabel>Status *</InputLabel>
                                    <Select
                                        value={data.status}
                                        label="Status *"
                                        onChange={(e) => setData('status', e.target.value)}
                                    >
                                        <MenuItem value="novo">Novo</MenuItem>
                                        <MenuItem value="orcamento_gerado">Orçamento Gerado</MenuItem>
                                        <MenuItem value="visita_agendada">Visita Agendada</MenuItem>
                                        <MenuItem value="finalizado">Finalizado</MenuItem>
                                    </Select>
                                </FormControl>
                            </Grid>

                            <Grid size={{ xs: 12 }}>
                                <Typography variant="body2" color="text.secondary" mb={1}>
                                    Tipo de pessoa *
                                </Typography>
                                <ToggleButtonGroup
                                    exclusive
                                    value={data.tipo_pessoa}
                                    onChange={(_, v) => v && setData('tipo_pessoa', v)}
                                    size="small"
                                >
                                    <ToggleButton value="pf">Pessoa Física</ToggleButton>
                                    <ToggleButton value="pj">Pessoa Jurídica</ToggleButton>
                                </ToggleButtonGroup>
                            </Grid>

                            {data.tipo_pessoa === 'pf' ? (
                                <>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <TextField
                                            fullWidth size="small"
                                            label="Nome completo"
                                            value={data.nome}
                                            onChange={(e) => setData('nome', e.target.value)}
                                            error={!!errors.nome}
                                            helperText={errors.nome}
                                        />
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 3 }}>
                                        <TextField
                                            fullWidth size="small"
                                            label="CPF"
                                            value={data.cpf}
                                            onChange={(e) => setData('cpf', e.target.value)}
                                            placeholder="000.000.000-00"
                                        />
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 3 }}>
                                        <TextField
                                            fullWidth size="small"
                                            label="RG"
                                            value={data.rg}
                                            onChange={(e) => setData('rg', e.target.value)}
                                        />
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 4 }}>
                                        <TextField
                                            fullWidth size="small"
                                            label="Data de nascimento"
                                            type="date"
                                            value={data.data_nascimento}
                                            onChange={(e) => setData('data_nascimento', e.target.value)}
                                            InputLabelProps={{ shrink: true }}
                                        />
                                    </Grid>
                                </>
                            ) : (
                                <>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <TextField
                                            fullWidth size="small"
                                            label="Razão social"
                                            value={data.razao_social}
                                            onChange={(e) => setData('razao_social', e.target.value)}
                                            error={!!errors.razao_social}
                                            helperText={errors.razao_social}
                                        />
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 4 }}>
                                        <TextField
                                            fullWidth size="small"
                                            label="CNPJ"
                                            value={data.cnpj}
                                            onChange={(e) => setData('cnpj', e.target.value)}
                                            placeholder="00.000.000/0000-00"
                                        />
                                    </Grid>
                                </>
                            )}
                        </Grid>
                    </CardContent>
                </Card>

                {/* Contato */}
                <Card sx={{ mb: 3 }}>
                    <CardHeader title="Contato" />
                    <Divider />
                    <CardContent>
                        <Grid container spacing={3}>
                            <Grid size={{ xs: 12, sm: 4 }}>
                                <TextField
                                    fullWidth size="small"
                                    label="E-mail"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    error={!!errors.email}
                                    helperText={errors.email}
                                />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 4 }}>
                                <TextField
                                    fullWidth size="small"
                                    label="Telefone"
                                    value={data.telefone}
                                    onChange={(e) => setData('telefone', e.target.value)}
                                />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 4 }}>
                                <TextField
                                    fullWidth size="small"
                                    label="Celular"
                                    value={data.celular}
                                    onChange={(e) => setData('celular', e.target.value)}
                                />
                            </Grid>
                        </Grid>
                    </CardContent>
                </Card>

                {/* Endereço */}
                <Card sx={{ mb: 3 }}>
                    <CardHeader title="Endereço" />
                    <Divider />
                    <CardContent>
                        <Grid container spacing={3}>
                            <Grid size={{ xs: 12, sm: 2 }}>
                                <TextField
                                    fullWidth size="small"
                                    label="CEP"
                                    value={data.cep}
                                    onChange={(e) => setData('cep', e.target.value)}
                                    placeholder="00000-000"
                                />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 5 }}>
                                <TextField
                                    fullWidth size="small"
                                    label="Rua"
                                    value={data.rua}
                                    onChange={(e) => setData('rua', e.target.value)}
                                />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 2 }}>
                                <TextField
                                    fullWidth size="small"
                                    label="Número"
                                    value={data.numero}
                                    onChange={(e) => setData('numero', e.target.value)}
                                />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 3 }}>
                                <TextField
                                    fullWidth size="small"
                                    label="Complemento"
                                    value={data.complemento}
                                    onChange={(e) => setData('complemento', e.target.value)}
                                />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 4 }}>
                                <TextField
                                    fullWidth size="small"
                                    label="Bairro"
                                    value={data.bairro}
                                    onChange={(e) => setData('bairro', e.target.value)}
                                />
                            </Grid>
                        </Grid>
                    </CardContent>
                </Card>

                {/* Anotações */}
                <Card sx={{ mb: 3 }}>
                    <CardHeader title="Anotações" />
                    <Divider />
                    <CardContent>
                        <TextField
                            fullWidth
                            multiline
                            rows={4}
                            size="small"
                            label="Anotações internas"
                            value={data.anotacoes}
                            onChange={(e) => setData('anotacoes', e.target.value)}
                        />
                    </CardContent>
                </Card>

                <Box sx={{ display: 'flex', gap: 2, justifyContent: 'flex-end' }}>
                    <Button
                        component={Link}
                        href={route('admin.clientes.index')}
                        variant="outlined"
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="submit"
                        variant="contained"
                        startIcon={<SaveRoundedIcon />}
                        disabled={processing}
                    >
                        {editing ? 'Salvar alterações' : 'Criar cliente'}
                    </Button>
                </Box>
            </Box>
        </AppLayout>
    );
}
