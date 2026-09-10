import React from 'react';
import { Alert, Box, Button, CircularProgress, Typography } from '@mui/material';
import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';

export default function VerifyEmail({ status }: { status?: string }) {
    const { post, processing } = useForm({});
    const handleSubmit = (e: React.FormEvent) => { e.preventDefault(); post(route('verification.send')); };
    return (
        <GuestLayout>
            <Head title="Verificar E-mail" />
            <Typography variant="h5" fontWeight={700} sx={{ mb: 1 }}>Verifique seu E-mail</Typography>
            <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>Enviamos um link de verificação. Caso não tenha recebido, clique abaixo para reenviar.</Typography>
            {status === 'verification-link-sent' && <Alert severity="success" sx={{ mb: 2, borderRadius: 2 }}>Novo link enviado!</Alert>}
            <Box component="form" onSubmit={handleSubmit} noValidate>
                <Button type="submit" variant="contained" fullWidth disabled={processing} sx={{ mb: 2, fontWeight: 700 }}>
                    {processing ? <CircularProgress size={22} sx={{ color: 'white' }} /> : 'Reenviar E-mail'}
                </Button>
            </Box>
            <Link href={route('logout')} method="post" as="button" style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#64748B', fontSize: '0.875rem' }}>
                Sair
            </Link>
        </GuestLayout>
    );
}
