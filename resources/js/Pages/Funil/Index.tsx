import React, { useEffect, useMemo, useRef, useState } from 'react';
import {
    Badge, Box, Button, Chip, Divider, InputAdornment, ListItemIcon, Menu, MenuItem, Paper, Slide, Snackbar, TextField,
    ToggleButton, ToggleButtonGroup, Tooltip, Typography, alpha, useMediaQuery, useTheme,
} from '@mui/material';
import {
    DndContext, DragEndEvent, DragOverlay, DragStartEvent, KeyboardSensor, PointerSensor, TouchSensor, useDroppable, useSensor, useSensors,
} from '@dnd-kit/core';
import SearchRoundedIcon from '@mui/icons-material/SearchRounded';
import InboxRoundedIcon from '@mui/icons-material/InboxRounded';
import EventBusyRoundedIcon from '@mui/icons-material/EventBusyRounded';
import OpenInNewRoundedIcon from '@mui/icons-material/OpenInNewRounded';
import PhoneInTalkRoundedIcon from '@mui/icons-material/PhoneInTalkRounded';
import ArrowForwardRoundedIcon from '@mui/icons-material/ArrowForwardRounded';
import ThumbDownAltRoundedIcon from '@mui/icons-material/ThumbDownAltRounded';
import ReplayRoundedIcon from '@mui/icons-material/ReplayRounded';
import ListAltRoundedIcon from '@mui/icons-material/ListAltRounded';
import SettingsRoundedIcon from '@mui/icons-material/SettingsRounded';
import ViewAgendaRoundedIcon from '@mui/icons-material/ViewAgendaRounded';
import ViewHeadlineRoundedIcon from '@mui/icons-material/ViewHeadlineRounded';
import ScheduleRoundedIcon from '@mui/icons-material/ScheduleRounded';
import AddRoundedIcon from '@mui/icons-material/AddRounded';
import ChecklistRoundedIcon from '@mui/icons-material/ChecklistRounded';
import PersonRoundedIcon from '@mui/icons-material/PersonRounded';
import CloseRoundedIcon from '@mui/icons-material/CloseRounded';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { ConfirmDialog } from '@/Components/UI/ConfirmDialog';
import { CardOrcamento, CardVisual } from '@/Components/Funil/CardOrcamento';
import { ColunaFunil } from '@/Components/Funil/ColunaFunil';
import { CaixaEntrada } from '@/Components/Funil/CaixaEntrada';
import { DialogAgenda, DialogPerda } from '@/Components/Funil/Dialogos';
import { PainelOrcamento } from '@/Components/Funil/PainelOrcamento';
import {
    Area, CAIXA, CanalContato, CardFunil, ColunaFunil as Coluna, Densidade, DetalheOrcamento, MotivoPerda, Ordem, ResumoFunil, TipoEtapa,
    abrirContato, moedaCompacta,
} from '@/Components/Funil/tipos';

interface Filtros {
    consultor_id?: string; busca?: string; grupo?: string; atrasados?: boolean; sem_passo?: boolean; sla?: boolean; ordem?: Ordem;
}

interface Props {
    area: Area;
    colunas: Coluna[];
    caixa: CardFunil[];
    resumo: ResumoFunil;
    motivos: MotivoPerda[];
    consultores: { id: number; name: string; status?: boolean }[];
    filtros: Filtros;
}

const GRUPOS = ['B1', 'B2', 'B3', 'A4', 'A3a', 'A3', 'A2', 'A1'];
const CHAVE_RECOLHIDAS = 'funil.colunasRecolhidas';
const CHAVE_DENSIDADE = 'funil.densidade';
/** Props que mudam com uma movimentação — o resto (motivos, consultores) não é recarregado. */
const PROPS_DO_QUADRO = ['colunas', 'caixa', 'resumo', 'flash'];
const ORDENS: { valor: Ordem; rotulo: string }[] = [
    { valor: 'prioridade', rotulo: 'Prioridade' }, { valor: 'valor', rotulo: 'Maior valor' }, { valor: 'antigo', rotulo: 'Mais tempo na etapa' },
];

/** Card em foco de uma ação (arraste, menu, painel ou caixa de entrada) e a coluna de onde ele saiu. */
interface Alvo { card: CardFunil; origem: string; origemTipo: TipoEtapa | 'caixa' }

type ModoAgenda = 'contato' | 'iniciar' | 'reativar' | 'mover';

/** Aviso flutuante do quadro, com ação opcional (desfazer, registrar contato). */
interface Aviso { mensagem: string; acao?: { rotulo: string; executar: () => void } }

/**
 * Regras de soltura espelhando o servidor (FunilService) — só para orientar o usuário;
 * a decisão final é sempre do backend.
 */
function podeSoltar(origemTipo: TipoEtapa | 'caixa', destino: Coluna, area: Area): boolean {
    const emNegociacao = origemTipo === 'aberta' || origemTipo === CAIXA;
    switch (destino.tipo) {
        case 'aberta': return emNegociacao || origemTipo === 'perdido' || (origemTipo === 'aprovacao' && area === 'admin');
        case 'aprovacao': return emNegociacao;
        case 'ganho': return origemTipo === 'aprovacao' && area === 'admin';
        case 'perdido': return emNegociacao;
    }
}

const ler = <T,>(chave: string): T | null => {
    try { const v = window.localStorage.getItem(chave); return v ? JSON.parse(v) : null; } catch { return null; }
};
const gravar = (chave: string, valor: unknown) => {
    try { window.localStorage.setItem(chave, JSON.stringify(valor)); } catch { /* armazenamento indisponível */ }
};

const alvoDoDetalhe = (d: DetalheOrcamento): Alvo => ({ card: d, origem: String(d.etapa?.id ?? CAIXA), origemTipo: d.etapa?.tipo ?? CAIXA });

