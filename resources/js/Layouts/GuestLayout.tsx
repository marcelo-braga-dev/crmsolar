import React from 'react';
import { Box, Paper, Typography } from '@mui/material';
import ElectricBoltRoundedIcon from '@mui/icons-material/ElectricBoltRounded';

interface GuestLayoutProps {
    children: React.ReactNode;
}

export default function GuestLayout({ children }: GuestLayoutProps) {
    return (
        <Box
            sx={{
                minHeight: '100vh',
                display: 'flex',
                background: 'linear-gradient(135deg, #0F172A 0%, #1E293B 50%, #0F172A 100%)',
                position: 'relative',
                overflow: 'hidden',
            }}
        >
            {/* Background glow effects */}
            <Box
                sx={{
                    position: 'absolute', top: '-20%', right: '-10%',
                    width: 600, height: 600, borderRadius: '50%',
                    background: 'radial-gradient(circle, rgba(37,99,235,0.15) 0%, transparent 70%)',
                    pointerEvents: 'none',
                }}
            />
            <Box
                sx={{
                    position: 'absolute', bottom: '-20%', left: '-10%',
                    width: 500, height: 500, borderRadius: '50%',
                    background: 'radial-gradient(circle, rgba(245,158,11,0.1) 0%, transparent 70%)',
                    pointerEvents: 'none',
                }}
            />

            {/* Left panel — branding */}
            <Box
                sx={{
                    flex: 1,
                    display: { xs: 'none', md: 'flex' },
                    flexDirection: 'column',
                    justifyContent: 'center',
                    px: { md: 6, lg: 10 },
                    py: 8,
                    position: 'relative',
                    zIndex: 1,
                }}
            >
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, mb: 6 }}>
                    <Box
                        sx={{
                            width: 44, height: 44, borderRadius: 2.5,
                            background: 'linear-gradient(135deg, #F59E0B 0%, #EF4444 100%)',
                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                        }}
                    >
                        <ElectricBoltRoundedIcon sx={{ color: '#fff', fontSize: 24 }} />
                    </Box>
                    <Typography variant="h5" sx={{ color: '#FFFFFF', fontWeight: 700 }}>
                        AppSolar
                    </Typography>
                </Box>

                <Typography variant="h3" sx={{ color: '#FFFFFF', fontWeight: 700, mb: 2, lineHeight: 1.2, maxWidth: 480 }}>
                    CRM para Energia Solar Fotovoltaica
                </Typography>
                <Typography sx={{ color: 'rgba(255,255,255,0.6)', fontSize: '1.05rem', maxWidth: 420, lineHeight: 1.7 }}>
                    Gerencie clientes, gere propostas profissionais e acompanhe todo o ciclo de venda em uma única plataforma.
                </Typography>

                {[
                    'Dimensionamento automático de sistemas fotovoltaicos',
                    'Propostas PDF profissionais em segundos',
                    'Pipeline completo de vendas e contratos',
                ].map((feat) => (
                    <Box key={feat} sx={{ display: 'flex', alignItems: 'center', gap: 1.5, mt: 2.5 }}>
                        <Box sx={{ width: 6, height: 6, borderRadius: '50%', backgroundColor: '#F59E0B', flexShrink: 0 }} />
                        <Typography sx={{ color: 'rgba(255,255,255,0.7)', fontSize: '0.9rem' }}>{feat}</Typography>
                    </Box>
                ))}
            </Box>

            {/* Right panel — form card */}
            <Box
                sx={{
                    width: { xs: '100%', md: 480 },
                    flexShrink: 0,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    p: { xs: 2, sm: 4 },
                    position: 'relative',
                    zIndex: 1,
                }}
            >
                <Paper
                    elevation={0}
                    sx={{
                        width: '100%',
                        maxWidth: 420,
                        p: { xs: 3, sm: 4 },
                        borderRadius: 3,
                        border: '1px solid rgba(255,255,255,0.08)',
                        backgroundColor: 'rgba(255,255,255,0.97)',
                        backdropFilter: 'blur(20px)',
                    }}
                >
                    {/* Mobile logo */}
                    <Box sx={{ display: { xs: 'flex', md: 'none' }, alignItems: 'center', gap: 1.5, mb: 3 }}>
                        <Box
                            sx={{
                                width: 36, height: 36, borderRadius: 2,
                                background: 'linear-gradient(135deg, #F59E0B 0%, #EF4444 100%)',
                                display: 'flex', alignItems: 'center', justifyContent: 'center',
                            }}
                        >
                            <ElectricBoltRoundedIcon sx={{ color: '#fff', fontSize: 20 }} />
                        </Box>
                        <Typography variant="h6" fontWeight={700}>AppSolar</Typography>
                    </Box>
                    {children}
                </Paper>
            </Box>
        </Box>
    );
}
