import React, { useState, useEffect } from 'react';
import { Box, Toolbar, useMediaQuery, useTheme, Snackbar, Alert } from '@mui/material';
import { usePage } from '@inertiajs/react';
import NProgress from 'nprogress';
import { router } from '@inertiajs/react';
import { Sidebar, SIDEBAR_WIDTH } from '@/Components/Sidebar/Sidebar';
import { TopBar } from '@/Components/TopBar/TopBar';
import { adminNav, consultorNav } from '@/Components/Sidebar/navConfig';
import { PageProps } from '@/types';

interface AppLayoutProps {
    children: React.ReactNode;
    title?: string;
}

export default function AppLayout({ children, title }: AppLayoutProps) {
    const theme = useTheme();
    const isDesktop = useMediaQuery(theme.breakpoints.up('lg'));
    const [mobileOpen, setMobileOpen] = useState(false);
    const { auth, flash, demo } = usePage<PageProps>().props;
    const [snack, setSnack] = useState<{ open: boolean; message: string; severity: 'success' | 'error' | 'warning' | 'info' }>({
        open: false,
        message: '',
        severity: 'success',
    });

    const currentPath = window.location.pathname;
    const sections = auth.user.tipo === 'consultor' ? consultorNav : adminNav;

    // NProgress on route changes (Inertia v2: router.on returns a cleanup fn)
    useEffect(() => {
        const removeStart = router.on('start', () => NProgress.start());
        const removeFinish = router.on('finish', () => NProgress.done());
        const removeError = router.on('error', () => NProgress.done());
        return () => {
            removeStart();
            removeFinish();
            removeError();
        };
    }, []);

    // Flash messages
    useEffect(() => {
        if (flash?.success) setSnack({ open: true, message: flash.success, severity: 'success' });
        else if (flash?.error) setSnack({ open: true, message: flash.error, severity: 'error' });
        // O aviso de bloqueio da demonstração é mostrado pela barra de demonstração (sem duplicar).
        else if (flash?.warning && flash.warning !== demo?.message) setSnack({ open: true, message: flash.warning, severity: 'warning' });
        else if (flash?.info) setSnack({ open: true, message: flash.info, severity: 'info' });
    }, [flash]);

    return (
        // --demo-dock: altura da barra do modo demonstração (0 fora dele).
        <Box sx={{ display: 'flex', height: 'calc(100vh - var(--demo-dock, 0px))', backgroundColor: 'background.default' }}>
            {/* Sidebar */}
            <Sidebar
                open={mobileOpen}
                onClose={() => setMobileOpen(false)}
                variant={isDesktop ? 'permanent' : 'temporary'}
                sections={sections}
                currentPath={currentPath}
            />

            {/* Main area */}
            <Box
                component="main"
                sx={{
                    flex: 1,
                    display: 'flex',
                    flexDirection: 'column',
                    minWidth: 0,
                    overflow: 'hidden',
                }}
            >
                <TopBar
                    user={auth.user}
                    onMenuToggle={() => setMobileOpen((prev) => !prev)}
                    title={title}
                />
                <Toolbar sx={{ minHeight: { xs: 60, sm: 64 } }} />

                {/* Page content */}
                <Box
                    sx={{
                        flex: 1,
                        overflowY: 'auto',
                        p: { xs: 2, sm: 3 },
                    }}
                >
                    {children}
                </Box>
            </Box>

            {/* Flash snackbar */}
            <Snackbar
                open={snack.open}
                autoHideDuration={4000}
                onClose={() => setSnack((s) => ({ ...s, open: false }))}
                anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
            >
                <Alert
                    severity={snack.severity}
                    variant="filled"
                    onClose={() => setSnack((s) => ({ ...s, open: false }))}
                    sx={{ borderRadius: 2, boxShadow: 4 }}
                >
                    {snack.message}
                </Alert>
            </Snackbar>
        </Box>
    );
}
