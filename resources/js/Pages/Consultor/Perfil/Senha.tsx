import React from 'react';
import {
    Box, Button, Card, CardContent, CardHeader, Divider, Grid, TextField,
} from '@mui/material';
import SaveRoundedIcon from '@mui/icons-material/SaveRounded';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { PageProps } from '@/types';

export default function PerfilSenha(_: PageProps) {
    const { data, setData, put, processing, errors, reset } = useForm({
        senha_atual: '',
        password: '',
        password_confirmation: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        put(route('consultor.perfil.senha.update'), {
            onSuccess: () => reset(),
        });
    }

    return (
        <AppLayout>
            <Head title="Alterar Senha" />
            <PageHeader title="Alterar Senha" breadcrumbs={[{ label: 'Perfil', href: route('consultor.perfil.edit') }, { label: 'Senha' }]} />

            <Card component="form" onSubmit={handleSubmit} sx={{ maxWidth: 480 }}>
                <CardHeader title="Trocar senha" subheader="Mínimo 8 caracteres" />
                <Divider />
                <CardContent>
                    <Grid container spacing={3}>
                        <Grid size={{ xs: 12 }}>
                            <TextField label="Senha atual *" type="password" value={data.senha_atual}
                                onChange={(e) => setData('senha_atual', e.target.value)}
                                error={!!errors.senha_atual} helperText={errors.senha_atual} fullWidth autoFocus />
                        </Grid>
                        <Grid size={{ xs: 12 }}>
                            <TextField label="Nova senha *" type="password" value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                error={!!errors.password} helperText={errors.password} fullWidth />
                        </Grid>
                        <Grid size={{ xs: 12 }}>
                            <TextField label="Confirmar nova senha *" type="password" value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)} fullWidth />
                        </Grid>
                    </Grid>
                </CardContent>
                <Divider />
                <Box sx={{ display: 'flex', justifyContent: 'flex-end', p: 2 }}>
                    <Button type="submit" variant="contained" startIcon={<SaveRoundedIcon />} disabled={processing}>
                        Alterar Senha
                    </Button>
                </Box>
            </Card>
        </AppLayout>
    );
}