export default function FunilIndex({ area, colunas: colunasServidor, caixa, resumo, motivos, consultores, filtros }: Props) {
    const tema = useTheme();
    const celular = useMediaQuery(tema.breakpoints.down('sm'));

    const [colunas, setColunas] = useState(colunasServidor);
    useEffect(() => setColunas(colunasServidor), [colunasServidor]);

    const [busca, setBusca] = useState(filtros.busca ?? '');
    const campoBusca = useRef<HTMLInputElement>(null);
    const [caixaAberta, setCaixaAberta] = useState(false);
    const [painelId, setPainelId] = useState<number | null>(null);
    const [arrastando, setArrastando] = useState<Alvo | null>(null);
    const [enviando, setEnviando] = useState(false);
    /** Card com requisição em andamento: só ele deixa de ser arrastável. */
    const [salvandoId, setSalvandoId] = useState<number | null>(null);
    const [densidade, setDensidade] = useState<Densidade>(() => ler<Densidade>(CHAVE_DENSIDADE) ?? 'confortavel');
    const trocarDensidade = (nova: Densidade | null) => { if (nova) { setDensidade(nova); gravar(CHAVE_DENSIDADE, nova); } };
    const [aviso, setAviso] = useState<Aviso | null>(null);
    /** Etapa exibida no celular (uma coluna por vez). */
    const [etapaCelular, setEtapaCelular] = useState<number | null>(null);

    // Seleção em lote (admin): null = modo desligado.
    const [selecao, setSelecao] = useState<number[] | null>(null);
    const [menuLote, setMenuLote] = useState<{ el: HTMLElement; tipo: 'mover' | 'reatribuir' } | null>(null);
    const [perdaLote, setPerdaLote] = useState(false);

    // Diálogos
    const [perda, setPerda] = useState<Alvo | null>(null);
    const [agenda, setAgenda] = useState<{ alvo: Alvo; modo: ModoAgenda; destino?: Coluna } | null>(null);
    const [confirmacao, setConfirmacao] = useState<{ alvo: Alvo; destino: Coluna; titulo: string; mensagem: string; rotulo: string } | null>(null);
    const [menu, setMenu] = useState<{ el: HTMLElement; alvo: Alvo } | null>(null);
    const [moverPara, setMoverPara] = useState<{ el: HTMLElement; alvo: Alvo } | null>(null);

    const [recolhidas, setRecolhidas] = useState<number[]>(
        () => ler<number[]>(CHAVE_RECOLHIDAS) ?? colunasServidor.filter((c) => c.tipo === 'perdido').map((c) => c.id),
    );
    const alternarColuna = (id: number) => setRecolhidas((atual) => {
        const novo = atual.includes(id) ? atual.filter((x) => x !== id) : [...atual, id];
        gravar(CHAVE_RECOLHIDAS, novo);
        return novo;
    });

    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 6 } }),
        useSensor(TouchSensor, { activationConstraint: { delay: 180, tolerance: 6 } }),
        useSensor(KeyboardSensor),
    );

    const primeiraAberta = colunas.find((c) => c.tipo === 'aberta');
    const etapasAbertas = colunas.filter((c) => c.tipo === 'aberta');
    const tipoDaOrigem = (origem: string): TipoEtapa | 'caixa' =>
        origem === CAIXA ? CAIXA : (colunas.find((c) => String(c.id) === origem)?.tipo ?? 'aberta');

    // ── Requisições ───────────────────────────────────────────────────────

    const rota = (acao: string, id?: number) => route(`${area}.funil.${acao}`, id);
    const opcoesPara = (cardId: number) => ({
        preserveScroll: true,
        preserveState: true,
        only: PROPS_DO_QUADRO,
        onStart: () => { setEnviando(true); setSalvandoId(cardId); },
        onFinish: () => { setEnviando(false); setSalvandoId(null); },
    });
    /** datetime-local (horário do navegador) → ISO com fuso, para o servidor gravar o instante certo. */
    const iso = (data: string | null) => (data ? new Date(data).toISOString() : null);
    /** A resposta do Inertia volta 200 mesmo quando o servidor recusa: a recusa vem no flash. */
    const recusado = (pagina: { props: Record<string, unknown> }) => !!(pagina.props.flash as { error?: string } | undefined)?.error;

    /** Move o card na tela antes da resposta; o servidor devolve o quadro real (e desfaz se recusar). */
    const moverLocalmente = (alvo: Alvo, destino: Coluna) => setColunas((atual) => atual.map((c) => {
        if (String(c.id) === alvo.origem) {
            return { ...c, cards: c.cards.filter((k) => k.id !== alvo.card.id), quantidade: c.quantidade - 1, valor: c.valor - alvo.card.valor };
        }
        if (c.id === destino.id) {
            return { ...c, cards: [{ ...alvo.card, dias_na_etapa: 0, sla_estourado: false }, ...c.cards], quantidade: c.quantidade + 1, valor: c.valor + alvo.card.valor };
        }
        return c;
    }));

    const mover = (alvo: Alvo, destino: Coluna, proximoContato: string | null = null, permitirDesfazer = true) => {
        moverLocalmente(alvo, destino);
        const origem = colunas.find((c) => String(c.id) === alvo.origem);
        // Só movimentos entre etapas abertas são desfeitos com um clique; os demais mudam status.
        const desfazivel = permitirDesfazer && origem?.tipo === 'aberta' && destino.tipo === 'aberta';

        router.post(rota('mover', alvo.card.id), { etapa_id: destino.id, origem: alvo.origem, proximo_contato_em: iso(proximoContato) }, {
            ...opcoesPara(alvo.card.id),
            onSuccess: (pagina) => {
                if (!desfazivel || !origem || recusado(pagina)) return;
                setAviso({
                    mensagem: `#${alvo.card.id} movido para ${destino.nome}`,
                    acao: {
                        rotulo: 'Desfazer',
                        executar: () => mover({ card: alvo.card, origem: String(destino.id), origemTipo: 'aberta' }, origem, null, false),
                    },
                });
            },
        });
    };

    const perder = (alvo: Alvo, motivoId: number, observacao: string) => {
        router.post(rota('perder', alvo.card.id), { motivo_perda_id: motivoId, observacao, origem: alvo.origem }, { ...opcoesPara(alvo.card.id), onSuccess: () => setPerda(null) });
    };

    const confirmarAgenda = ({ nota, data }: { nota: string; data: string | null }) => {
        if (!agenda) return;
        const { alvo, modo, destino } = agenda;
        const fechar = { ...opcoesPara(alvo.card.id), onSuccess: () => setAgenda(null) };
        if (modo === 'contato') {
            router.post(rota('contato', alvo.card.id), { nota, proximo_contato_em: iso(data) }, fechar);
        } else if (modo === 'reativar') {
            router.post(rota('reativar', alvo.card.id), { etapa_id: destino?.id, proximo_contato_em: iso(data) }, fechar);
        } else if (destino) {
            setAgenda(null);
            mover(alvo, destino, data);
        }
    };

    /** Decide o que fazer ao levar um card para uma coluna (arrastando, menu "Mover para" ou painel). */
    const levarPara = (alvo: Alvo, destino: Coluna) => {
        if (String(destino.id) === alvo.origem || !podeSoltar(alvo.origemTipo, destino, area)) return;

        if (destino.tipo === 'perdido') return setPerda(alvo);
        if (alvo.origemTipo === 'perdido') return setAgenda({ alvo, modo: 'reativar', destino });
        if (alvo.origemTipo === CAIXA) return setAgenda({ alvo, modo: 'iniciar', destino });
        if (destino.tipo === 'aprovacao') {
            return setConfirmacao({ alvo, destino, titulo: 'Enviar para aprovação', rotulo: 'Enviar',
                mensagem: `Enviar o orçamento #${alvo.card.id} (${alvo.card.cliente}) para aprovação do administrador?` });
        }
        if (destino.tipo === 'ganho') {
            return setConfirmacao({ alvo, destino, titulo: 'Aprovar venda', rotulo: 'Aprovar',
                mensagem: `Aprovar o orçamento #${alvo.card.id} (${alvo.card.cliente})? Ele passa a contar como venda fechada.` });
        }
        if (alvo.origemTipo === 'aprovacao') {
            return setConfirmacao({ alvo, destino, titulo: 'Reprovar orçamento', rotulo: 'Reprovar',
                mensagem: `Reprovar o orçamento #${alvo.card.id} e devolvê-lo para "${destino.nome}"? O consultor verá o selo "Reprovado".` });
        }
        // Todo negócio precisa de um próximo passo: avançar sem contato agendado pede a data.
        if (!alvo.card.proximo_contato_em) return setAgenda({ alvo, modo: 'mover', destino });
        mover(alvo, destino);
    };

    // ── Contato rápido ────────────────────────────────────────────────────

    /** Depois de abrir o WhatsApp ou ligar, oferece registrar o contato (o próximo passo). */
    const contatar = (alvo: Alvo, canal: CanalContato) => {
        abrirContato(alvo.card, canal);
        window.setTimeout(() => setAviso({
            mensagem: `Falou com ${alvo.card.cliente}?`,
            acao: { rotulo: 'Registrar contato', executar: () => setAgenda({ alvo, modo: 'contato' }) },
        }), 800);
    };

    // ── Seleção em lote (admin) ───────────────────────────────────────────

    const alternarSelecao = (id: number) => setSelecao((atual) => atual && (atual.includes(id) ? atual.filter((x) => x !== id) : [...atual, id]));
    const executarLote = (dados: Record<string, unknown>) => {
        if (!selecao?.length) return;
        router.post(rota('lote'), { ids: selecao, ...dados }, {
            preserveScroll: true, preserveState: true, only: PROPS_DO_QUADRO,
            onStart: () => setEnviando(true), onFinish: () => setEnviando(false),
            onSuccess: (pagina) => { if (!recusado(pagina)) { setSelecao(null); setPerdaLote(false); } },
        });
    };

    // ── Arrastar e soltar ─────────────────────────────────────────────────

    const aoIniciarArraste = (e: DragStartEvent) => {
        const { card, origem } = e.active.data.current as { card: CardFunil; origem: string };
        setArrastando({ card, origem, origemTipo: tipoDaOrigem(origem) });
    };

    const aoSoltar = (e: DragEndEvent) => {
        const alvo = arrastando;
        setArrastando(null);
        const destino = e.over?.data.current?.coluna as Coluna | undefined;
        if (alvo && destino) levarPara(alvo, destino);
    };

    // ── Filtros ───────────────────────────────────────────────────────────

    const filtrar = (novos: Partial<Filtros>) => {
        const params = Object.fromEntries(
            Object.entries({ ...filtros, busca, ...novos }).filter(([, v]) => v !== '' && v != null && v !== false && v !== 'prioridade'),
        );
        router.get(rota('index'), params, { preserveState: true, replace: true, preserveScroll: true });
    };

    // Busca instantânea: dispara 350 ms depois da última tecla.
    useEffect(() => {
        if (busca === (filtros.busca ?? '')) return;
        const t = window.setTimeout(() => filtrar({ busca }), 350);
        return () => window.clearTimeout(t);
    }, [busca]); // eslint-disable-line react-hooks/exhaustive-deps

    // Atalhos: "/" busca, "c" caixa de entrada, Esc sai da seleção.
    useEffect(() => {
        const aoTeclar = (e: KeyboardEvent) => {
            const el = e.target as HTMLElement;
            if (e.ctrlKey || e.metaKey || e.altKey || el.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(el.tagName)) return;
            if (document.querySelector('[role="dialog"], [role="presentation"].MuiDrawer-root, .MuiMenu-root')) return;
            if (e.key === '/') { e.preventDefault(); campoBusca.current?.focus(); }
            if (e.key === 'c' || e.key === 'C') setCaixaAberta(true);
            if (e.key === 'Escape') setSelecao(null);
        };
        window.addEventListener('keydown', aoTeclar);
        return () => window.removeEventListener('keydown', aoTeclar);
    }, []);

    const abrirCompleto = (id: number) => router.visit(route(`${area}.orcamentos.show`, id));
    const corDaOrigem = (origem: string) => colunas.find((c) => String(c.id) === origem)?.cor ?? '#64748B';
    const filtrosAtivos = !!(filtros.busca || filtros.grupo || filtros.consultor_id || filtros.atrasados || filtros.sem_passo || filtros.sla || filtros.ordem);

    /** Base da barra de participação das colunas e da faixa do funil: negociações abertas + Em aprovação. */
    const valorDoFunil = useMemo(
        () => colunas.filter((c) => c.tipo === 'aberta' || c.tipo === 'aprovacao').reduce((t, c) => t + c.valor, 0),
        [colunas],
    );

    const destinosPossiveis = useMemo(
        () => (alvo: Alvo) => colunas.filter((c) => String(c.id) !== alvo.origem && podeSoltar(alvo.origemTipo, c, area)),
        [colunas, area],
    );

    const colunaCelular = colunas.find((c) => c.id === etapaCelular) ?? primeiraAberta ?? colunas[0];
    const colunasVisiveis = celular && colunaCelular ? [colunaCelular] : colunas;

    return (
        <AppLayout title="Funil de vendas">
            <Head title="Funil de vendas" />

            <PageHeader
                title="Funil de vendas"
                subtitle={area === 'admin' ? 'Negociações de todos os consultores' : 'Acompanhe e avance suas negociações'}
                breadcrumbs={[{ label: 'Orçamentos' }, { label: 'Funil' }]}
                action={
                    <Box sx={{ display: 'flex', gap: 1, flexWrap: 'wrap', justifyContent: 'flex-end' }}>
                        {area === 'admin' && (
                            <Tooltip title="Etapas, cores e motivos de perda">
                                <Button component={Link} href={route('admin.configuracoes.funil.index')} color="inherit" startIcon={<SettingsRoundedIcon />}>
                                    Configurar
                                </Button>
                            </Tooltip>
                        )}
                        <Button component={Link} href={route(`${area}.orcamentos.index`)} color="inherit" startIcon={<ListAltRoundedIcon />}>
                            Lista
                        </Button>
                        {area === 'consultor' && (
                            <Button component={Link} href={route('consultor.orcamentos.selecionar_grupo')} variant="outlined" startIcon={<AddRoundedIcon />}>
                                Novo orçamento
                            </Button>
                        )}
                        <Tooltip title="Atalho: C">
                            <Badge badgeContent={resumo.caixa} color="warning" max={99}>
                                <Button variant="contained" startIcon={<InboxRoundedIcon />} onClick={() => setCaixaAberta(true)}>
                                    Caixa de entrada
                                </Button>
                            </Badge>
                        </Tooltip>
                    </Box>
                }
            />

            {/* Indicadores */}
            <Box sx={{ display: 'grid', gridTemplateColumns: { xs: 'repeat(2, minmax(0, 1fr))', md: 'repeat(5, minmax(0, 1fr))' }, gap: 1.5, mb: 2 }}>
                <Indicador rotulo="Em negociação" valor={String(resumo.abertos)} detalhe={moedaCompacta(resumo.valor_aberto)} />
                <Indicador rotulo="Previsão ponderada" valor={moedaCompacta(resumo.ponderado)} detalhe="valor × probabilidade da etapa" />
                <Indicador
                    rotulo="Contatos atrasados" valor={String(resumo.atrasados)} detalhe={filtros.atrasados ? 'filtrando — clique para limpar' : 'clique para filtrar'}
                    alerta={resumo.atrasados > 0} onClick={() => filtrar({ atrasados: !filtros.atrasados })} ativo={!!filtros.atrasados}
                />
                <Indicador
                    rotulo="Sem próximo passo" valor={String(resumo.sem_passo)} detalhe={filtros.sem_passo ? 'filtrando — clique para limpar' : 'negociações sem contato agendado'}
                    atencao={resumo.sem_passo > 0} onClick={() => filtrar({ sem_passo: !filtros.sem_passo })} ativo={!!filtros.sem_passo}
                />
                <Indicador
                    rotulo="Caixa de entrada" valor={String(resumo.caixa)} detalhe={`aguardando atendimento · alerta após ${resumo.dias_caixa_entrada}d`}
                    alerta={caixa.some((c) => c.esfriando)} onClick={() => setCaixaAberta(true)}
                />
            </Box>

            <FaixaFunil colunas={colunas} total={valorDoFunil} />

            {/* Filtros */}
            <Box sx={{ display: 'flex', gap: 1, mb: 2, flexWrap: 'wrap', alignItems: 'center' }}>
                <TextField
                    size="small" placeholder="Buscar cliente ou #número" value={busca} inputRef={campoBusca}
                    onChange={(e) => setBusca(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && filtrar({})}
                    InputProps={{
                        startAdornment: <InputAdornment position="start"><SearchRoundedIcon fontSize="small" /></InputAdornment>,
                        endAdornment: !busca && !celular ? <InputAdornment position="end"><Box component="kbd" sx={kbd}>/</Box></InputAdornment> : undefined,
                    }}
                    sx={{ minWidth: 240, flex: { xs: '1 1 100%', sm: 'none' }, bgcolor: 'background.paper' }}
                />
                {area === 'admin' && (
                    <TextField
                        select size="small" value={filtros.consultor_id ?? ''} onChange={(e) => filtrar({ consultor_id: e.target.value })}
                        sx={{ minWidth: 190, bgcolor: 'background.paper' }} SelectProps={{ displayEmpty: true }}
                    >
                        <MenuItem value="">Todos os consultores</MenuItem>
                        {consultores.map((c) => <MenuItem key={c.id} value={String(c.id)}>{c.name}</MenuItem>)}
                    </TextField>
                )}
                <TextField
                    select size="small" value={filtros.grupo ?? ''} onChange={(e) => filtrar({ grupo: e.target.value })}
                    sx={{ minWidth: 140, bgcolor: 'background.paper' }} SelectProps={{ displayEmpty: true }}
                >
                    <MenuItem value="">Todos os grupos</MenuItem>
                    {GRUPOS.map((g) => <MenuItem key={g} value={g}>Grupo {g}</MenuItem>)}
                </TextField>
                <FiltroChip rotulo="Atrasados" icone={<EventBusyRoundedIcon />} ativo={!!filtros.atrasados} onClick={() => filtrar({ atrasados: !filtros.atrasados })} />
                <FiltroChip rotulo="Sem próximo passo" icone={<PhoneInTalkRoundedIcon />} ativo={!!filtros.sem_passo} onClick={() => filtrar({ sem_passo: !filtros.sem_passo })} />
                <FiltroChip rotulo="Prazo estourado" icone={<ScheduleRoundedIcon />} ativo={!!filtros.sla} onClick={() => filtrar({ sla: !filtros.sla })} />
                {filtrosAtivos && (
                    <Button size="small" onClick={() => { setBusca(''); router.get(rota('index'), {}, { preserveState: true, replace: true }); }}>
                        Limpar filtros
                    </Button>
                )}
                <Box sx={{ flex: 1 }} />
                <TextField
                    select size="small" value={filtros.ordem ?? 'prioridade'} onChange={(e) => filtrar({ ordem: e.target.value as Ordem })}
                    sx={{ minWidth: 170, bgcolor: 'background.paper' }} label="Ordenar"
                >
                    {ORDENS.map((o) => <MenuItem key={o.valor} value={o.valor}>{o.rotulo}</MenuItem>)}
                </TextField>
                {area === 'admin' && !celular && (
                    <Button
                        size="small" variant={selecao ? 'contained' : 'outlined'} color={selecao ? 'primary' : 'inherit'} startIcon={<ChecklistRoundedIcon />}
                        onClick={() => setSelecao(selecao ? null : [])} sx={{ bgcolor: selecao ? undefined : 'background.paper', height: 40 }}
                    >
                        Selecionar
                    </Button>
                )}
                {!celular && (
                    <ToggleButtonGroup
                        size="small" exclusive value={densidade} onChange={(_, v) => trocarDensidade(v)}
                        aria-label="Densidade dos cards" sx={{ bgcolor: 'background.paper' }}
                    >
                        <ToggleButton value="confortavel" aria-label="Confortável"><Tooltip title="Cards completos"><ViewAgendaRoundedIcon fontSize="small" /></Tooltip></ToggleButton>
                        <ToggleButton value="compacta" aria-label="Compacta"><Tooltip title="Cards compactos"><ViewHeadlineRoundedIcon fontSize="small" /></Tooltip></ToggleButton>
                    </ToggleButtonGroup>
                )}
            </Box>

            {/* Celular: uma etapa por vez, escolhida nas abas */}
            {celular && (
                <Box sx={{ display: 'flex', gap: 0.75, overflowX: 'auto', pb: 1, mb: 1, mx: -2, px: 2, '&::-webkit-scrollbar': { display: 'none' } }}>
                    {colunas.map((c) => {
                        const ativa = c.id === colunaCelular?.id;
                        return (
                            <Chip
                                key={c.id} clickable onClick={() => setEtapaCelular(c.id)}
                                label={<Box component="span" sx={{ display: 'inline-flex', gap: 0.75 }}>{c.nome}<b>{c.quantidade}</b></Box>}
                                sx={{
                                    flexShrink: 0, fontWeight: 600, border: '1px solid',
                                    borderColor: ativa ? c.cor : 'divider', bgcolor: ativa ? alpha(c.cor, 0.12) : 'background.paper', color: ativa ? c.cor : 'text.secondary',
                                }}
                            />
                        );
                    })}
                </Box>
            )}

            {/* Quadro */}
            <DndContext sensors={sensors} onDragStart={aoIniciarArraste} onDragEnd={aoSoltar} onDragCancel={() => setArrastando(null)}>
                <Box
                    sx={{
                        display: 'flex', gap: 1.5, overflowX: 'auto', pb: 1.5, alignItems: 'stretch',
                        height: { xs: 'calc(100vh - 300px)', sm: 'calc(100vh - 370px)' }, minHeight: { xs: 380, sm: 460 },
                        '&::-webkit-scrollbar': { height: 8 }, '&::-webkit-scrollbar-thumb': { bgcolor: 'rgba(15,23,42,.18)', borderRadius: 4 },
                    }}
                >
                    {colunasVisiveis.map((coluna) => (
                        <ColunaFunil
                            key={coluna.id}
                            coluna={coluna}
                            aceita={arrastando && String(coluna.id) !== arrastando.origem ? podeSoltar(arrastando.origemTipo, coluna, area) : null}
                            recolhida={recolhidas.includes(coluna.id) && !(arrastando && coluna.tipo === 'perdido')}
                            valorDoFunil={valorDoFunil}
                            cheia={celular}
                            onAlternar={() => alternarColuna(coluna.id)}
                        >
                            {coluna.cards.map((card) => {
                                const alvo: Alvo = { card, origem: String(coluna.id), origemTipo: coluna.tipo };
                                return (
                                    <CardOrcamento
                                        key={card.id}
                                        card={card}
                                        cor={coluna.cor}
                                        slaDias={coluna.tipo === 'aberta' || coluna.tipo === 'aprovacao' ? coluna.sla_dias : null}
                                        densidade={celular ? 'confortavel' : densidade}
                                        salvando={salvandoId === card.id}
                                        selecionado={selecao ? selecao.includes(card.id) : undefined}
                                        origem={String(coluna.id)}
                                        arrastavel={!selecao && !celular && salvandoId !== card.id && coluna.tipo !== 'ganho' && (coluna.tipo !== 'aprovacao' || area === 'admin')}
                                        mostrarConsultor={area === 'admin'}
                                        onAbrir={() => (selecao ? alternarSelecao(card.id) : setPainelId(card.id))}
                                        onMenu={(e) => setMenu({ el: e.currentTarget, alvo })}
                                        onContato={coluna.tipo === 'ganho' ? undefined : (canal) => contatar(alvo, canal)}
                                    />
                                );
                            })}
                        </ColunaFunil>
                    ))}
                </Box>

                {/* Zona "Perdido" sempre à mão durante o arraste, mesmo com a coluna fora da tela. */}
                {arrastando && (arrastando.origemTipo === 'aberta' || arrastando.origemTipo === CAIXA) && (
                    <ZonaPerdido coluna={colunas.find((c) => c.tipo === 'perdido')} />
                )}

                <DragOverlay dropAnimation={{ duration: 180, easing: 'cubic-bezier(0.2, 0.8, 0.2, 1)' }}>
                    {arrastando && (
                        <Box sx={{ width: 282 }}>
                            <CardVisual card={arrastando.card} cor={corDaOrigem(arrastando.origem)} mostrarConsultor={area === 'admin'} densidade={densidade} sobreposto />
                        </Box>
                    )}
                </DragOverlay>
            </DndContext>

            {/* Barra de ações em lote */}
            <Box sx={{ position: 'fixed', bottom: 24, left: 0, right: 0, zIndex: 1200, display: 'flex', justifyContent: 'center', pointerEvents: 'none' }}>
            <Slide direction="up" in={!!selecao} mountOnEnter unmountOnExit>
                <Paper
                    elevation={8}
                    sx={{
                        pointerEvents: 'auto', display: 'flex', alignItems: 'center', gap: 1, px: 2, py: 1.25, borderRadius: 3, bgcolor: '#0F172A', color: '#fff',
                    }}
                >
                    <Typography variant="body2" fontWeight={600} sx={{ mr: 1, whiteSpace: 'nowrap' }}>
                        {selecao?.length ? `${selecao.length} selecionado(s)` : 'Clique nos cards para selecionar'}
                    </Typography>
                    <Button size="small" color="inherit" startIcon={<ArrowForwardRoundedIcon />} disabled={!selecao?.length || enviando}
                        onClick={(e) => setMenuLote({ el: e.currentTarget, tipo: 'mover' })}>Mover</Button>
                    <Button size="small" color="inherit" startIcon={<PersonRoundedIcon />} disabled={!selecao?.length || enviando}
                        onClick={(e) => setMenuLote({ el: e.currentTarget, tipo: 'reatribuir' })}>Responsável</Button>
                    <Button size="small" color="inherit" startIcon={<ThumbDownAltRoundedIcon />} disabled={!selecao?.length || enviando}
                        onClick={() => setPerdaLote(true)}>Perdido</Button>
                    <Divider orientation="vertical" flexItem sx={{ borderColor: 'rgba(255,255,255,.2)', mx: 0.5 }} />
                    <Button size="small" color="inherit" startIcon={<CloseRoundedIcon />} onClick={() => setSelecao(null)}>Cancelar</Button>
                </Paper>
            </Slide>
            </Box>

            <Menu anchorEl={menuLote?.el} open={!!menuLote} onClose={() => setMenuLote(null)}>
                {menuLote?.tipo === 'mover' && etapasAbertas.map((c) => (
                    <MenuItem key={c.id} onClick={() => { setMenuLote(null); executarLote({ acao: 'mover', etapa_id: c.id }); }}>
                        <Box sx={{ width: 10, height: 10, borderRadius: '50%', bgcolor: c.cor, mr: 1.5 }} />{c.nome}
                    </MenuItem>
                ))}
                {menuLote?.tipo === 'reatribuir' && consultores.filter((c) => c.status !== false).map((c) => (
                    <MenuItem key={c.id} onClick={() => { setMenuLote(null); executarLote({ acao: 'reatribuir', consultor_id: c.id }); }}>{c.name}</MenuItem>
                ))}
            </Menu>

            {/* Menu de ações do card (também é a alternativa acessível ao arraste) */}
            <Menu anchorEl={menu?.el} open={!!menu} onClose={() => setMenu(null)}>
                {menu && [
                    <MenuItem key="abrir" onClick={() => { setMenu(null); abrirCompleto(menu.alvo.card.id); }}>
                        <ListItemIcon><OpenInNewRoundedIcon fontSize="small" /></ListItemIcon>Abrir orçamento
                    </MenuItem>,
                    menu.alvo.origemTipo !== 'ganho' && menu.alvo.origemTipo !== 'perdido' && (
                        <MenuItem key="contato" onClick={() => { setAgenda({ alvo: menu.alvo, modo: 'contato' }); setMenu(null); }}>
                            <ListItemIcon><PhoneInTalkRoundedIcon fontSize="small" /></ListItemIcon>Registrar contato
                        </MenuItem>
                    ),
                    destinosPossiveis(menu.alvo).length > 0 && (
                        <MenuItem key="mover" onClick={(e) => { setMoverPara({ el: e.currentTarget, alvo: menu.alvo }); setMenu(null); }}>
                            <ListItemIcon><ArrowForwardRoundedIcon fontSize="small" /></ListItemIcon>Mover para…
                        </MenuItem>
                    ),
                    menu.alvo.origemTipo === 'perdido' && primeiraAberta && (
                        <MenuItem key="reativar" onClick={() => { setAgenda({ alvo: menu.alvo, modo: 'reativar', destino: primeiraAberta }); setMenu(null); }}>
                            <ListItemIcon><ReplayRoundedIcon fontSize="small" /></ListItemIcon>Reativar negociação
                        </MenuItem>
                    ),
                    (menu.alvo.origemTipo === 'aberta') && <Divider key="div" />,
                    (menu.alvo.origemTipo === 'aberta') && (
                        <MenuItem key="perder" onClick={() => { setPerda(menu.alvo); setMenu(null); }} sx={{ color: 'error.main' }}>
                            <ListItemIcon><ThumbDownAltRoundedIcon fontSize="small" color="error" /></ListItemIcon>Marcar como perdido
                        </MenuItem>
                    ),
                ]}
            </Menu>

            <Menu anchorEl={moverPara?.el} open={!!moverPara} onClose={() => setMoverPara(null)}>
                {moverPara && destinosPossiveis(moverPara.alvo).map((c) => (
                    <MenuItem key={c.id} onClick={() => { const alvo = moverPara.alvo; setMoverPara(null); levarPara(alvo, c); }}>
                        <Box sx={{ width: 10, height: 10, borderRadius: '50%', bgcolor: c.cor, mr: 1.5 }} />{c.nome}
                    </MenuItem>
                ))}
            </Menu>

            <PainelOrcamento
                area={area}
                orcamentoId={painelId}
                versao={colunasServidor}
                consultores={consultores}
                onFechar={() => setPainelId(null)}
                onContato={(d, canal) => contatar(alvoDoDetalhe(d), canal)}
                onRegistrarContato={(d) => setAgenda({ alvo: alvoDoDetalhe(d), modo: 'contato' })}
                onMover={(d, el) => setMoverPara({ el, alvo: alvoDoDetalhe(d) })}
                onPerder={(d) => setPerda(alvoDoDetalhe(d))}
                onReativar={(d) => primeiraAberta && setAgenda({ alvo: alvoDoDetalhe(d), modo: 'reativar', destino: primeiraAberta })}
                onReatribuir={(d, consultorId) => router.post(rota('reatribuir', d.id), { consultor_id: consultorId }, opcoesPara(d.id))}
                onAbrirCompleto={abrirCompleto}
            />

            <CaixaEntrada
                aberta={caixaAberta}
                itens={caixa}
                diasLimite={resumo.dias_caixa_entrada}
                mostrarConsultor={area === 'admin'}
                onFechar={() => setCaixaAberta(false)}
                onAbrir={(card) => setPainelId(card.id)}
                onIniciar={(card) => primeiraAberta && setAgenda({ alvo: { card, origem: CAIXA, origemTipo: CAIXA }, modo: 'iniciar', destino: primeiraAberta })}
                onDescartar={(card) => setPerda({ card, origem: CAIXA, origemTipo: CAIXA })}
            />

            <DialogPerda
                aberto={!!perda || perdaLote}
                cliente={perda ? `#${perda.card.id} · ${perda.card.cliente}` : `${selecao?.length ?? 0} orçamento(s) selecionado(s)`}
                motivos={motivos}
                enviando={enviando}
                onFechar={() => { setPerda(null); setPerdaLote(false); }}
                onConfirmar={(motivo, obs) => (perdaLote ? executarLote({ acao: 'perder', motivo_perda_id: motivo, observacao: obs }) : perda && perder(perda, motivo, obs))}
            />

            <DialogAgenda
                aberto={!!agenda}
                titulo={{
                    contato: 'Registrar contato', reativar: 'Reativar negociação',
                    iniciar: `Iniciar atendimento — ${agenda?.destino?.nome ?? ''}`, mover: `Mover para ${agenda?.destino?.nome ?? ''}`,
                }[agenda?.modo ?? 'contato']}
                cliente={agenda ? `#${agenda.alvo.card.id} · ${agenda.alvo.card.cliente}` : undefined}
                descricao={agenda?.modo === 'mover' ? 'Esta negociação não tem próximo contato. Agende o próximo passo (ou escolha "Sem data").' : undefined}
                comNota={agenda?.modo === 'contato'}
                textoConfirmar={{ contato: 'Registrar', reativar: 'Reativar', iniciar: 'Iniciar', mover: 'Mover' }[agenda?.modo ?? 'contato']}
                enviando={enviando}
                onFechar={() => setAgenda(null)}
                onConfirmar={confirmarAgenda}
            />

            <ConfirmDialog
                open={!!confirmacao}
                title={confirmacao?.titulo ?? ''}
                message={confirmacao?.mensagem ?? ''}
                confirmLabel={confirmacao?.rotulo ?? 'Confirmar'}
                onConfirm={() => { if (confirmacao) { const { alvo, destino } = confirmacao; setConfirmacao(null); mover(alvo, destino); } }}
                onCancel={() => setConfirmacao(null)}
            />

            <Snackbar
                open={!!aviso} onClose={(_, motivo) => motivo !== 'clickaway' && setAviso(null)} autoHideDuration={aviso?.acao?.rotulo === 'Desfazer' ? 6000 : 12000}
                anchorOrigin={{ vertical: 'bottom', horizontal: 'left' }} message={aviso?.mensagem}
                action={aviso?.acao && (
                    <Button size="small" color="secondary" onClick={() => { const a = aviso.acao; setAviso(null); a?.executar(); }}>
                        {aviso.acao.rotulo}
                    </Button>
                )}
            />
        </AppLayout>
    );
}

