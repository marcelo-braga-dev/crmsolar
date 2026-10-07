import React from 'react';
import { Box, Breadcrumbs, Link as MuiLink, Typography } from '@mui/material';
import NavigateNextRoundedIcon from '@mui/icons-material/NavigateNextRounded';
import { Link } from '@inertiajs/react';

interface Breadcrumb {
    label: string;
    href?: string;
}

interface PageHeaderProps {
    title: string;
    subtitle?: string;
    breadcrumbs?: Breadcrumb[];
    action?: React.ReactNode;
}

export function PageHeader({ title, subtitle, breadcrumbs, action }: PageHeaderProps) {
    return (
        <Box sx={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', mb: 3, flexWrap: 'wrap', gap: 2 }}>
            <Box>
                {breadcrumbs && breadcrumbs.length > 0 && (
                    <Breadcrumbs
                        separator={<NavigateNextRoundedIcon sx={{ fontSize: 16 }} />}
                        sx={{ mb: 0.5, '& .MuiBreadcrumbs-ol': { flexWrap: 'nowrap' } }}
                    >
                        {breadcrumbs.map((crumb, i) => {
                            const isLast = i === breadcrumbs.length - 1;
                            return isLast ? (
                                <Typography key={i} variant="caption" color="text.primary" fontWeight={500} sx={{ fontSize: '0.78rem' }}>
                                    {crumb.label}
                                </Typography>
                            ) : (
                                <MuiLink
                                    key={i}
                                    component={Link}
                                    href={crumb.href ?? '#'}
                                    underline="hover"
                                    color="text.secondary"
                                    sx={{ fontSize: '0.78rem' }}
                                >
                                    {crumb.label}
                                </MuiLink>
                            );
                        })}
                    </Breadcrumbs>
                )}
                <Typography variant="h5" fontWeight={700} sx={{ lineHeight: 1.2 }}>
                    {title}
                </Typography>
                {subtitle && (
                    <Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>
                        {subtitle}
                    </Typography>
                )}
            </Box>
            {action && <Box sx={{ flexShrink: 0, maxWidth: '100%' }}>{action}</Box>}
        </Box>
    );
}
