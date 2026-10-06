import React, { useEffect, useRef, useState } from 'react';
import {
    Alert, Box, Button, Dialog, DialogActions, DialogContent, IconButton, Snackbar, Tooltip, Typography, alpha, useMediaQuery, useTheme,
} from '@mui/material';
import { router } from '@inertiajs/react';
import type { Page } from '@inertiajs/core';
import AutoAwesomeRoundedIcon from '@mui/icons-material/AutoAwesomeRounded';
import LockRoundedIcon from '@mui/icons-material/LockRounded';
import AdminPanelSettingsRoundedIcon from '@mui/icons-material/AdminPanelSettingsRounded';
import SupportAgentRoundedIcon from '@mui/icons-material/SupportAgentRounded';
import PersonRoundedIcon from '@mui/icons-material/PersonRounded';
import ExpandMoreRoundedIcon from '@mui/icons-material/ExpandMoreRounded';
import ExpandLessRoundedIcon from '@mui/icons-material/ExpandLessRounded';
import LogoutRoundedIcon from '@mui/icons-material/LogoutRounded';
import SouthRoundedIcon from '@mui/icons-material/SouthRounded';
import { EVENTO_BLOQUEIO } from './demoGuard';
import type { DemoProps, PageProps } from '@/types';

const DESTAQUE = '#F59E0B';
const CHAVE_MINIMIZADA = 'demo.minimizada';
const CHAVE_BOAS_VINDAS = 'demo.boasVindas';

const ICONES: Record<string, React.ReactElement> = {
    admin: <AdminPanelSettingsRoundedIcon fontSize="small" />,
    consultor: <SupportAgentRoundedIcon fontSize="small" />,
};

const lerSessao = (chave: string) => {
    try { return window.sessionStorage.getItem(chave); } catch { return null; }
};
const gravarSessao = (chave: string, valor: string) => {
    try { window.sessionStorage.setItem(chave, valor); } catch { /* armazenamento indisponível */ }
};

interface Estado { demo: DemoProps | null; logado: boolean; aviso: string | null }

const estadoDe = (page: Page<PageProps>): Estado => ({
    demo: page.props.demo ?? null,
    logado: Boolean(page.props.auth?.user),
    aviso: page.props.flash?.warning ?? null,
});

/**
 * Barra do modo demonstração (DEMO.md), montada na raiz do app — fora dos layouts — para
 * aparecer em todas as telas e perfis. Troca de perfil, minimiza, sai, dá boas-vindas e avisa
 * quando algo foi bloqueado.
 */
