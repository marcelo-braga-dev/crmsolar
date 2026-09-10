import React from 'react';
import {
    Alert,
    Box,
    Button,
    Checkbox,
    CircularProgress,
    Divider,
    FormControlLabel,
    IconButton,
    InputAdornment,
    TextField,
    Typography,
} from '@mui/material';
import VisibilityRoundedIcon from '@mui/icons-material/VisibilityRounded';
import VisibilityOffRoundedIcon from '@mui/icons-material/VisibilityOffRounded';
import EmailRoundedIcon from '@mui/icons-material/EmailRounded';
import LockRoundedIcon from '@mui/icons-material/LockRounded';
import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';

export default function Login({
    status,
    canResetPassword,
}: {
    status?: string;
    canResetPassword: boolean;
}) {
    const [showPassword, setShowPassword] = React.useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false as boolean,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('login'), { onFinish: () => reset('password') });
    };

    return (
        <GuestLayout>
            <Head title="Entrar" />

            <Typography variant="h5" fontWeight={700} sx={{ mb: 0.5, color: 'text.primary' }}>
                Bem-vindo de volta
            </Typography>
            <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
                Entre com suas credenciais para acessar a plataforma
            </Typography>

            {status && (
                <Alert severity="success" sx={{ mb: 2, borderRadius: 2 }}>
                    {status}
                </Alert>
            )}

            <Box component="form" onSubmit={handleSubmit} noValidate>
                <TextField
                    label="E-mail"
                    type="email"
                    fullWidth
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    error={Boolean(errors.email)}
                    helperText={errors.email}
                    autoComplete="email"
                    autoFocus
                    sx={{ mb: 2 }}
                    slotProps={{
                        input: {
                            startAdornment: (
                                <InputAdornment position="start">
                                    <EmailRoundedIcon fontSize="small" color="action" />
                                </InputAdornment>
                            ),
                        },
                    }}
                />

                <TextField
                    label="Senha"
                    type={showPassword ? 'text' : 'password'}
                    fullWidth
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    error={Boolean(errors.password)}
                    helperText={errors.password}
                    autoComplete="current-password"
                    sx={{ mb: 1 }}
                    slotProps={{
                        input: {
                            startAdornment: (
                                <InputAdornment position="start">
                                    <LockRoundedIcon fontSize="small" color="action" />
                                </InputAdornment>
                            ),
                            endAdornment: (
                                <InputAdornment position="end">
                                    <IconButton
                                        onClick={() => setShowPassword((v) => !v)}
                                        edge="end"
                                        size="small"
                                    >
                                        {showPassword ? (
                                            <VisibilityOffRoundedIcon fontSize="small" />
                                        ) : (
                                            <VisibilityRoundedIcon fontSize="small" />
                                        )}
                                    </IconButton>
                                </InputAdornment>
                            ),
                        },
                    }}
                />

                <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', mb: 2.5 }}>
                    <FormControlLabel
                        control={
                            <Checkbox
                                size="small"
                                checked={data.remember}
                                onChange={(e) => setData('remember', e.target.checked as false)}
                            />
                        }
                        label={<Typography variant="body2">Lembrar-me</Typography>}
                    />
                    {canResetPassword && (
                        <Link href={route('password.request')} style={{ textDecoration: 'none' }}>
                            <Typography variant="body2" color="primary" sx={{ '&:hover': { textDecoration: 'underline' } }}>
                                Esqueceu a senha?
                            </Typography>
                        </Link>
                    )}
                </Box>

                <Button
                    type="submit"
                    variant="contained"
                    fullWidth
                    size="large"
                    disabled={processing}
                    sx={{ py: 1.5, fontWeight: 700, fontSize: '0.95rem' }}
                >
                    {processing ? (
                        <CircularProgress size={22} sx={{ color: 'white' }} />
                    ) : (
                        'Entrar'
                    )}
                </Button>
            </Box>
        </GuestLayout>
    );
}
