import React, { useEffect, useMemo, useState } from 'react';
import {
    Badge, Box, Button, Divider, InputAdornment, ListItemIcon, Menu, MenuItem, Paper, TextField, ToggleButton, Tooltip, Typography, alpha,
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
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { ConfirmDialog } from '@/Components/UI/ConfirmDialog';
import { CardOrcamento, CardVisual } from '@/Components/Funil/CardOrcamento';
import { ColunaFunil } from '@/Components/Funil/ColunaFunil';
import { CaixaEntrada } from '@/Components/Funil/CaixaEntrada';
import { DialogAgenda, DialogPerda } from '@/Components/Funil/Dialogos';
import {
    Area, CAIXA, CardFunil, ColunaFunil as Coluna, MotivoPerda, ResumoFunil, TipoEtapa, moedaCompacta,
} from '@/Components/Funil/tipos';

interface Props {
    area: Area;
    colunas: Coluna[];
    caixa: CardFunil[];
    resumo: ResumoFunil;
    motivos: MotivoPerda[];
    consultores: { id: number; name: string }[];
    filtros: { consultor_id?: string; busca?: string; grupo?: string; atrasados?: boolean };
}

const GRUPOS = ['B1', 'B2', 'B3', 'A4', 'A3a', 'A3', 'A2', 'A1'];
const CHAVE_RECOLHIDAS = 'funil.colunasRecolhidas';

/** Card em foco de uma ação (arraste, menu ou caixa de entrada) e a coluna de onde ele saiu. */
interface Alvo { card: CardFunil; origem: string; origemTipo: TipoEtapa | 'caixa' }

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

const lerRecolhidas = (): number[] | null => {
    try { const v = window.localStorage.getItem(CHAVE_RECOLHIDAS); return v ? JSON.parse(v) : null; } catch { return null; }
};

export default function FunilIndex({ area, colunas: colunasServidor, caixa, resumo, motivos, consultores, filtros }: Props) {
    const [colunas, setColunas] = useState(colunasServidor);
    useEffect(() => setColunas(colunasServidor), [colunasServidor]);

    const [busca, setBusca] = useState(filtros.busca ?? '');
    const [caixaAberta, setCaixaAberta] = useState(false);
    const [arrastando, setArrastando] = useState<Alvo | null>(null);
    const [enviando, setEnviando] = useState(false);

    // Diálogos
    const [perda, setPerda] = useState<Alvo | null>(null);
    const [agenda, setAgenda] = useState<{ alvo: Alvo; modo: 'contato' | 'iniciar' | 'reativar'; destino?: Coluna } | null>(null);
    const [confirmacao, setConfirmacao] = useState<{ alvo: Alvo; destino: Coluna; titulo: string; mensagem: string; rotulo: string } | null>(null);
    const [menu, setMenu] = useState<{ el: HTMLElement; alvo: Alvo } | null>(null);
    const [moverPara, setMoverPara] = useState<{ el: HTMLElement; alvo: Alvo } | null>(null);

    const [recolhidas, setRecolhidas] = useState<number[]>(
        () => lerRecolhidas() ?? colunasServidor.filter((c) => c.tipo === 'perdido').map((c) => c.id),
    );
    const alternarColuna = (id: number) => setRecolhidas((atual) => {
        const novo = atual.includes(id) ? atual.filter((x) => x !== id) : [...atual, id];
        try { window.localStorage.setItem(CHAVE_RECOLHIDAS, JSON.stringify(novo)); } catch { /* armazenamento indisponível */ }
        return novo;
    });

    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 6 } }),
        useSensor(TouchSensor, { activationConstraint: { delay: 180, tolerance: 6 } }),
        useSensor(KeyboardSensor),
    );

    const primeiraAberta = colunas.find((c) => c.tipo === 'aberta');
    const tipoDaOrigem = (origem: string): TipoEtapa | 'caixa' =>
        origem === CAIXA ? CAIXA : (colunas.find((c) => String(c.id) === origem)?.tipo ?? 'aberta');

    // ── Requisições ───────────────────────────────────────────────────────

    const rota = (acao: string, id?: number) => route(`${area}.funil.${acao}`, id);
    const opcoes = {
        preserveScroll: true,
        preserveState: true,
        onStart: () => setEnviando(true),
        onFinish: () => setEnviando(false),
    };
    /** datetime-local (horário do navegador) → ISO com fuso, para o servidor gravar o instante certo. */
    const iso = (data: string | null) => (data ? new Date(data).toISOString() : null);

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

    const mover = (alvo: Alvo, destino: Coluna, proximoContato: string | null = null) => {
        moverLocalmente(alvo, destino);
        router.post(rota('mover', alvo.card.id), { etapa_id: destino.id, origem: alvo.origem, proximo_contato_em: iso(proximoContato) }, opcoes);
    };

    const perder = (alvo: Alvo, motivoId: number, observacao: string) => {
        router.post(rota('perder', alvo.card.id), { motivo_perda_id: motivoId, observacao, origem: alvo.origem }, { ...opcoes, onSuccess: () => setPerda(null) });
    };

    const confirmarAgenda = ({ nota, data }: { nota: string; data: string | null }) => {
        if (!agenda) return;
        const fechar = { ...opcoes, onSuccess: () => setAgenda(null) };
        const { alvo, modo, destino } = agenda;
        if (modo === 'contato') {
            router.post(rota('contato', alvo.card.id), { nota, proximo_contato_em: iso(data) }, fechar);
        } else if (modo === 'reativar') {
            router.post(rota('reativar', alvo.card.id), { etapa_id: destino?.id, proximo_contato_em: iso(data) }, fechar);
        } else if (destino) {
            setAgenda(null);
            mover(alvo, destino, data);
        }
    };

    /** Decide o que fazer ao levar um card para uma coluna (arrastando ou pelo menu "Mover para"). */
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
        mover(alvo, destino);
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

    const filtrar = (novos: Partial<Props['filtros']>) => {
        const params = Object.fromEntries(
            Object.entries({ ...filtros, busca, ...novos }).filter(([, v]) => v !== '' && v != null && v !== false),
        );
        router.get(rota('index'), params, { preserveState: true, replace: true, preserveScroll: true });
    };

    const abrirOrcamento = (card: CardFunil) => router.visit(route(`${area}.orcamentos.show`, card.id));
    const corDaOrigem = (origem: string) => colunas.find((c) => String(c.id) === origem)?.cor ?? '#64748B';

    const destinosPossiveis = useMemo(
        () => (alvo: Alvo) => colunas.filter((c) => String(c.id) !== alvo.origem && podeSoltar(alvo.origemTipo, c, area)),
        [colunas, area],
    );

    return (
        <AppLayout title="Funil de vendas">
            <Head title="Funil de vendas" />

            <PageHeader
                title="Funil de vendas"
                subtitle={area === 'admin' ? 'Negociações de todos os consultores' : 'Acompanhe e avance suas negociações'}
                breadcrumbs={[{ label: 'Orçamentos' }, { label: 'Funil' }]}
                action={
                    <Box sx={{ display: 'flex', gap: 1 }}>
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
                        <Badge badgeContent={resumo.caixa} color="warning" max={99}>
                            <Button variant="contained" startIcon={<InboxRoundedIcon />} onClick={() => setCaixaAberta(true)}>
                                Caixa de entrada
                            </Button>
                        </Badge>
                    </Box>
                }
            />

            {/* Indicadores */}
            <Box sx={{ display: 'grid', gridTemplateColumns: { xs: 'repeat(2, minmax(0, 1fr))', md: 'repeat(4, minmax(0, 1fr))' }, gap: 1.5, mb: 2 }}>
                <Indicador rotulo="Em negociação" valor={String(resumo.abertos)} detalhe={moedaCompacta(resumo.valor_aberto)} />
                <Indicador rotulo="Previsão ponderada" valor={moedaCompacta(resumo.ponderado)} detalhe="valor × probabilidade da etapa" />
                <Indicador
                    rotulo="Contatos atrasados" valor={String(resumo.atrasados)} detalhe={filtros.atrasados ? 'filtrando — clique para limpar' : 'clique para filtrar'}
                    alerta={resumo.atrasados > 0} onClick={() => filtrar({ atrasados: !filtros.atrasados })} ativo={!!filtros.atrasados}
                />
                <Indicador
                    rotulo="Caixa de entrada" valor={String(resumo.caixa)} detalhe={`aguardando atendimento · alerta após ${resumo.dias_caixa_entrada}d`}
                    alerta={caixa.some((c) => c.esfriando)} onClick={() => setCaixaAberta(true)}
                />
            </Box>

            {/* Filtros */}
            <Box sx={{ display: 'flex', gap: 1.5, mb: 2, flexWrap: 'wrap', alignItems: 'center' }}>
                <TextField
                    size="small" placeholder="Buscar cliente ou #número" value={busca}
                    onChange={(e) => setBusca(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && filtrar({})}
                    InputProps={{ startAdornment: <InputAdornment position="start"><SearchRoundedIcon fontSize="small" /></InputAdornment> }}
                    sx={{ minWidth: 240, flex: { xs: 1, sm: 'none' }, bgcolor: 'background.paper' }}
                />
                {area === 'admin' && (
                    <TextField
                        select size="small" value={filtros.consultor_id ?? ''} onChange={(e) => filtrar({ consultor_id: e.target.value })}
                        sx={{ minWidth: 200, bgcolor: 'background.paper' }} SelectProps={{ displayEmpty: true }}
                    >
                        <MenuItem value="">Todos os consultores</MenuItem>
                        {consultores.map((c) => <MenuItem key={c.id} value={String(c.id)}>{c.name}</MenuItem>)}
                    </TextField>
                )}
                <TextField
                    select size="small" value={filtros.grupo ?? ''} onChange={(e) => filtrar({ grupo: e.target.value })}
                    sx={{ minWidth: 150, bgcolor: 'background.paper' }} SelectProps={{ displayEmpty: true }}
                >
                    <MenuItem value="">Todos os grupos</MenuItem>
                    {GRUPOS.map((g) => <MenuItem key={g} value={g}>Grupo {g}</MenuItem>)}
                </TextField>
                <ToggleButton
                    size="small" value="atrasados" selected={!!filtros.atrasados} onChange={() => filtrar({ atrasados: !filtros.atrasados })}
                    sx={{ bgcolor: 'background.paper', textTransform: 'none', gap: 0.5 }}
                >
                    <EventBusyRoundedIcon fontSize="small" /> Só atrasados
                </ToggleButton>
                {(filtros.busca || filtros.grupo || filtros.consultor_id || filtros.atrasados) && (
                    <Button size="small" onClick={() => { setBusca(''); router.get(rota('index'), {}, { preserveState: true, replace: true }); }}>
                        Limpar filtros
                    </Button>
                )}
            </Box>

            {/* Quadro */}
            <DndContext sensors={sensors} onDragStart={aoIniciarArraste} onDragEnd={aoSoltar} onDragCancel={() => setArrastando(null)}>
                <Box
                    sx={{
                        display: 'flex', gap: 1.5, overflowX: 'auto', pb: 1.5, alignItems: 'stretch',
                        height: { xs: 'calc(100vh - 330px)', md: 'calc(100vh - 300px)' }, minHeight: 420,
                        scrollSnapType: { xs: 'x mandatory', md: 'none' }, '& > *': { scrollSnapAlign: 'start' },
                        '&::-webkit-scrollbar': { height: 8 }, '&::-webkit-scrollbar-thumb': { bgcolor: 'rgba(15,23,42,.18)', borderRadius: 4 },
                    }}
                >
                    {colunas.map((coluna) => (
                        <ColunaFunil
                            key={coluna.id}
                            coluna={coluna}
                            aceita={arrastando && String(coluna.id) !== arrastando.origem ? podeSoltar(arrastando.origemTipo, coluna, area) : null}
                            recolhida={recolhidas.includes(coluna.id) && !(arrastando && coluna.tipo === 'perdido')}
                            onAlternar={() => alternarColuna(coluna.id)}
                        >
                            {coluna.cards.map((card) => {
                                const alvo: Alvo = { card, origem: String(coluna.id), origemTipo: coluna.tipo };
                                return (
                                    <CardOrcamento
                                        key={card.id}
                                        card={card}
                                        cor={coluna.cor}
                                        origem={String(coluna.id)}
                                        arrastavel={!enviando && coluna.tipo !== 'ganho' && (coluna.tipo !== 'aprovacao' || area === 'admin')}
                                        mostrarConsultor={area === 'admin'}
                                        onAbrir={() => abrirOrcamento(card)}
                                        onMenu={(e) => setMenu({ el: e.currentTarget, alvo })}
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

                <DragOverlay dropAnimation={null}>
                    {arrastando && (
                        <Box sx={{ width: 280 }}>
                            <CardVisual card={arrastando.card} cor={corDaOrigem(arrastando.origem)} mostrarConsultor={area === 'admin'} sobreposto />
                        </Box>
                    )}
                </DragOverlay>
            </DndContext>

            {/* Menu de ações do card (também é a alternativa acessível ao arraste) */}
            <Menu anchorEl={menu?.el} open={!!menu} onClose={() => setMenu(null)}>
                {menu && [
                    <MenuItem key="abrir" onClick={() => { setMenu(null); abrirOrcamento(menu.alvo.card); }}>
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

            <CaixaEntrada
                aberta={caixaAberta}
                itens={caixa}
                diasLimite={resumo.dias_caixa_entrada}
                mostrarConsultor={area === 'admin'}
                onFechar={() => setCaixaAberta(false)}
                onAbrir={abrirOrcamento}
                onIniciar={(card) => primeiraAberta && setAgenda({ alvo: { card, origem: CAIXA, origemTipo: CAIXA }, modo: 'iniciar', destino: primeiraAberta })}
                onDescartar={(card) => setPerda({ card, origem: CAIXA, origemTipo: CAIXA })}
            />

            <DialogPerda
                aberto={!!perda}
                cliente={perda ? `#${perda.card.id} · ${perda.card.cliente}` : undefined}
                motivos={motivos}
                enviando={enviando}
                onFechar={() => setPerda(null)}
                onConfirmar={(motivo, obs) => perda && perder(perda, motivo, obs)}
            />

            <DialogAgenda
                aberto={!!agenda}
                titulo={agenda?.modo === 'contato' ? 'Registrar contato' : agenda?.modo === 'reativar' ? 'Reativar negociação' : `Iniciar atendimento — ${agenda?.destino?.nome ?? ''}`}
                cliente={agenda ? `#${agenda.alvo.card.id} · ${agenda.alvo.card.cliente}` : undefined}
                comNota={agenda?.modo === 'contato'}
                textoConfirmar={agenda?.modo === 'contato' ? 'Registrar' : agenda?.modo === 'reativar' ? 'Reativar' : 'Iniciar'}
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
        </AppLayout>
    );
}

function Indicador({ rotulo, valor, detalhe, alerta, ativo, onClick }: {
    rotulo: string; valor: string; detalhe: string; alerta?: boolean; ativo?: boolean; onClick?: () => void;
}) {
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
            <Typography variant="h6" fontWeight={700} sx={{ lineHeight: 1.25, color: alerta ? 'error.main' : 'text.primary' }}>{valor}</Typography>
            <Typography variant="caption" color="text.secondary" noWrap component="div">{detalhe}</Typography>
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
