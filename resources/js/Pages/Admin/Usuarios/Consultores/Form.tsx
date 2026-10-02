import React from 'react';
import {
    Box,
    Button,
    Card,
    CardContent,
    CardHeader,
    Divider,
    FormControl,
    FormControlLabel,
    FormHelperText,
    Grid,
    InputLabel,
    MenuItem,
    Select,
    Switch,
    TextField,
} from '@mui/material';
import SaveRoundedIcon from '@mui/icons-material/SaveRounded';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { PageProps } from '@/types';

interface ConsultorData {
    id?: number;
    name: string;
    email: string;
    tipo: 'consultor' | 'admin_consultor';
    cpf: string;
    rg: string;
    celular: string;
    comissao_percentual: string;
    status: boolean;
}

interface FormData {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    tipo: string;
    cpf: string;
    rg: string;
    celular: string;
    comissao_percentual: string;
    status: boolean;
}

interface Props extends PageProps {
    consultor?: ConsultorData;
}

export default function ConsultoresForm({ consultor }: Props) {
    const editing = !!consultor?.id;

    const { data, setData, post, put, processing, errors } = useForm<FormData>({
        name: consultor?.name ?? '',
        email: consultor?.email ?? '',
        password: '',
        password_confirmation: '',
        tipo: consultor?.tipo ?? 'consultor',
        cpf: consultor?.cpf ?? '',
        rg: consultor?.rg ?? '',
        celular: consultor?.celular ?? '',
        comissao_percentual: consultor?.comissao_percentual ?? '',
        status: consultor?.status ?? true,
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (editing) {
            put(route('admin.usuarios.consultores.update', consultor!.id));
        } else {
            post(route('admin.usuarios.consultores.store'));
        }
    }

    return (
        <AppLayout>
            <Head title={editing ? 'Editar Consultor' : 'Novo Consultor'} />

            <PageHeader
                title={editing ? 'Editar Consultor' : 'Novo Consultor'}
                breadcrumbs={[
                    { label: 'Usuários' },
                    { label: 'Consultores', href: route('admin.usuarios.consultores.index') },
                    { label: editing ? 'Editar' : 'Novo' },
                ]}
            />

            <Box component="form" onSubmit={submit}>
                <Card sx={{ mb: 3 }}>
                    <CardHeader title="Dados Pessoais" />
                    <Divider />
                    <CardContent>
                        <Grid container spacing={3}>
                            <Grid size={{ xs: 12, sm: 6 }}>
                                <TextField
                                    fullWidth size="small"
                                    label="Nome completo *"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    error={!!errors.name}
                                    helperText={errors.name}
                                    autoFocus
                                />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 6 }}>
                                <TextField
                                    fullWidth size="small"
                                    label="E-mail *"
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
                                    label="CPF"
                                    value={data.cpf}
                                    onChange={(e) => setData('cpf', e.target.value)}
                                    placeholder="000.000.000-00"
                                    error={!!errors.cpf}
                                    helperText={errors.cpf}
                                />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 4 }}>
                                <TextField
                                    fullWidth size="small"
                                    label="RG"
                                    value={data.rg}
                                    onChange={(e) => setData('rg', e.target.value)}
                                    error={!!errors.rg}
                                    helperText={errors.rg}
                                />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 4 }}>
                                <TextField
                                    fullWidth size="small"
                                    label="Celular"
                                    value={data.celular}
                                    onChange={(e) => setData('celular', e.target.value)}
                                    placeholder="(00) 90000-0000"
                                    error={!!errors.celular}
                                    helperText={errors.celular}
                                />
                            </Grid>
                        </Grid>
                    </CardContent>
                </Card>

                <Card sx={{ mb: 3 }}>
                    <CardHeader title="Configurações de Acesso" />
                    <Divider />
                    <CardContent>
                        <Grid container spacing={3}>
                            <Grid size={{ xs: 12, sm: 4 }}>
                                <FormControl fullWidth size="small" error={!!errors.tipo}>
                                    <InputLabel>Tipo de acesso *</InputLabel>
                                    <Select
                                        value={data.tipo}
                                        label="Tipo de acesso *"
                                        onChange={(e) => setData('tipo', e.target.value)}
                                    >
                                        <MenuItem value="consultor">Consultor</MenuItem>
                                    </Select>
                                    {errors.tipo && <FormHelperText>{errors.tipo}</FormHelperText>}
                                </FormControl>
                            </Grid>
                            <Grid size={{ xs: 12, sm: 4 }}>
                                <TextField
                                    fullWidth size="small"
                                    label="Comissão (%)"
                                    type="number"
                                    value={data.comissao_percentual}
                                    onChange={(e) => setData('comissao_percentual', e.target.value)}
                                    inputProps={{ step: '0.01', min: '0', max: '100' }}
                                    error={!!errors.comissao_percentual}
                                    helperText={errors.comissao_percentual}
                                />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 4 }}>
                                <FormControlLabel
                                    control={
                                        <Switch
                                            checked={data.status}
                                            onChange={(e) => setData('status', e.target.checked)}
                                        />
                                    }
                                    label="Conta ativa"
                                />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 6 }}>
                                <TextField
                                    fullWidth size="small"
                                    label={editing ? 'Nova senha (deixe vazio para não alterar)' : 'Senha *'}
                                    type="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    error={!!errors.password}
                                    helperText={errors.password}
                                />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 6 }}>
                                <TextField
                                    fullWidth size="small"
                                    label="Confirmar senha"
                                    type="password"
                                    value={data.password_confirmation}
                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                />
                            </Grid>
                        </Grid>
                    </CardContent>
                </Card>

                <Box sx={{ display: 'flex', gap: 2, justifyContent: 'flex-end' }}>
                    <Button component={Link} href={route('admin.usuarios.consultores.index')} variant="outlined">
                        Cancelar
                    </Button>
                    <Button type="submit" variant="contained" startIcon={<SaveRoundedIcon />} disabled={processing}>
                        {editing ? 'Salvar alterações' : 'Criar consultor'}
                    </Button>
                </Box>
            </Box>
        </AppLayout>
    );
}
