import React from 'react';
import { Box, Button, CircularProgress, TextField, Typography } from '@mui/material';
import { Head, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';

export default function ResetPassword({ token, email }: { token: string; email: string }) {
    const { data, setData, post, processing, errors, reset } = useForm({ token, email, password: '', password_confirmation: '' });
    const handleSubmit = (e: React.FormEvent) => { e.preventDefault(); post(route('password.store'), { onFinish: () => reset('password', 'password_confirmation') }); };
    return (
        <GuestLayout>
            <Head title="Nova Senha" />
            <Typography variant="h5" fontWeight={700} sx={{ mb: 3 }}>Redefinir Senha</Typography>
            <Box component="form" onSubmit={handleSubmit} noValidate sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
                <TextField label="E-mail" type="email" fullWidth value={data.email} onChange={(e) => setData('email', e.target.value)} error={Boolean(errors.email)} helperText={errors.email} />
                <TextField label="Nova Senha" type="password" fullWidth value={data.password} onChange={(e) => setData('password', e.target.value)} error={Boolean(errors.password)} helperText={errors.password} />
                <TextField label="Confirmar Nova Senha" type="password" fullWidth value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} error={Boolean(errors.password_confirmation)} helperText={errors.password_confirmation} />
                <Button type="submit" variant="contained" fullWidth size="large" disabled={processing} sx={{ py: 1.5, fontWeight: 700 }}>
                    {processing ? <CircularProgress size={22} sx={{ color: 'white' }} /> : 'Salvar Nova Senha'}
                </Button>
            </Box>
        </GuestLayout>
    );
}
