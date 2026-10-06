import React from 'react';
import {
    Alert, Avatar, Box, Button, Checkbox, CircularProgress, FormControlLabel, FormHelperText, Paper, TextField, Typography, alpha, useTheme,
} from '@mui/material';
import { Head, useForm } from '@inertiajs/react';
import SwapHorizRoundedIcon from '@mui/icons-material/SwapHorizRounded';
import QueryStatsRoundedIcon from '@mui/icons-material/QueryStatsRounded';
import LockRoundedIcon from '@mui/icons-material/LockRounded';
import ElectricBoltRoundedIcon from '@mui/icons-material/ElectricBoltRounded';
import { MaskedTextField } from '@/Components/UI/MaskedTextField';
import { useIdentidade } from '@/hooks/useIdentidade';

interface Props {
    utm: { utm_source?: string; utm_medium?: string; utm_campaign?: string };
}

const DESTAQUES = [
    { icone: <SwapHorizRoundedIcon />, titulo: 'Troque de perfil com um clique', texto: 'Veja a plataforma como administrador e como consultor.' },
    { icone: <QueryStatsRoundedIcon />, titulo: 'Base com meses de operação simulada', texto: 'Funil, orçamentos, contratos, comissões e relatórios cheios.' },
    { icone: <LockRoundedIcon />, titulo: 'Acesso de teste', texto: 'Navegue à vontade: nada do que você fizer altera a plataforma.' },
];

