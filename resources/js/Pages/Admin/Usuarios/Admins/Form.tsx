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

interface AdminData {
    id?: number;
    name: string;
    email: string;
    cpf?: string;
    celular?: string;
    status: boolean;
}

interface FormData {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    cpf: string;
    celular: string;
    status: boolean;
}

interface Props extends PageProps {
    admin?: AdminData;
}

export default function AdminsForm({ admin }: Props) {
    const editing = !!admin?.id;

    const { data, setData, post, put, processing, errors } = useForm<FormData>({
        name: admin?.name ?? '',
        email: admin?.email ?? '',
        password: '',
        password_confirmation: '',
        cpf: admin?.cpf ?? '',
        celular: admin?.celular ?? '',
        status: admin?.status ?? true,
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (editing) {
            put(route('admin.usuarios.admins.update', admin!.id));
        } else {
            post(route('admin.usuarios.admins.store'));
        }
    }

    return (
        <AppLayout>
            <Head title={editing ? 'Editar Admin' : 'Novo Admin'} />

            <PageHeader
                title={editing ? 'Editar Admin' : 'Novo Admin'}
                breadcrumbs={[
                    { label: 'Usuários' },
                    { label: 'Admins', href: route('admin.usuarios.admins.index') },
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
                                    label="Celular"
                                    value={data.celular}
                                    onChange={(e) => setData('celular', e.target.value)}
                                    placeholder="(00) 90000-0000"
                                    error={!!errors.celular}
                                    helperText={errors.celular}
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
                        </Grid>
                    </CardContent>
                </Card>

                <Card sx={{ mb: 3 }}>
                    <CardHeader title="Senha de Acesso" />
                    <Divider />
                    <CardContent>
                        <Grid container spacing={3}>
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
                    <Button component={Link} href={route('admin.usuarios.admins.index')} variant="outlined">
                        Cancelar
                    </Button>
                    <Button type="submit" variant="contained" startIcon={<SaveRoundedIcon />} disabled={processing}>
                        {editing ? 'Salvar alterações' : 'Criar admin'}
                    </Button>
                </Box>
            </Box>
        </AppLayout>
    );
}
