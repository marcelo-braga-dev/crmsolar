import React, { useState } from 'react';
import {
    Avatar,
    Box,
    Collapse,
    Drawer,
    List,
    ListItemButton,
    ListItemIcon,
    ListItemText,
    Tooltip,
    Typography,
    alpha,
    useTheme,
} from '@mui/material';
import ExpandLessRoundedIcon from '@mui/icons-material/ExpandLessRounded';
import ExpandMoreRoundedIcon from '@mui/icons-material/ExpandMoreRounded';
import ElectricBoltRoundedIcon from '@mui/icons-material/ElectricBoltRounded';
import { router, usePage } from '@inertiajs/react';
import { NavSection, NavItem } from './navConfig';
import { useIdentidade } from '@/hooks/useIdentidade';
import { rotuloTipoUsuario } from '@/Components/UI/rotuloTipoUsuario';
import type { PageProps } from '@/types';

export const SIDEBAR_WIDTH = 260;

interface SidebarProps {
    open: boolean;
    onClose?: () => void;
    variant: 'permanent' | 'temporary';
    sections: NavSection[];
    currentPath: string;
}

function NavItemRow({
    item,
    currentPath,
    collapsed = false,
}: {
    item: NavItem;
    currentPath: string;
    collapsed?: boolean;
}) {
    const theme = useTheme();
    const hasChildren = Boolean(item.children?.length);
    const isChildActive = item.children?.some((c) => currentPath.startsWith(c.href));
    const [open, setOpen] = useState<boolean>(Boolean(isChildActive));

    const isActive = item.href
        ? currentPath === item.href || currentPath.startsWith(item.href + '/')
        : false;

    const activeSx = {
        backgroundColor: alpha(theme.palette.primary.main, 0.15),
        color: theme.palette.sidebar.strong,
        '& .MuiListItemIcon-root': { color: theme.palette.sidebar.active },
        '&:hover': { backgroundColor: alpha(theme.palette.primary.main, 0.2) },
    };

    const defaultSx = {
        color: theme.palette.sidebar.text,
        '& .MuiListItemIcon-root': { color: theme.palette.sidebar.textMuted },
        '&:hover': {
            backgroundColor: theme.palette.sidebar.hover,
            color: theme.palette.sidebar.strong,
            '& .MuiListItemIcon-root': { color: theme.palette.sidebar.strong },
        },
    };

    const handleClick = () => {
        if (item.href) {
            router.visit(item.href);
        } else {
            setOpen((prev) => !prev);
        }
    };

    const row = (
        <ListItemButton
            onClick={handleClick}
            sx={{
                borderRadius: 2,
                mx: 1,
                mb: 0.25,
                py: 0.9,
                px: 1.5,
                minHeight: 42,
                transition: 'all 0.15s ease',
                ...(isActive ? activeSx : defaultSx),
            }}
        >
            <ListItemIcon sx={{ minWidth: 36, '& svg': { fontSize: 20 } }}>
                {item.icon}
            </ListItemIcon>
            <ListItemText
                primary={item.title}
                primaryTypographyProps={{
                    fontSize: '0.8rem',
                    fontWeight: isActive ? 600 : 500,
                    lineHeight: 1.4,
                }}
            />
            {hasChildren && (
                <Box sx={{ ml: 0.5, display: 'flex', color: theme.palette.sidebar.textMuted }}>
                    {open ? <ExpandLessRoundedIcon fontSize="small" /> : <ExpandMoreRoundedIcon fontSize="small" />}
                </Box>
            )}
        </ListItemButton>
    );

    return (
        <>
            {collapsed ? (
                <Tooltip title={item.title} placement="right">
                    {row}
                </Tooltip>
            ) : (
                row
            )}
            {hasChildren && (
                <Collapse in={open} timeout="auto" unmountOnExit>
                    <List disablePadding sx={{ pl: 1.5 }}>
                        {item.children!.map((child) => {
                            const childActive =
                                currentPath === child.href || currentPath.startsWith(child.href + '/');
                            return (
                                <ListItemButton
                                    key={child.href}
                                    onClick={() => router.visit(child.href)}
                                    sx={{
                                        borderRadius: 2,
                                        mx: 1,
                                        mb: 0.25,
                                        py: 0.7,
                                        px: 1.5,
                                        minHeight: 36,
                                        ...(childActive ? activeSx : defaultSx),
                                    }}
                                >
                                    <Box
                                        sx={{
                                            width: 6,
                                            height: 6,
                                            borderRadius: '50%',
                                            mr: 1.5,
                                            ml: 0.5,
                                            backgroundColor: childActive
                                                ? theme.palette.sidebar.active
                                                : theme.palette.sidebar.textMuted,
                                            flexShrink: 0,
                                        }}
                                    />
                                    <ListItemText
                                        primary={child.title}
                                        primaryTypographyProps={{
                                            fontSize: '0.8rem',
                                            fontWeight: childActive ? 600 : 400,
                                        }}
                                    />
                                </ListItemButton>
                            );
                        })}
                    </List>
                </Collapse>
            )}
        </>
    );
}