const kbd = {
    px: 0.75, borderRadius: 0.75, border: '1px solid', borderColor: 'divider', fontSize: '0.7rem', color: 'text.secondary', fontFamily: 'inherit', lineHeight: 1.6,
};

function FiltroChip({ rotulo, icone, ativo, onClick }: { rotulo: string; icone: React.ReactElement; ativo: boolean; onClick: () => void }) {
    return (
        <Chip
            icon={icone} label={rotulo} clickable onClick={onClick} color={ativo ? 'primary' : 'default'} variant={ativo ? 'filled' : 'outlined'}
            sx={{ height: 34, borderRadius: 2, fontWeight: 500, bgcolor: ativo ? undefined : 'background.paper', '& .MuiChip-icon': { fontSize: 17 } }}
        />
    );
}

function Indicador({ rotulo, valor, detalhe, alerta, atencao, ativo, onClick }: {
    rotulo: string; valor: string; detalhe: string; alerta?: boolean; atencao?: boolean; ativo?: boolean; onClick?: () => void;
}) {
    const cor = alerta ? 'error.main' : atencao ? 'warning.dark' : 'text.primary';
    return (
        <Paper
            variant="outlined" onClick={onClick}
            sx={{
                px: 2, py: 1.25, borderRadius: 2.5, cursor: onClick ? 'pointer' : 'default', minWidth: 0,
                borderColor: ativo ? 'primary.main' : alerta ? alpha('#DC2626', 0.35) : 'divider',
                bgcolor: ativo ? alpha('#2563EB', 0.05) : 'background.paper',
                transition: 'border-color .15s', '&:hover': onClick ? { borderColor: 'primary.light' } : undefined,
            }}
        >
            <Typography variant="caption" color="text.secondary" fontWeight={500}>{rotulo}</Typography>
            <Typography variant="h6" fontWeight={700} sx={{ lineHeight: 1.25, color: cor }}>{valor}</Typography>
            <Typography variant="caption" color="text.secondary" noWrap component="div">{detalhe}</Typography>
        </Paper>
    );
}

