import React from 'react';
import { Box, Button, CircularProgress, TextField, Typography, Link as MuiLink } from '@mui/material';
import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({ name: '', email: '', password: '', password_confirmation: '' });
    const handleSubmit = (e: React.FormEvent) => { e.preventDefault(); post(route('register'), { onFinish: () => reset('password', 'password_confirmation') }); };
    return (
        <GuestLayout>
            <Head title="Criar Conta" />
            <Typography variant="h5" fontWeight={700} sx={{ mb: 0.5 }}>Criar Conta</Typography>
            <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>Preencha os dados abaixo para criar sua conta.</Typography>
            <Box component="form" onSubmit={handleSubmit} noValidate sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
                <TextField label="Nome completo" fullWidth value={data.name} onChange={(e) => setData('name', e.target.value)} error={Boolean(errors.name)} helperText={errors.name} autoFocus />
                <TextField label="E-mail" type="email" fullWidth value={data.email} onChange={(e) => setData('email', e.target.value)} error={Boolean(errors.email)} helperText={errors.email} />
                <TextField label="Senha" type="password" fullWidth value={data.password} onChange={(e) => setData('password', e.target.value)} error={Boolean(errors.password)} helperText={errors.password} />
                <TextField label="Confirmar Senha" type="password" fullWidth value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} error={Boolean(errors.password_confirmation)} helperText={errors.password_confirmation} />
                <Button type="submit" variant="contained" fullWidth size="large" disabled={processing} sx={{ py: 1.5, fontWeight: 700 }}>
                    {processing ? <CircularProgress size={22} sx={{ color: 'white' }} /> : 'Criar Conta'}
                </Button>
                <Typography variant="body2" align="center" color="text.secondary">
                    Já tem conta? <MuiLink component={Link} href={route('login')}>Entrar</MuiLink>
                </Typography>
            </Box>
        </GuestLayout>
    );
}
