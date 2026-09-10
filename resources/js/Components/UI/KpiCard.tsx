import React from 'react';
import { Box, Card, CardContent, Skeleton, Typography, alpha } from '@mui/material';
import TrendingUpRoundedIcon from '@mui/icons-material/TrendingUpRounded';
import TrendingDownRoundedIcon from '@mui/icons-material/TrendingDownRounded';

interface KpiCardProps {
    title: string;
    value: string | number;
    subtitle?: string;
    icon: React.ReactNode;
    color?: string;
    trend?: number; // percentage, positive = up, negative = down
    loading?: boolean;
}

export function KpiCard({ title, value, subtitle, icon, color = '#2563EB', trend, loading }: KpiCardProps) {
    const isPositiveTrend = trend !== undefined && trend >= 0;

    if (loading) {
        return (
            <Card>
                <CardContent>
                    <Skeleton variant="text" width="60%" sx={{ mb: 1 }} />
                    <Skeleton variant="text" width="40%" height={40} sx={{ mb: 1 }} />
                    <Skeleton variant="text" width="80%" />
                </CardContent>
            </Card>
        );
    }

    return (
        <Card sx={{ height: '100%' }}>
            <CardContent sx={{ p: '20px !important' }}>
                <Box sx={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', mb: 2 }}>
                    <Box>
                        <Typography variant="body2" color="text.secondary" fontWeight={500} sx={{ mb: 0.5, fontSize: '0.8rem', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                            {title}
                        </Typography>
                        <Typography variant="h4" fontWeight={700} sx={{ lineHeight: 1.2, color: 'text.primary', fontSize: { xs: '1.5rem', sm: '1.75rem' } }}>
                            {value}
                        </Typography>
                    </Box>
                    <Box
                        sx={{
                            width: 48,
                            height: 48,
                            borderRadius: 2.5,
                            backgroundColor: alpha(color, 0.12),
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            color: color,
                            flexShrink: 0,
                            '& svg': { fontSize: 24 },
                        }}
                    >
                        {icon}
                    </Box>
                </Box>

                {(trend !== undefined || subtitle) && (
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.5 }}>
                        {trend !== undefined && (
                            <>
                                <Box
                                    sx={{
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: 0.25,
                                        color: isPositiveTrend ? 'success.main' : 'error.main',
                                        '& svg': { fontSize: 16 },
                                    }}
                                >
                                    {isPositiveTrend ? <TrendingUpRoundedIcon /> : <TrendingDownRoundedIcon />}
                                    <Typography variant="caption" fontWeight={700} color="inherit">
                                        {Math.abs(trend)}%
                                    </Typography>
                                </Box>
                                <Typography variant="caption" color="text.secondary">
                                    vs mês anterior
                                </Typography>
                            </>
                        )}
                        {!trend && subtitle && (
                            <Typography variant="caption" color="text.secondary">
                                {subtitle}
                            </Typography>
                        )}
                    </Box>
                )}
            </CardContent>
        </Card>
    );
}
