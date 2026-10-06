import '../css/app.css';
import './bootstrap';

import React, { useEffect, useMemo, useState } from 'react';
import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { ThemeProvider } from '@mui/material/styles';
import CssBaseline from '@mui/material/CssBaseline';
import NProgress from 'nprogress';
import { IDENTIDADE_PADRAO, criarTema } from './theme';
import type { Identidade } from './types';

/** Nome da plataforma (Identidade visual) usado no título das abas; atualizado a cada navegação. */
let nomePlataforma = IDENTIDADE_PADRAO.nome;

// NProgress configuration
NProgress.configure({ showSpinner: false, trickleSpeed: 200 });

/** Troca o favicon sem recarregar a página (quando o Admin salva uma nova identidade). */
function aplicarFavicon(url: string | null) {
    let link = document.querySelector<HTMLLinkElement>('link[rel="icon"]');
    if (!link) {
        link = document.createElement('link');
        link.rel = 'icon';
        document.head.appendChild(link);
    }
    link.href = url ?? '/favicon.ico';
}

/**
 * Tema MUI da identidade visual. Recargas parciais do Inertia (only: [...]) não trazem a prop
 * compartilhada `identidade`; nesse caso mantém a última recebida.
 */
function TemaDaIdentidade({ inicial, children }: { inicial?: Identidade; children: React.ReactNode }) {
    const [identidade, setIdentidade] = useState<Identidade>(inicial ?? IDENTIDADE_PADRAO);

    useEffect(() => router.on('navigate', (evento) => {
        const nova = evento.detail.page.props.identidade as Identidade | undefined;
        if (nova) setIdentidade(nova);
    }), []);

    useEffect(() => {
        nomePlataforma = identidade.nome;
        aplicarFavicon(identidade.favicon_url);
    }, [identidade]);

    const tema = useMemo(() => criarTema(identidade), [identidade]);

    return (
        <ThemeProvider theme={tema}>
            <CssBaseline />
            {children}
        </ThemeProvider>
    );
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${nomePlataforma}` : nomePlataforma),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const inicial = props.initialPage.props.identidade as Identidade | undefined;
        nomePlataforma = inicial?.nome ?? nomePlataforma;

        createRoot(el).render(
            <TemaDaIdentidade inicial={inicial}>
                <App {...props} />
            </TemaDaIdentidade>,
        );
    },
    progress: false, // handled by NProgress
});
