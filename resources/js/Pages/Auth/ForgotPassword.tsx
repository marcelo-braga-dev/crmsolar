import React from 'react';
import { Alert, Box, Button, CircularProgress, TextField, Typography } from '@mui/material';
import { Head, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({ email: '' });
    const handleSubmit = (e: React.FormEvent) => { e.preventDefault(); post(route('password.email')); };
    return (
        <GuestLayout>
            <Head title="Recuperar Senha" />
            <Typography variant="h5" fontWeight={700} sx={{ mb: 0.5 }}>Recuperar Senha</Typography>
            <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>Informe seu e-mail para receber o link de redefinição.</Typography>
            {status && <Alert severity="success" sx={{ mb: 2, borderRadius: 2 }}>{status}</Alert>}
            <Box component="form" onSubmit={handleSubmit} noValidate>
                <TextField label="E-mail" type="email" fullWidth value={data.email} onChange={(e) => setData('email', e.target.value)} error={Boolean(errors.email)} helperText={errors.email} autoFocus sx={{ mb: 2.5 }} />
                <Button type="submit" variant="contained" fullWidth size="large" disabled={processing} sx={{ py: 1.5, fontWeight: 700 }}>
                    {processing ? <CircularProgress size={22} sx={{ color: 'white' }} /> : 'Enviar Link'}
                </Button>
            </Box>
        </GuestLayout>
    );
}
