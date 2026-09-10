import { createTheme, alpha } from '@mui/material/styles';

declare module '@mui/material/styles' {
    interface Palette {
        sidebar: {
            bg: string;
            text: string;
            textMuted: string;
            active: string;
            hover: string;
            border: string;
        };
        solar: {
            main: string;
            light: string;
            dark: string;
        };
    }
    interface PaletteOptions {
        sidebar?: {
            bg: string;
            text: string;
            textMuted: string;
            active: string;
            hover: string;
            border: string;
        };
        solar?: {
            main: string;
            light: string;
            dark: string;
        };
    }
}

const SIDEBAR_BG = '#0F172A'; // slate-900

export const theme = createTheme({
    palette: {
        mode: 'light',
        primary: {
            main: '#2563EB',
            light: '#3B82F6',
            dark: '#1D4ED8',
            contrastText: '#FFFFFF',
        },
        secondary: {
            main: '#F59E0B',
            light: '#FCD34D',
            dark: '#D97706',
            contrastText: '#FFFFFF',
        },
        error: { main: '#EF4444', light: '#FCA5A5', dark: '#DC2626' },
        warning: { main: '#F59E0B', light: '#FDE68A', dark: '#D97706' },
        success: { main: '#10B981', light: '#6EE7B7', dark: '#059669' },
        info: { main: '#06B6D4', light: '#67E8F9', dark: '#0891B2' },
        background: {
            default: '#F1F5F9',
            paper: '#FFFFFF',
        },
        text: {
            primary: '#0F172A',
            secondary: '#64748B',
            disabled: '#94A3B8',
        },
        divider: '#E2E8F0',
        sidebar: {
            bg: SIDEBAR_BG,
            text: '#CBD5E1',
            textMuted: '#64748B',
            active: '#2563EB',
            hover: alpha('#FFFFFF', 0.06),
            border: alpha('#FFFFFF', 0.08),
        },
        solar: {
            main: '#F59E0B',
            light: '#FCD34D',
            dark: '#D97706',
        },
    },
    typography: {
        fontFamily: "'Inter', sans-serif",
        h1: { fontWeight: 700, letterSpacing: '-0.025em' },
        h2: { fontWeight: 700, letterSpacing: '-0.025em' },
        h3: { fontWeight: 600, letterSpacing: '-0.02em' },
        h4: { fontWeight: 600, letterSpacing: '-0.01em' },
        h5: { fontWeight: 600 },
        h6: { fontWeight: 600 },
        subtitle1: { fontWeight: 500 },
        subtitle2: { fontWeight: 500, color: '#64748B' },
        body1: { lineHeight: 1.6 },
        body2: { lineHeight: 1.6 },
        button: { fontWeight: 600, textTransform: 'none', letterSpacing: '0.01em' },
        overline: { fontWeight: 600, letterSpacing: '0.1em' },
        caption: { color: '#64748B' },
    },
    shape: {
        borderRadius: 10,
    },
    shadows: [
        'none',
        '0 1px 2px 0 rgb(0 0 0 / 0.05)',
        '0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1)',
        '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)',
        '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
        '0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1)',
        '0 25px 50px -12px rgb(0 0 0 / 0.25)',
        '0 25px 50px -12px rgb(0 0 0 / 0.25)',
        '0 25px 50px -12px rgb(0 0 0 / 0.25)',
        '0 25px 50px -12px rgb(0 0 0 / 0.25)',
        '0 25px 50px -12px rgb(0 0 0 / 0.25)',
        '0 25px 50px -12px rgb(0 0 0 / 0.25)',
        '0 25px 50px -12px rgb(0 0 0 / 0.25)',
        '0 25px 50px -12px rgb(0 0 0 / 0.25)',
        '0 25px 50px -12px rgb(0 0 0 / 0.25)',
        '0 25px 50px -12px rgb(0 0 0 / 0.25)',
        '0 25px 50px -12px rgb(0 0 0 / 0.25)',
        '0 25px 50px -12px rgb(0 0 0 / 0.25)',
        '0 25px 50px -12px rgb(0 0 0 / 0.25)',
        '0 25px 50px -12px rgb(0 0 0 / 0.25)',
        '0 25px 50px -12px rgb(0 0 0 / 0.25)',
        '0 25px 50px -12px rgb(0 0 0 / 0.25)',
        '0 25px 50px -12px rgb(0 0 0 / 0.25)',
        '0 25px 50px -12px rgb(0 0 0 / 0.25)',
        '0 25px 50px -12px rgb(0 0 0 / 0.25)',
    ],
    components: {
        MuiCssBaseline: {
            styleOverrides: {
                body: {
                    fontFamily: "'Inter', sans-serif",
                    backgroundColor: '#F1F5F9',
                },
            },
        },
        MuiCard: {
            defaultProps: { elevation: 0 },
            styleOverrides: {
                root: {
                    border: '1px solid #E2E8F0',
                    borderRadius: 12,
                    boxShadow: '0 1px 3px 0 rgb(0 0 0 / 0.04), 0 1px 2px -1px rgb(0 0 0 / 0.04)',
                },
            },
        },
        MuiCardContent: {
            styleOverrides: { root: { padding: 24, '&:last-child': { paddingBottom: 24 } } },
        },
        MuiButton: {
            defaultProps: { disableElevation: true },
            styleOverrides: {
                root: {
                    borderRadius: 8,
                    padding: '8px 20px',
                    fontSize: '0.875rem',
                },
                contained: {
                    boxShadow: 'none',
                    '&:hover': { boxShadow: '0 4px 12px rgba(37,99,235,0.25)' },
                },
                sizeSmall: { padding: '5px 14px', fontSize: '0.8125rem' },
                sizeLarge: { padding: '11px 28px', fontSize: '1rem' },
            },
        },
        MuiIconButton: {
            styleOverrides: {
                root: { borderRadius: 8 },
            },
        },
        MuiTextField: {
            defaultProps: { size: 'small' },
            styleOverrides: {
                root: {
                    '& .MuiOutlinedInput-root': { borderRadius: 8 },
                    // Espaçamento mínimo entre o label e o input abaixo
                    '& .MuiFormHelperText-root': { marginTop: 5 },
                },
            },
        },
        MuiOutlinedInput: {
            styleOverrides: {
                root: { borderRadius: 8 },
                // Aumenta o padding vertical interno dos inputs
                input: { paddingTop: 10, paddingBottom: 10 },
                notchedOutline: { borderColor: '#E2E8F0' },
            },
        },
        MuiSelect: {
            styleOverrides: {
                select: { paddingTop: 10, paddingBottom: 10 },
            },
        },
        MuiFormControl: {
            styleOverrides: {
                root: {
                    // Garante que labels flutuantes não colidam com o borde superior
                    '& .MuiInputLabel-outlined': { lineHeight: '1.2em' },
                },
            },
        },
        MuiChip: {
            styleOverrides: {
                root: { borderRadius: 6, fontWeight: 500, fontSize: '0.75rem' },
            },
        },
        MuiTableHead: {
            styleOverrides: {
                root: {
                    '& .MuiTableCell-root': {
                        backgroundColor: '#F8FAFC',
                        fontWeight: 600,
                        fontSize: '0.75rem',
                        textTransform: 'uppercase',
                        letterSpacing: '0.05em',
                        color: '#64748B',
                        borderBottom: '1px solid #E2E8F0',
                    },
                },
            },
        },
        MuiTableCell: {
            styleOverrides: {
                root: { borderColor: '#F1F5F9', fontSize: '0.875rem' },
            },
        },
        MuiTableRow: {
            styleOverrides: {
                root: {
                    '&:hover': { backgroundColor: '#F8FAFC' },
                    '&:last-child td': { border: 0 },
                },
            },
        },
        MuiPaper: {
            defaultProps: { elevation: 0 },
            styleOverrides: {
                root: { borderRadius: 12, border: '1px solid #E2E8F0' },
            },
        },
        MuiLinearProgress: {
            styleOverrides: {
                root: { borderRadius: 4 },
            },
        },
        MuiAlert: {
            styleOverrides: { root: { borderRadius: 10 } },
        },
        MuiTooltip: {
            styleOverrides: {
                tooltip: {
                    borderRadius: 6,
                    fontSize: '0.75rem',
                    backgroundColor: '#0F172A',
                },
            },
        },
        MuiSkeleton: {
            defaultProps: { animation: 'wave' },
        },
        MuiDivider: {
            styleOverrides: { root: { borderColor: '#E2E8F0' } },
        },
        MuiAvatar: {
            styleOverrides: {
                root: { fontSize: '0.875rem', fontWeight: 600 },
            },
        },
        MuiBadge: {
            styleOverrides: {
                badge: { fontWeight: 700, fontSize: '0.65rem', minWidth: 18, height: 18 },
            },
        },
    },
});
