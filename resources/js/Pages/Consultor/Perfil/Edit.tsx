import React from 'react';
import {
    Box, Button, Card, CardContent, CardHeader, Divider, Grid, TextField,
} from '@mui/material';
import SaveRoundedIcon from '@mui/icons-material/SaveRounded';
import LockRoundedIcon from '@mui/icons-material/LockRounded';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { PageProps } from '@/types';

interface UserData {
    id: number;
    name: string;
    email: string;
    cpf?: string;
    rg?: string;
    celular?: string;
}

interface Props extends PageProps { user: UserData }

export default function PerfilEdit({ user }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: user.name,
        email: user.email,
        cpf: user.cpf ?? '',
        rg: user.rg ?? '',
        celular: user.celular ?? '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        put(route('consultor.perfil.update'));
    }

    return (
        <AppLayout>
            <Head title="Meu Perfil" />
            <PageHeader
                title="Meu Perfil"
                breadcrumbs={[{ label: 'Perfil' }]}
                action={
                    <Button component={Link} href={route('consultor.perfil.senha')} variant="outlined" startIcon={<LockRoundedIcon />}>
                        Alterar Senha
                    </Button>
                }
            />

            <Card component="form" onSubmit={handleSubmit}>
                <CardHeader title="Dados pessoais" />
                <Divider />
                <CardContent>
                    <Grid container spacing={3}>
                        <Grid size={{ xs: 12, sm: 6 }}>
                            <TextField label="Nome *" value={data.name} onChange={(e) => setData('name', e.target.value)}
                                error={!!errors.name} helperText={errors.name} fullWidth />
                        </Grid>
                        <Grid size={{ xs: 12, sm: 6 }}>
                            <TextField label="E-mail *" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)}
                                error={!!errors.email} helperText={errors.email} fullWidth />
                        </Grid>
                        <Grid size={{ xs: 12, sm: 4 }}>
                            <TextField label="CPF" value={data.cpf} onChange={(e) => setData('cpf', e.target.value)} fullWidth />
                        </Grid>
                        <Grid size={{ xs: 12, sm: 4 }}>
                            <TextField label="RG" value={data.rg} onChange={(e) => setData('rg', e.target.value)} fullWidth />
                        </Grid>
                        <Grid size={{ xs: 12, sm: 4 }}>
                            <TextField label="Celular" value={data.celular} onChange={(e) => setData('celular', e.target.value)} fullWidth />
                        </Grid>
                    </Grid>
                </CardContent>
                <Divider />
                <Box sx={{ display: 'flex', justifyContent: 'flex-end', p: 2 }}>
                    <Button type="submit" variant="contained" startIcon={<SaveRoundedIcon />} disabled={processing}>
                        Salvar
                    </Button>
                </Box>
            </Card>
        </AppLayout>
    );
}