/** Acesso ao modo demonstração (substitui o login com senha quando DEMO_MODE está ligado). */
export default function DemoAcesso({ utm }: Props) {
    const identidade = useIdentidade();
    const tema = useTheme();
    const { data, setData, post, processing, errors } = useForm({
        nome: '',
        email: '',
        telefone: '',
        empresa: '',
        aceite: false,
        utm_source: utm.utm_source ?? '',
        utm_medium: utm.utm_medium ?? '',
        utm_campaign: utm.utm_campaign ?? '',
    });

    const enviar = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('demo.acesso'));
    };

    const logo = identidade.logo_clara_url ?? identidade.logo_url;

    return (
        <Box
            data-demo-allow
            sx={{
                minHeight: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center', px: 2, py: { xs: 3, md: 6 },
                bgcolor: '#F1F5F9',
                backgroundImage: `radial-gradient(60rem 30rem at 50% -10%, ${alpha(tema.palette.primary.main, 0.1)} 0%, transparent 70%)`,
            }}
        >
            <Head title="Demonstração" />
            <Paper
                elevation={0}
                sx={{
                    width: '100%', maxWidth: 960, borderRadius: 4, overflow: 'hidden', border: '1px solid', borderColor: 'divider',
                    display: 'grid', gridTemplateColumns: { xs: '1fr', md: '1.05fr 1fr' },
                    boxShadow: '0 24px 64px -24px rgb(15 23 42 / .25)',
                }}
            >
                {/* Vitrine */}
                <Box
                    sx={{
                        p: { xs: 3, md: 5 }, color: '#F8FAFC',
                        background: `linear-gradient(160deg, ${tema.palette.sidebar.bg} 0%, ${alpha(tema.palette.primary.dark, 0.95)} 140%)`,
                    }}
                >
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, mb: { xs: 3, md: 5 } }}>
                        <Avatar
                            src={identidade.logo_url ?? undefined}
                            alt={identidade.nome}
                            sx={{
                                width: 44, height: 44, bgcolor: identidade.logo_url ? '#FFFFFF' : tema.palette.secondary.main,
                                '& .MuiAvatar-img': { objectFit: 'contain', p: '5px' },
                            }}
                        >
                            <ElectricBoltRoundedIcon sx={{ color: tema.palette.secondary.contrastText }} />
                        </Avatar>
                        <Typography variant="h6" fontWeight={700}>{identidade.nome}</Typography>
                    </Box>

                    <Typography variant="overline" sx={{ color: tema.palette.secondary.light, fontWeight: 700 }}>Demonstração gratuita</Typography>
                    <Typography variant="h4" fontWeight={800} sx={{ mt: 0.5, lineHeight: 1.15, letterSpacing: '-0.02em' }}>
                        Conheça a plataforma por dentro
                    </Typography>
                    <Typography sx={{ mt: 1.5, color: alpha('#F8FAFC', 0.8) }}>
                        CRM completo para empresas de energia solar: do lead ao contrato, com dimensionamento, funil de vendas e comissões.
                    </Typography>

                    <Box sx={{ mt: 4, display: 'flex', flexDirection: 'column', gap: 2.25 }}>
                        {DESTAQUES.map((d) => (
                            <Box key={d.titulo} sx={{ display: 'flex', gap: 1.5 }}>
                                <Box sx={{ width: 38, height: 38, borderRadius: 2, flexShrink: 0, display: 'flex', alignItems: 'center', justifyContent: 'center',
                                    bgcolor: alpha('#FFFFFF', 0.1), color: tema.palette.secondary.light }}>
                                    {d.icone}
                                </Box>
                                <Box>
                                    <Typography fontWeight={700} variant="body2">{d.titulo}</Typography>
                                    <Typography variant="body2" sx={{ color: alpha('#F8FAFC', 0.7) }}>{d.texto}</Typography>
                                </Box>
                            </Box>
                        ))}
                    </Box>
                </Box>

                {/* Formulário */}
                <Box component="form" onSubmit={enviar} sx={{ p: { xs: 3, md: 5 }, bgcolor: 'background.paper', display: 'flex', flexDirection: 'column', gap: 2 }}>
                    <Box>
                        <Typography variant="h5" fontWeight={700}>Acesse a demonstração</Typography>
                        <Typography variant="body2" color="text.secondary">Sem senha — leva menos de um minuto.</Typography>
                    </Box>

                    <TextField
                        label="Nome" required autoFocus fullWidth value={data.nome} onChange={(e) => setData('nome', e.target.value)}
                        error={!!errors.nome} helperText={errors.nome} inputProps={{ maxLength: 120 }} autoComplete="name"
                    />
                    <TextField
                        label="E-mail" type="email" fullWidth value={data.email} onChange={(e) => setData('email', e.target.value)}
                        error={!!errors.email} helperText={errors.email} autoComplete="email"
                    />
                    <MaskedTextField
                        mask="celular" label="Telefone / WhatsApp" fullWidth value={data.telefone}
                        onChange={(mascarado) => setData('telefone', mascarado)}
                        error={!!errors.telefone} helperText={errors.telefone ?? 'Preencha e-mail, telefone ou os dois.'}
                        inputProps={{ inputMode: 'numeric' }} autoComplete="tel"
                    />
                    <TextField
                        label="Empresa (opcional)" fullWidth value={data.empresa} onChange={(e) => setData('empresa', e.target.value)}
                        error={!!errors.empresa} helperText={errors.empresa} inputProps={{ maxLength: 120 }} autoComplete="organization"
                    />

                    <Box>
                        <FormControlLabel
                            control={<Checkbox checked={data.aceite} onChange={(e) => setData('aceite', e.target.checked)} />}
                            label={<Typography variant="body2">Aceito ser contatado pela equipe comercial</Typography>}
                        />
                        {errors.aceite && <FormHelperText error>{errors.aceite}</FormHelperText>}
                    </Box>

                    <Button
                        type="submit" variant="contained" size="large" disabled={processing}
                        startIcon={processing ? <CircularProgress size={18} color="inherit" /> : undefined}
                    >
                        {processing ? 'Entrando…' : 'Acessar demonstração'}
                    </Button>

                    <Alert severity="info" icon={<LockRoundedIcon fontSize="small" />} sx={{ py: 0.25 }}>
                        Ambiente com dados fictícios e somente leitura: nada do que você fizer será gravado.
                    </Alert>
                </Box>
            </Paper>
        </Box>
    );
}
