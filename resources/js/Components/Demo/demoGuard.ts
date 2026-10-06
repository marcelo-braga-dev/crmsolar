import { router } from '@inertiajs/react';
import type { DemoProps } from '@/types';

/**
 * Guarda de somente leitura do modo demonstração (DEMO.md). Só experiência: esmaece botões que
 * alteram dados e avisa em vez de abrir formulários. A garantia real é o middleware
 * BloqueiaEscritaNaDemonstracao no servidor.
 */

export const EVENTO_BLOQUEIO = 'demo:blocked';

/** Verbos de ação da plataforma (início do rótulo). "Cancelar" fica de fora: fecha diálogos. */
const ACAO = /^(nov[oa]s?|cadastrar|criar|adicionar|incluir|editar|alterar|mudar|excluir|remover|salvar|aprovar|reprovar|rejeitar|gerar contrato|emitir|enviar|importar|integrar|registrar|marcar|vincular|atribuir|encaminhar|reatribuir|ativar|desativar|convidar|conectar|confirmar|responder|mover|reativar|iniciar atendimento|descartar|desfazer|restaurar|trocar|aplicar|duplicar|assinar|reordenar)\b/i;

/** Caminhos de telas que só existem para alterar dados. */
const FORMULARIO = /\/(create|edit|editar|novo|nova|criar|senha)(\/|$)/;

const CANDIDATOS = 'button, a[href], [role="button"], [role="menuitem"]';

const ESCRITA = ['post', 'put', 'patch', 'delete'];

let liberados: string[] = [];

/** Caminho bate com uma entrada liberada ("*" = um segmento; terminada em "/" = prefixo). */
export function caminhoLiberado(caminho: string, entradas: string[] = liberados): boolean {
    return entradas.some((entrada) => {
        if (entrada.endsWith('/')) return caminho.startsWith(entrada);
        const a = entrada.split('/');
        const b = caminho.replace(/\/$/, '').split('/');
        return a.length === b.length && a.every((seg, i) => seg === '*' || seg === b[i]);
    });
}

export function bloqueado(metodo: string, url: string | URL): boolean {
    const caminho = new URL(url.toString(), window.location.origin).pathname;
    if (caminhoLiberado(caminho)) return false;

    return ESCRITA.includes(metodo.toLowerCase()) || FORMULARIO.test(caminho);
}

export function avisarBloqueio(mensagem?: string) {
    window.dispatchEvent(new CustomEvent(EVENTO_BLOQUEIO, { detail: { mensagem } }));
}

function alteraDados(el: Element): boolean {
    if (el.closest('[data-demo-allow]')) return false;

    const href = el.getAttribute('href');
    if (href && !href.startsWith('#')) {
        const caminho = new URL(href, window.location.origin).pathname;
        if (/\/pdf$/.test(caminho) || caminhoLiberado(caminho)) return false;
        if (FORMULARIO.test(caminho)) return true;
    }

    const rotulo = (el.getAttribute('aria-label') || el.getAttribute('title') || el.textContent || '').trim().slice(0, 80);

    return ACAO.test(rotulo);
}

/** Reavalia sempre (marca e desmarca): o React reaproveita elementos entre telas. */
function avaliarTudo() {
    document.querySelectorAll(CANDIDATOS).forEach((el) => {
        if (alteraDados(el)) {
            if (!el.hasAttribute('data-demo-muted')) el.setAttribute('data-demo-muted', '');
        } else if (el.hasAttribute('data-demo-muted')) {
            el.removeAttribute('data-demo-muted');
        }
    });
}

let instalado = false;

export function instalarGuardaDemo(demo: DemoProps) {
    liberados = demo.allowed_paths;
    if (instalado) return;
    instalado = true;

    const estilo = document.createElement('style');
    estilo.textContent = '[data-demo-muted]{opacity:.42 !important;filter:grayscale(.35);cursor:not-allowed !important}';
    document.head.appendChild(estilo);

    // Esmaecimento: reavaliado a cada mudança no DOM, agrupado por quadro.
    let agendado = false;
    const agendar = () => {
        if (agendado) return;
        agendado = true;
        requestAnimationFrame(() => {
            agendado = false;
            avaliarTudo();
        });
    };
    new MutationObserver(agendar).observe(document.body, { childList: true, subtree: true, characterData: true, attributes: true, attributeFilter: ['href', 'aria-label', 'title'] });
    agendar();

    // Clique em elemento esmaecido: avisa em vez de abrir formulário ou diálogo.
    document.addEventListener('click', (e) => {
        const alvo = (e.target as Element | null)?.closest?.('[data-demo-muted]');
        if (alvo) {
            e.preventDefault();
            e.stopPropagation();
            avisarBloqueio();
        }
    }, true);

    // Navegação do Inertia.
    router.on('before', (evento) => {
        const { method, url } = evento.detail.visit;
        if (!bloqueado(method, url)) return;

        evento.preventDefault();
        avisarBloqueio();
        // Telas com atualização otimista (ex.: arrastar no funil) voltam ao estado real.
        if (method !== 'get') router.reload();
    });

    // Requisições fora do roteador (axios e fetch).
    window.axios?.interceptors.request.use((config) => {
        if (ESCRITA.includes((config.method ?? 'get').toLowerCase()) && config.url && bloqueado(config.method ?? 'get', config.url)) {
            avisarBloqueio();
            return Promise.reject(new Error(demo.message));
        }
        return config;
    });

    const fetchOriginal = window.fetch.bind(window);
    window.fetch = (entrada, init) => {
        const metodo = (init?.method ?? (entrada instanceof Request ? entrada.method : 'GET')).toLowerCase();
        const url = entrada instanceof Request ? entrada.url : entrada.toString();
        if (ESCRITA.includes(metodo) && bloqueado(metodo, url)) {
            avisarBloqueio();
            return Promise.reject(new Error(demo.message));
        }
        return fetchOriginal(entrada, init);
    };
}