function SidebarContent({
    sections,
    currentPath,
}: {
    sections: NavSection[];
    currentPath: string;
}) {
    const theme = useTheme();
    const identidade = useIdentidade();
    const usuario = usePage<PageProps>().props.auth?.user;
    return (
        <Box
            sx={{
                height: '100%',
                display: 'flex',
                flexDirection: 'column',
                backgroundColor: theme.palette.sidebar.bg,
                overflowX: 'hidden',
            }}
        >
            {/* Marca (Configurações → Identidade visual) */}
            <Box
                sx={{
                    px: 2.5,
                    py: 2.5,
                    display: 'flex',
                    alignItems: 'center',
                    gap: 1.5,
                    borderBottom: `1px solid ${theme.palette.sidebar.border}`,
                    mb: 1,
                    minHeight: 76,
                }}
            >
                {/* Logo em avatar redondo; sem logo, o ícone padrão no mesmo formato. */}
                <Avatar
                    src={identidade.logo_url ?? undefined}
                    alt={identidade.nome}
                    sx={{
                        width: 42,
                        height: 42,
                        flexShrink: 0,
                        bgcolor: identidade.logo_url ? '#FFFFFF' : undefined,
                        background: identidade.logo_url
                            ? undefined
                            : `linear-gradient(135deg, ${theme.palette.secondary.main} 0%, ${theme.palette.secondary.dark} 100%)`,
                        border: `2px solid ${alpha(theme.palette.sidebar.text, 0.18)}`,
                        // Logos costumam ser retangulares: cabem inteiras no círculo, com respiro.
                        '& .MuiAvatar-img': { objectFit: 'contain', p: '5px' },
                    }}
                >
                    <ElectricBoltRoundedIcon sx={{ color: theme.palette.secondary.contrastText, fontSize: 21 }} />
                </Avatar>
                <Box sx={{ minWidth: 0 }}>
                    <Typography
                        variant="subtitle1"
                        noWrap
                        sx={{ color: theme.palette.sidebar.strong, fontWeight: 700, lineHeight: 1.2, fontSize: '0.95rem' }}
                    >
                        {identidade.nome}
                    </Typography>
                    {/* Função de quem está logado (Administrador, Consultor…). */}
                    {usuario && (
                        <Typography
                            variant="caption"
                            noWrap
                            component="div"
                            sx={{ color: theme.palette.sidebar.textMuted, fontSize: '0.7rem' }}
                        >
                            {rotuloTipoUsuario(usuario.tipo)}
                        </Typography>
                    )}
                </Box>
            </Box>

            {/* Navigation */}
            <Box sx={{ flex: 1, overflowY: 'auto', overflowX: 'hidden', px: 0.5, py: 1 }}>
                {sections.map((section, si) => (
                    <Box key={si} sx={{ mb: 1 }}>
                        {section.subheader && (
                            <Typography
                                variant="overline"
                                sx={{
                                    color: theme.palette.sidebar.textMuted,
                                    fontSize: '0.65rem',
                                    fontWeight: 700,
                                    letterSpacing: '0.1em',
                                    px: 2.5,
                                    py: 1,
                                    display: 'block',
                                }}
                            >
                                {section.subheader}
                            </Typography>
                        )}
                        <List disablePadding>
                            {section.items.map((item) => (
                                <NavItemRow
                                    key={item.title}
                                    item={item}
                                    currentPath={currentPath}
                                />
                            ))}
                        </List>
                    </Box>
                ))}
            </Box>
        </Box>
    );
}

export function Sidebar({ open, onClose, variant, sections, currentPath }: SidebarProps) {
    const theme = useTheme();

    const drawerProps = {
        sx: {
            width: SIDEBAR_WIDTH,
            flexShrink: 0,
            '& .MuiDrawer-paper': {
                width: SIDEBAR_WIDTH,
                boxSizing: 'border-box',
                border: 'none',
                boxShadow:
                    variant === 'permanent'
                        ? '1px 0 0 rgba(255,255,255,0.05)'
                        : '4px 0 24px rgba(0,0,0,0.4)',
            },
        },
    };

    if (variant === 'permanent') {
        return (
            <Drawer variant="permanent" {...drawerProps}>
                <SidebarContent sections={sections} currentPath={currentPath} />
            </Drawer>
        );
    }

    return (
        <Drawer
            variant="temporary"
            open={open}
            onClose={onClose}
            ModalProps={{ keepMounted: true }}
            {...drawerProps}
        >
            <SidebarContent sections={sections} currentPath={currentPath} />
        </Drawer>
    );
}