/**
 * Faixa horizontal do funil: cada etapa aberta (e Em aprovação) com largura proporcional ao valor.
 * Clicar rola o quadro até a coluna.
 */
function FaixaFunil({ colunas, total }: { colunas: Coluna[]; total: number }) {
    const etapas = colunas.filter((c) => c.tipo === 'aberta' || c.tipo === 'aprovacao');
    if (total <= 0 || etapas.length === 0) return null;

    const irPara = (id: number) =>
        document.getElementById(`coluna-${id}`)?.scrollIntoView({ behavior: 'smooth', inline: 'start', block: 'nearest' });

    return (
        <Paper variant="outlined" sx={{ mb: 2, p: 1.25, borderRadius: 2.5, display: { xs: 'none', sm: 'block' } }}>
            <Box sx={{ display: 'flex', gap: '3px', height: 10, borderRadius: 1, overflow: 'hidden' }}>
                {etapas.filter((c) => c.valor > 0).map((c) => (
                    <Tooltip key={c.id} title={`${c.nome}: ${moedaCompacta(c.valor)} · ${c.quantidade} negociação(ões)`}>
                        <Box
                            onClick={() => irPara(c.id)}
                            sx={{ flex: `${c.valor} 1 0`, minWidth: 6, bgcolor: c.cor, cursor: 'pointer', transition: 'filter .15s', '&:hover': { filter: 'brightness(1.1)' } }}
                        />
                    </Tooltip>
                ))}
            </Box>
            <Box sx={{ display: 'flex', flexWrap: 'wrap', columnGap: 2, rowGap: 0.5, mt: 1 }}>
                {etapas.map((c) => (
                    <Box
                        key={c.id} component="button" type="button" onClick={() => irPara(c.id)}
                        sx={{
                            display: 'flex', alignItems: 'center', gap: 0.75, border: 0, bgcolor: 'transparent', p: 0, cursor: 'pointer',
                            font: 'inherit', color: 'text.secondary', '&:hover': { color: 'text.primary' },
                        }}
                    >
                        <Box sx={{ width: 8, height: 8, borderRadius: '2px', bgcolor: c.cor }} />
                        <Typography variant="caption" color="inherit">{c.nome}</Typography>
                        <Typography variant="caption" fontWeight={700} color="text.primary" sx={{ fontVariantNumeric: 'tabular-nums' }}>
                            {moedaCompacta(c.valor)}
                        </Typography>
                    </Box>
                ))}
            </Box>
        </Paper>
    );
}

function ZonaPerdido({ coluna }: { coluna?: Coluna }) {
    const { setNodeRef, isOver } = useDroppable({ id: 'zona-perdido', data: { coluna }, disabled: !coluna });
    if (!coluna) return null;

    return (
        <Box
            ref={setNodeRef}
            sx={{
                position: 'fixed', bottom: 24, left: '50%', transform: 'translateX(-50%)', zIndex: 1300,
                px: 4, py: 1.75, borderRadius: 3, display: 'flex', alignItems: 'center', gap: 1,
                bgcolor: isOver ? coluna.cor : 'background.paper', color: isOver ? '#fff' : coluna.cor,
                border: '2px dashed', borderColor: coluna.cor, boxShadow: '0 10px 30px rgba(15,23,42,.18)',
                transition: 'all .15s',
            }}
        >
            <ThumbDownAltRoundedIcon fontSize="small" />
            <Typography variant="body2" fontWeight={700}>Solte aqui para marcar como {coluna.nome.toLowerCase()}</Typography>
        </Box>
    );
}
