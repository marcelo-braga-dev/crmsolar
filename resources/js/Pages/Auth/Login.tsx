import React from 'react';
import {
    Alert,
    Box,
    Button,
    Checkbox,
    CircularProgress,
    FormControlLabel,
    IconButton,
    InputAdornment,
    TextField,
    Typography,
} from '@mui/material';
import VisibilityRoundedIcon from '@mui/icons-material/VisibilityRounded';
import VisibilityOffRoundedIcon from '@mui/icons-material/VisibilityOffRounded';
import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';

const labelSx = {
    display: 'block',
    mb: 0.75,
    fontSize: '0.8125rem',
    fontWeight: 600,
    color: 'text.primary',
} as const;

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

            <Typography variant="h5" sx={{ fontWeight: 700, letterSpacing: '-0.02em', mb: 0.5 }}>
                Entrar
            </Typography>
            <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
                Acesse sua conta para continuar.
            </Typography>

            {status && (
                <Alert severity="success" sx={{ mb: 2.5, borderRadius: 2 }}>
                    {status}
                </Alert>
            )}

            <Box component="form" onSubmit={handleSubmit} noValidate>
                <Box sx={{ mb: 2 }}>
                    <Typography component="label" htmlFor="email" sx={labelSx}>
                        E-mail
                    </Typography>
                    <TextField
                        id="email"
                        type="email"
                        placeholder="voce@empresa.com"
                        fullWidth
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        error={Boolean(errors.email)}
                        helperText={errors.email}
                        autoComplete="email"
                        autoFocus
                    />
                </Box>

                <Box sx={{ mb: 1.5 }}>
                    <Box
                        sx={{
                            display: 'flex',
                            alignItems: 'baseline',
                            justifyContent: 'space-between',
                            gap: 2,
                        }}
                    >
                        <Typography component="label" htmlFor="password" sx={labelSx}>
                            Senha
                        </Typography>
                        {canResetPassword && (
                            <Typography
                                component={Link}
                                href={route('password.request')}
                                variant="caption"
                                sx={{
                                    fontWeight: 600,
                                    color: 'primary.main',
                                    textDecoration: 'none',
                                    '&:hover': { textDecoration: 'underline' },
                                }}
                            >
                                Esqueceu a senha?
                            </Typography>
                        )}
                    </Box>
                    <TextField
                        id="password"
                        type={showPassword ? 'text' : 'password'}
                        placeholder="••••••••"
                        fullWidth
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        error={Boolean(errors.password)}
                        helperText={errors.password}
                        autoComplete="current-password"
                        slotProps={{
                            input: {
                                endAdornment: (
                                    <InputAdornment position="end">
                                        <IconButton
                                            onClick={() => setShowPassword((v) => !v)}
                                            edge="end"
                                            size="small"
                                            aria-label={showPassword ? 'Ocultar senha' : 'Mostrar senha'}
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
                </Box>

                <FormControlLabel
                    sx={{ mb: 2.5 }}
                    control={
                        <Checkbox
                            size="small"
                            checked={data.remember}
                            onChange={(e) => setData('remember', e.target.checked as false)}
                        />
                    }
                    label={
                        <Typography variant="body2" color="text.secondary">
                            Manter conectado
                        </Typography>
                    }
                />

                <Button
                    type="submit"
                    variant="contained"
                    fullWidth
                    size="large"
                    disabled={processing}
                    sx={{ py: 1.35, fontWeight: 700 }}
                >
                    {processing ? <CircularProgress size={22} sx={{ color: 'white' }} /> : 'Entrar'}
                </Button>
            </Box>
        </GuestLayout>
    );
}