export function DemoBar({ paginaInicial }: { paginaInicial: Page<PageProps> }) {
    const tema = useTheme();
    const celular = useMediaQuery(tema.breakpoints.down('md'));
    const [estado, setEstado] = useState<Estado>(() => estadoDe(paginaInicial));
    const [minimizada, setMinimizada] = useState(() => lerSessao(CHAVE_MINIMIZADA) === '1');
    const [trocando, setTrocando] = useState<string | null>(null);
    const [aviso, setAviso] = useState<string | null>(null);
    const [boasVindas, setBoasVindas] = useState(false);
    const barra = useRef<HTMLDivElement>(null);

    const { demo, logado } = estado;
    const visivel = Boolean(demo?.enabled && logado);

    // Props das páginas chegam pelo evento de navegação (a barra fica fora do <App>).
    useEffect(() => router.on('navigate', (e) => {
        const novo = estadoDe(e.detail.page as Page<PageProps>);
        setEstado(novo);
        if (novo.demo && novo.aviso && novo.aviso === novo.demo.message) setAviso(novo.aviso);
    }), []);

    useEffect(() => {
        const ouvir = (e: Event) => setAviso((e as CustomEvent<{ mensagem?: string }>).detail?.mensagem ?? demo?.message ?? null);
        window.addEventListener(EVENTO_BLOQUEIO, ouvir);
        return () => window.removeEventListener(EVENTO_BLOQUEIO, ouvir);
    }, [demo?.message]);

    // Boas-vindas na primeira tela da sessão.
    useEffect(() => {
        if (visivel && lerSessao(CHAVE_BOAS_VINDAS) !== '1') setBoasVindas(true);
    }, [visivel]);

    // Reserva o espaço da barra: padding no body e --demo-dock para menus e elementos fixos.
    useEffect(() => {
        const raiz = document.documentElement;
        if (!visivel || !barra.current) {
            raiz.style.removeProperty('--demo-dock');
            document.body.style.paddingBottom = '';
            return;
        }
        const observador = new ResizeObserver(([entrada]) => {
            const altura = Math.ceil(entrada.target.getBoundingClientRect().height);
            raiz.style.setProperty('--demo-dock', `${altura}px`);
            document.body.style.paddingBottom = `${altura}px`;
        });
        observador.observe(barra.current);
        return () => observador.disconnect();
    }, [visivel, minimizada]);

    if (!demo || !visivel) return null;

    const perfilAtual = demo.roles.find((r) => r.key === demo.role);

    const trocar = (perfil: string) => {
        if (perfil === demo.role || trocando) return;
        router.post(route('demo.perfil', perfil), {}, {
            onStart: () => setTrocando(perfil),
            onFinish: () => setTrocando(null),
        });
    };

    const alternar = () => {
        const nova = !minimizada;
        setMinimizada(nova);
        gravarSessao(CHAVE_MINIMIZADA, nova ? '1' : '0');
    };

    const fecharBoasVindas = () => {
        gravarSessao(CHAVE_BOAS_VINDAS, '1');
        setBoasVindas(false);
    };

    const primeiroNome = (demo.visitor ?? '').split(' ')[0];
    const perfisTexto = demo.roles.map((r) => r.label).join(' e ');

    return (
        <Box data-demo-allow>
            <Box
                ref={barra}
                role="region"
                aria-label="Barra do modo demonstração"
                sx={{
                    position: 'fixed', left: 0, right: 0, bottom: 0, zIndex: 1600,
                    bgcolor: '#0B1220', color: '#E2E8F0', borderTop: `3px solid ${DESTAQUE}`,
                    boxShadow: '0 -8px 24px rgba(0,0,0,.25)',
                    px: { xs: 1.5, md: 2.5 }, pt: 1, pb: 'calc(8px + env(safe-area-inset-bottom))',
                    '& :focus-visible': { outline: `2px solid ${DESTAQUE}`, outlineOffset: 2 },
                }}
            >
                {minimizada ? (
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                        <Selo compacto />
                        <Typography variant="body2" sx={{ flex: 1, color: '#CBD5E1' }} noWrap>
                            Vendo como <b style={{ color: '#fff' }}>{perfilAtual?.label ?? demo.role}</b>
                        </Typography>
                        <Tooltip title="Expandir a barra">
                            <IconButton size="small" onClick={alternar} aria-label="Expandir a barra de demonstração" sx={{ color: '#CBD5E1' }}>
                                <ExpandLessRoundedIcon />
                            </IconButton>
                        </Tooltip>
                    </Box>
                ) : (
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: { xs: 1, md: 2 }, flexWrap: { xs: 'wrap', md: 'nowrap' } }}>
                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, flexShrink: 0 }}>
                            <Selo />
                            {!celular && (
                                <Typography variant="caption" sx={{ color: '#94A3B8', display: 'flex', alignItems: 'center', gap: 0.5 }}>
                                    <LockRoundedIcon sx={{ fontSize: 14 }} /> Somente visualização
                                </Typography>
                            )}
                        </Box>

                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, order: { xs: 3, md: 0 }, flex: { xs: '1 1 100%', md: 1 }, justifyContent: 'center' }}>
                            {!celular && <Typography variant="caption" sx={{ color: '#94A3B8', whiteSpace: 'nowrap' }}>Ver como:</Typography>}
                            <Box sx={{ display: 'grid', gridTemplateColumns: `repeat(${demo.roles.length}, minmax(0, 1fr))`, gap: 1, flex: { xs: 1, md: 'none' }, minWidth: { md: 320 } }}>
                                {demo.roles.map((r) => {
                                    const ativo = r.key === demo.role;
                                    return (
                                        <Button
                                            key={r.key}
                                            onClick={() => trocar(r.key)}
                                            disabled={Boolean(trocando) && trocando !== r.key}
                                            aria-pressed={ativo}
                                            aria-label={`Ver a plataforma como ${r.label}`}
                                            startIcon={celular ? undefined : (ICONES[r.key] ?? <PersonRoundedIcon fontSize="small" />)}
                                            sx={{
                                                flexDirection: celular ? 'column' : 'row', gap: celular ? 0.25 : 0, py: celular ? 0.5 : 0.6,
                                                textTransform: 'none', fontWeight: 600, borderRadius: 2,
                                                color: ativo ? '#0B1220' : '#E2E8F0',
                                                bgcolor: ativo ? '#F8FAFC' : alpha('#FFFFFF', 0.06),
                                                border: `1px solid ${ativo ? DESTAQUE : alpha('#FFFFFF', 0.12)}`,
                                                '&:hover': { bgcolor: ativo ? '#F8FAFC' : alpha('#FFFFFF', 0.12) },
                                                '&.Mui-disabled': { color: alpha('#E2E8F0', 0.4) },
                                            }}
                                        >
                                            {celular && (ICONES[r.key] ?? <PersonRoundedIcon fontSize="small" />)}
                                            {trocando === r.key ? 'Abrindo…' : r.label}
                                        </Button>
                                    );
                                })}
                            </Box>
                        </Box>

                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.5, ml: { xs: 'auto', md: 0 }, flexShrink: 0 }}>
                            <Tooltip title="Minimizar a barra">
                                <IconButton size="small" onClick={alternar} aria-label="Minimizar a barra de demonstração" sx={{ color: '#CBD5E1' }}>
                                    <ExpandMoreRoundedIcon />
                                </IconButton>
                            </Tooltip>
                            <Button
                                size="small" startIcon={<LogoutRoundedIcon />} onClick={() => router.post(route('logout'))}
                                aria-label="Sair da demonstração" sx={{ color: '#CBD5E1', textTransform: 'none' }}
                            >
                                {celular ? 'Sair' : 'Sair da demonstração'}
                            </Button>
                        </Box>
                    </Box>
                )}
            </Box>

            <Snackbar
                open={Boolean(aviso)} autoHideDuration={5000} onClose={() => setAviso(null)}
                anchorOrigin={{ vertical: 'top', horizontal: 'center' }} sx={{ zIndex: 1700 }}
            >
                <Alert severity="warning" variant="filled" icon={<LockRoundedIcon />} onClose={() => setAviso(null)} sx={{ alignItems: 'center' }}>
                    {aviso ?? demo.message}
                </Alert>
            </Snackbar>

            <Dialog open={boasVindas} onClose={fecharBoasVindas} maxWidth="xs" fullWidth sx={{ zIndex: 1650 }} data-demo-allow>
                <DialogContent sx={{ pt: 3, textAlign: 'center' }}>
                    <Box sx={{ display: 'flex', justifyContent: 'center', mb: 2 }}><Selo claro /></Box>
                    <Typography variant="h6" fontWeight={700}>Bem-vindo(a){primeiroNome ? `, ${primeiroNome}` : ''}!</Typography>
                    <Typography variant="body2" color="text.secondary" sx={{ mt: 1 }}>
                        Esta é uma empresa fictícia operando há vários meses. Navegue à vontade: os dados são de demonstração e
                        nada do que você fizer altera a plataforma.
                    </Typography>
                    <Box sx={{ mt: 2.5, p: 1.5, borderRadius: 2, bgcolor: alpha(DESTAQUE, 0.1), border: `1px solid ${alpha(DESTAQUE, 0.4)}`, display: 'flex', gap: 1, alignItems: 'center', textAlign: 'left' }}>
                        <SouthRoundedIcon sx={{ color: DESTAQUE }} />
                        <Typography variant="body2">
                            Use a barra no rodapé para ver a plataforma como <b>{perfisTexto}</b> — cada perfil tem as suas telas.
                        </Typography>
                    </Box>
                </DialogContent>
                <DialogActions sx={{ px: 3, pb: 2.5, justifyContent: 'center' }}>
                    <Button variant="contained" onClick={fecharBoasVindas} autoFocus>Começar a explorar</Button>
                </DialogActions>
            </Dialog>
        </Box>
    );
}

function Selo({ compacto, claro }: { compacto?: boolean; claro?: boolean }) {
    return (
        <Box
            sx={{
                display: 'inline-flex', alignItems: 'center', gap: 0.75, px: compacto ? 1 : 1.25, py: 0.4, borderRadius: 1.5,
                bgcolor: claro ? alpha(DESTAQUE, 0.15) : DESTAQUE, color: claro ? '#92400E' : '#0B1220',
                fontWeight: 800, fontSize: compacto ? '0.65rem' : '0.7rem', letterSpacing: '0.06em', whiteSpace: 'nowrap',
            }}
        >
            <AutoAwesomeRoundedIcon sx={{ fontSize: 15 }} /> MODO DEMONSTRAÇÃO
        </Box>
    );
}
