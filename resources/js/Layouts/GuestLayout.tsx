import React from 'react';
import { Box, Paper, Typography } from '@mui/material';
import WbSunnyRoundedIcon from '@mui/icons-material/WbSunnyRounded';

interface GuestLayoutProps {
    children: React.ReactNode;
}

export default function GuestLayout({ children }: GuestLayoutProps) {
    return (
        <Box
            sx={{
                minHeight: '100vh',
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                justifyContent: 'center',
                px: 2,
                py: { xs: 4, sm: 6 },
                backgroundColor: '#F8FAFC',
                backgroundImage:
                    'radial-gradient(60rem 30rem at 50% -10%, rgba(37,99,235,0.07) 0%, transparent 70%)',
            }}
        >
            {/* Marca */}
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.25, mb: 3.5 }}>
                <Box
                    sx={{
                        width: 38,
                        height: 38,
                        borderRadius: 2,
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        backgroundColor: '#0F172A',
                    }}
                >
                    <WbSunnyRoundedIcon sx={{ color: '#F59E0B', fontSize: 21 }} />
                </Box>
                <Typography
                    variant="h6"
                    sx={{ fontWeight: 700, letterSpacing: '-0.02em', color: 'text.primary' }}
                >
                    CRM Solar
                </Typography>
            </Box>

            {/* Card */}
            <Paper
                elevation={0}
                sx={{
                    width: '100%',
                    maxWidth: 400,
                    p: { xs: 3, sm: 4 },
                    borderRadius: 3,
                    border: '1px solid #E2E8F0',
                    backgroundColor: '#FFFFFF',
                    boxShadow: '0 1px 2px rgb(15 23 42 / 0.04), 0 12px 32px -12px rgb(15 23 42 / 0.12)',
                }}
            >
                {children}
            </Paper>

            <Typography variant="caption" sx={{ mt: 3 }}>
                © {new Date().getFullYear()} CRM Solar · CRM para energia solar
            </Typography>
        </Box>
    );
}
