import React from 'react';
import { Box, Button, CircularProgress, TextField, Typography } from '@mui/material';
import { Head, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({ password: '' });
    const handleSubmit = (e: React.FormEvent) => { e.preventDefault(); post(route('password.confirm'), { onFinish: () => reset('password') }); };
    return (
        <GuestLayout>
            <Head title="Confirmar Senha" />
            <Typography variant="h5" fontWeight={700} sx={{ mb: 1 }}>Confirmar Senha</Typography>
            <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>Esta é uma área segura. Por favor confirme sua senha antes de continuar.</Typography>
            <Box component="form" onSubmit={handleSubmit} noValidate>
                <TextField label="Senha" type="password" fullWidth value={data.password} onChange={(e) => setData('password', e.target.value)} error={Boolean(errors.password)} helperText={errors.password} autoFocus sx={{ mb: 2.5 }} />
                <Button type="submit" variant="contained" fullWidth size="large" disabled={processing} sx={{ py: 1.5, fontWeight: 700 }}>
                    {processing ? <CircularProgress size={22} sx={{ color: 'white' }} /> : 'Confirmar'}
                </Button>
            </Box>
        </GuestLayout>
    );
}
