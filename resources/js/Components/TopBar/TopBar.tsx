import React, { useState } from 'react';
import {
    AppBar,
    Avatar,
    Box,
    Divider,
    IconButton,
    ListItemIcon,
    Menu,
    MenuItem,
    Toolbar,
    Tooltip,
    Typography,
    useMediaQuery,
    useTheme,
} from '@mui/material';
import MenuRoundedIcon from '@mui/icons-material/MenuRounded';
import NotificationsNoneRoundedIcon from '@mui/icons-material/NotificationsNoneRounded';
import PersonRoundedIcon from '@mui/icons-material/PersonRounded';
import LockResetRoundedIcon from '@mui/icons-material/LockResetRounded';
import LogoutRoundedIcon from '@mui/icons-material/LogoutRounded';
import { router } from '@inertiajs/react';
import { User } from '@/types';
import { rotuloTipoUsuario } from '@/Components/UI/rotuloTipoUsuario';
import { SIDEBAR_WIDTH } from '../Sidebar/Sidebar';

interface TopBarProps {
    user: User;
    onMenuToggle: () => void;
    title?: string;
}

function stringToColor(str: string) {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        hash = str.charCodeAt(i) + ((hash << 5) - hash);
    }
    const colors = ['#2563EB', '#7C3AED', '#DC2626', '#059669', '#D97706', '#0891B2'];
    return colors[Math.abs(hash) % colors.length];
}

function getInitials(name: string) {
    return name.split(' ').slice(0, 2).map((n) => n[0]).join('').toUpperCase();
}

export function TopBar({ user, onMenuToggle, title }: TopBarProps) {
    const theme = useTheme();
    const isDesktop = useMediaQuery(theme.breakpoints.up('lg'));
    const [anchorEl, setAnchorEl] = useState<null | HTMLElement>(null);

    const handleMenuOpen = (e: React.MouseEvent<HTMLElement>) => setAnchorEl(e.currentTarget);
    const handleMenuClose = () => setAnchorEl(null);

    const handleLogout = () => {
        handleMenuClose();
        router.post('/logout');
    };

    const handleProfile = () => {
        handleMenuClose();
        const base = user.tipo === 'consultor' ? '/consultor' : '/admin';
        router.visit(`${base}/perfil`);
    };

    const handlePassword = () => {
        handleMenuClose();
        const base = user.tipo === 'consultor' ? '/consultor' : '/admin';
        router.visit(`${base}/perfil/senha`);
    };

    return (
        <AppBar
            position="fixed"
            elevation={0}
            sx={{
                backgroundColor: '#FFFFFF',
                borderBottom: '1px solid #E2E8F0',
                color: theme.palette.text.primary,
                width: { lg: `calc(100% - ${SIDEBAR_WIDTH}px)` },
                ml: { lg: `${SIDEBAR_WIDTH}px` },
                zIndex: theme.zIndex.drawer - 1,
            }}
        >
            <Toolbar sx={{ minHeight: { xs: 60, sm: 64 }, gap: 1 }}>
                {!isDesktop && (
                    <IconButton onClick={onMenuToggle} edge="start" size="small" sx={{ mr: 1 }}>
                        <MenuRoundedIcon />
                    </IconButton>
                )}

                {title && (
                    <Typography variant="h6" sx={{ fontSize: '1rem', fontWeight: 600 }}>
                        {title}
                    </Typography>
                )}

                <Box sx={{ flex: 1 }} />

                <Tooltip title="Notificações">
                    <IconButton size="small" sx={{ mr: 0.5 }}>
                        <NotificationsNoneRoundedIcon fontSize="small" />
                    </IconButton>
                </Tooltip>

                <Tooltip title="Minha conta">
                    <IconButton onClick={handleMenuOpen} size="small" sx={{ p: 0 }}>
                        <Avatar
                            sx={{
                                width: 36,
                                height: 36,
                                backgroundColor: stringToColor(user.name),
                                fontSize: '0.8rem',
                                fontWeight: 700,
                            }}
                        >
                            {getInitials(user.name)}
                        </Avatar>
                    </IconButton>
                </Tooltip>

                <Menu
                    anchorEl={anchorEl}
                    open={Boolean(anchorEl)}
                    onClose={handleMenuClose}
                    transformOrigin={{ horizontal: 'right', vertical: 'top' }}
                    anchorOrigin={{ horizontal: 'right', vertical: 'bottom' }}
                    slotProps={{
                        paper: {
                            elevation: 4,
                            sx: { mt: 1, minWidth: 200, borderRadius: 2, border: '1px solid #E2E8F0', boxShadow: '0 8px 24px rgba(0,0,0,0.1)' },
                        },
                    }}
                >
                    <Box sx={{ px: 2, py: 1.5 }}>
                        <Typography variant="subtitle2" fontWeight={600} noWrap>{user.name}</Typography>
                        <Typography variant="caption" color="text.secondary" noWrap>
                            {rotuloTipoUsuario(user.tipo)}
                        </Typography>
                    </Box>
                    <Divider />
                    <MenuItem onClick={handleProfile} sx={{ py: 1.2, fontSize: '0.875rem' }}>
                        <ListItemIcon><PersonRoundedIcon fontSize="small" /></ListItemIcon>
                        Meu Perfil
                    </MenuItem>
                    <MenuItem onClick={handlePassword} sx={{ py: 1.2, fontSize: '0.875rem' }}>
                        <ListItemIcon><LockResetRoundedIcon fontSize="small" /></ListItemIcon>
                        Alterar Senha
                    </MenuItem>
                    <Divider />
                    <MenuItem onClick={handleLogout} sx={{ py: 1.2, fontSize: '0.875rem', color: 'error.main' }}>
                        <ListItemIcon><LogoutRoundedIcon fontSize="small" color="error" /></ListItemIcon>
                        Sair
                    </MenuItem>
                </Menu>
            </Toolbar>
        </AppBar>
    );
}
