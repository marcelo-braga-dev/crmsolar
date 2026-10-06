import React, { useEffect, useState } from 'react';
import {
    Alert, Box, Button, Chip, CircularProgress, Divider, Drawer, IconButton, MenuItem, TextField, Tooltip, Typography, alpha,
} from '@mui/material';
import CloseRoundedIcon from '@mui/icons-material/CloseRounded';
import WhatsAppIcon from '@mui/icons-material/WhatsApp';
import PhoneRoundedIcon from '@mui/icons-material/PhoneRounded';
import MailOutlineRoundedIcon from '@mui/icons-material/MailOutlineRounded';
import PhoneInTalkRoundedIcon from '@mui/icons-material/PhoneInTalkRounded';
import ArrowForwardRoundedIcon from '@mui/icons-material/ArrowForwardRounded';
import ThumbDownAltRoundedIcon from '@mui/icons-material/ThumbDownAltRounded';
import ReplayRoundedIcon from '@mui/icons-material/ReplayRounded';
import OpenInNewRoundedIcon from '@mui/icons-material/OpenInNewRounded';
import EventRoundedIcon from '@mui/icons-material/EventRounded';
import { ROTULO_EVENTO } from './eventoHistorico';
import { Area, CanalContato, DetalheOrcamento, SAUDE, abrirContato, dataContato, moeda } from './tipos';

interface Props {
    area: Area;
    /** Orçamento aberto no painel (null = fechado). */
    orcamentoId: number | null;
    /** Muda quando o quadro é recarregado, para o painel buscar os dados de novo. */
    versao: unknown;
    consultores: { id: number; name: string; status?: boolean }[];
    onFechar: () => void;
    onContato: (detalhe: DetalheOrcamento, canal: CanalContato) => void;
    onRegistrarContato: (detalhe: DetalheOrcamento) => void;
    onMover: (detalhe: DetalheOrcamento, el: HTMLElement) => void;
    onPerder: (detalhe: DetalheOrcamento) => void;
    onReativar: (detalhe: DetalheOrcamento) => void;
    onReatribuir: (detalhe: DetalheOrcamento, consultorId: number) => void;
    onAbrirCompleto: (id: number) => void;
}

/**
 * Painel lateral do card: tudo o que o consultor precisa para agir sem sair do quadro
 * (docs/funil-de-vendas.md, seção 22.4). Os dados vêm sob demanda de GET funil/{orcamento}.
 */
export function PainelOrcamento({
    area, orcamentoId, versao, consultores, onFechar, onContato, onRegistrarContato, onMover, onPerder, onReativar, onReatribuir, onAbrirCompleto,
}: Props) {
    const [detalhe, setDetalhe] = useState<DetalheOrcamento | null>(null);
    const [erro, setErro] = useState<string | null>(null);

    useEffect(() => {
        if (orcamentoId === null) { setDetalhe(null); return; }
        let ativo = true;
        setErro(null);
        window.axios.get<DetalheOrcamento>(route(`${area}.funil.show`, orcamentoId))
            .then((r) => { if (ativo) setDetalhe(r.data); })
            .catch(() => { if (ativo) setErro('Não foi possível carregar o orçamento.'); });
        return () => { ativo = false; };
    }, [orcamentoId, versao, area]);

    const d = detalhe?.id === orcamentoId ? detalhe : null;
    const tipo = d?.etapa?.tipo;
    const emAndamento = tipo === 'aberta' || tipo === 'aprovacao' || (d !== null && d.etapa === null);
    const saude = d?.saude ? SAUDE[d.saude] : null;

    return (
        <Drawer anchor="right" open={orcamentoId !== null} onClose={onFechar} PaperProps={{ sx: { width: { xs: '100%', sm: 440 } } }}>
            {!d && (
                <Box sx={{ flex: 1, display: 'flex', alignItems: 'center', justifyContent: 'center', p: 4 }}>
                    {erro ? <Alert severity="error">{erro}</Alert> : <CircularProgress size={28} />}
                </Box>
            )}

            {d && (
                <>
                    {/* Cabeçalho */}
                    <Box sx={{ px: 2.5, pt: 2, pb: 1.75, borderTop: '4px solid', borderColor: d.etapa?.cor ?? '#64748B' }}>
                        <Box sx={{ display: 'flex', alignItems: 'flex-start', gap: 1 }}>
                            <Box sx={{ flex: 1, minWidth: 0 }}>
                                <Typography variant="caption" color="text.secondary">Orçamento #{d.id}</Typography>
                                <Typography variant="h6" fontWeight={700} sx={{ lineHeight: 1.25 }}>{d.cliente}</Typography>
                            </Box>
                            <IconButton onClick={onFechar} aria-label="Fechar" sx={{ mt: -0.5, mr: -1 }}><CloseRoundedIcon /></IconButton>
                        </Box>
                        <Box sx={{ display: 'flex', gap: 0.75, mt: 1, flexWrap: 'wrap' }}>
                            <Chip
                                size="small" label={d.etapa?.nome ?? 'Caixa de entrada'}
                                sx={{ fontWeight: 600, bgcolor: alpha(d.etapa?.cor ?? '#64748B', 0.12), color: d.etapa?.cor ?? '#64748B' }}
                            />
                            {saude && (
                                <Tooltip title={saude.descricao}>
                                    <Chip size="small" label={saude.rotulo} sx={{ fontWeight: 600, bgcolor: alpha(saude.cor, 0.12), color: saude.cor }} />
                                </Tooltip>
                            )}
                            {d.reprovado && <Chip size="small" label="Reprovado" color="error" variant="outlined" />}
                            {d.tem_contrato && <Chip size="small" label="Contrato" color="success" variant="outlined" />}
                        </Box>
                    </Box>
                    <Divider />

                    <Box sx={{ flex: 1, overflowY: 'auto', px: 2.5, py: 2, display: 'flex', flexDirection: 'column', gap: 2.25 }}>
                        {/* Números */}
                        <Box sx={{ display: 'grid', gridTemplateColumns: 'repeat(3, minmax(0, 1fr))', gap: 1 }}>
                            <Numero rotulo="Valor" valor={moeda(d.valor)} />
                            <Numero rotulo="Potência" valor={d.potencia_kwp ? `${d.potencia_kwp.toLocaleString('pt-BR')} kWp` : '—'} />
                            <Numero rotulo="Geração" valor={d.geracao_estimada ? `${Math.round(d.geracao_estimada).toLocaleString('pt-BR')} kWh` : '—'} />
                        </Box>
                        <Typography variant="body2" color="text.secondary" sx={{ mt: -1 }}>
                            {[d.grupo && `Grupo ${d.grupo}`, d.cidade, area === 'admin' && d.consultor?.nome, `${d.dias_na_etapa === 0 ? 'entrou hoje' : `${d.dias_na_etapa}d`} na etapa`]
                                .filter(Boolean).join(' · ')}
                        </Typography>

                        {/* Contato */}
                        <Box sx={{ display: 'flex', gap: 1, flexWrap: 'wrap' }}>
                            <Button
                                size="small" variant="contained" startIcon={<WhatsAppIcon />} disabled={!d.whatsapp}
                                onClick={() => { abrirContato(d, 'whatsapp'); onContato(d, 'whatsapp'); }}
                                sx={{ bgcolor: '#16A34A', '&:hover': { bgcolor: '#15803D' } }}
                            >
                                WhatsApp
                            </Button>
                            <Button size="small" variant="outlined" startIcon={<PhoneRoundedIcon />} disabled={!d.telefone}
                                onClick={() => { abrirContato(d, 'telefone'); onContato(d, 'telefone'); }}>
                                Ligar
                            </Button>
                            {d.email && (
                                <Button size="small" variant="outlined" color="inherit" startIcon={<MailOutlineRoundedIcon />} href={`mailto:${d.email}`}>
                                    E-mail
                                </Button>
                            )}
                            {!d.whatsapp && !d.telefone && (
                                <Typography variant="caption" color="text.secondary" sx={{ alignSelf: 'center' }}>Cliente sem telefone cadastrado</Typography>
                            )}
                        </Box>

                        {/* Próximo passo */}
                        {emAndamento && (
                            <Box sx={{ p: 1.5, borderRadius: 2, border: '1px solid', borderColor: d.contato_atrasado ? alpha(SAUDE.atrasado.cor, 0.4) : 'divider', bgcolor: d.contato_atrasado ? alpha(SAUDE.atrasado.cor, 0.04) : 'transparent' }}>
                                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                                    <EventRoundedIcon fontSize="small" sx={{ color: d.contato_atrasado ? 'error.main' : 'text.secondary' }} />
                                    <Box sx={{ flex: 1 }}>
                                        <Typography variant="caption" color="text.secondary">Próximo contato</Typography>
                                        <Typography variant="body2" fontWeight={600} color={d.contato_atrasado ? 'error.main' : 'text.primary'}>
                                            {d.proximo_contato_em ? dataContato(d.proximo_contato_em) : 'Nenhum agendado'}
                                        </Typography>
                                    </Box>
                                    <Button size="small" startIcon={<PhoneInTalkRoundedIcon />} onClick={() => onRegistrarContato(d)}>Registrar</Button>
                                </Box>
                            </Box>
                        )}

                        {tipo === 'perdido' && (
                            <Alert severity="info" sx={{ py: 0.5 }}>
                                Perdido: <b>{d.motivo_perda}</b>{d.perda_observacao ? ` — ${d.perda_observacao}` : ''}
                            </Alert>
                        )}

                        {/* Ações */}
                        <Box sx={{ display: 'flex', gap: 1, flexWrap: 'wrap' }}>
                            {tipo !== 'ganho' && tipo !== 'perdido' && (tipo !== 'aprovacao' || area === 'admin') && (
                                <Button size="small" variant="outlined" startIcon={<ArrowForwardRoundedIcon />} onClick={(e) => onMover(d, e.currentTarget)}>Mover para…</Button>
                            )}
                            {tipo === 'perdido' && (
                                <Button size="small" variant="outlined" startIcon={<ReplayRoundedIcon />} onClick={() => onReativar(d)}>Reativar</Button>
                            )}
                            {(tipo === 'aberta' || d.etapa === null) && (
                                <Button size="small" color="error" startIcon={<ThumbDownAltRoundedIcon />} onClick={() => onPerder(d)}>Perdido</Button>
                            )}
                            <Box sx={{ flex: 1 }} />
                            <Button size="small" color="inherit" endIcon={<OpenInNewRoundedIcon />} onClick={() => onAbrirCompleto(d.id)}>Abrir completo</Button>
                        </Box>

                        {/* Responsável (admin) */}
                        {area === 'admin' && (tipo === 'aberta' || d.etapa === null) && d.status !== 'aprovando' && (
                            <TextField
                                select size="small" label="Responsável" value={d.consultor?.id ?? ''}
                                onChange={(e) => onReatribuir(d, Number(e.target.value))}
                                helperText="A comissão passa a ser a do novo consultor"
                            >
                                {consultores.filter((c) => c.status !== false || c.id === d.consultor?.id).map((c) => (
                                    <MenuItem key={c.id} value={c.id}>{c.name}</MenuItem>
                                ))}
                            </TextField>
                        )}

                        {/* Itens */}
                        {d.itens.length > 0 && (
                            <Box>
                                <Titulo>Itens</Titulo>
                                {d.itens.map((i, n) => (
                                    <Box key={n} sx={{ display: 'flex', gap: 1, py: 0.6, borderBottom: '1px solid', borderColor: 'divider', '&:last-child': { border: 0 } }}>
                                        <Typography variant="body2" sx={{ flex: 1 }}>
                                            {i.quantidade !== 1 && `${i.quantidade.toLocaleString('pt-BR')}× `}{i.descricao}
                                        </Typography>
                                        <Typography variant="body2" fontWeight={600} sx={{ fontVariantNumeric: 'tabular-nums' }}>{moeda(i.total)}</Typography>
                                    </Box>
                                ))}
                            </Box>
                        )}

                        {/* Linha do tempo */}
                        <Box>
                            <Titulo>Linha do tempo</Titulo>
                            {d.historico.length === 0 && <Typography variant="body2" color="text.secondary">Nenhum evento ainda.</Typography>}
                            <Box sx={{ position: 'relative', pl: 2.25, '&::before': { content: '""', position: 'absolute', left: 4, top: 6, bottom: 6, width: 2, bgcolor: 'divider' } }}>
                                {d.historico.map((h) => (
                                    <Box key={h.id} sx={{ position: 'relative', pb: 1.5 }}>
                                        <Box sx={{ position: 'absolute', left: -21, top: 5, width: 10, height: 10, borderRadius: '50%', bgcolor: 'background.paper', border: '2px solid', borderColor: corEvento(h.tipo) }} />
                                        <Typography variant="body2">{h.mensagem}</Typography>
                                        <Typography variant="caption" color="text.secondary">
                                            {ROTULO_EVENTO[h.tipo] ?? 'Status'} · {h.usuario ?? 'Sistema'} · {new Date(h.em).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' })}
                                        </Typography>
                                    </Box>
                                ))}
                            </Box>
                        </Box>
                    </Box>
                </>
            )}
        </Drawer>
    );
}

const corEvento = (tipo: string) => ({ contato: '#2563EB', perda: '#DC2626', reativacao: '#7C3AED', etapa: '#0EA5E9', responsavel: '#F59E0B' }[tipo] ?? '#64748B');

function Numero({ rotulo, valor }: { rotulo: string; valor: string }) {
    return (
        <Box sx={{ p: 1.25, borderRadius: 2, bgcolor: '#F8FAFC', border: '1px solid', borderColor: 'divider', minWidth: 0 }}>
            <Typography variant="caption" color="text.secondary">{rotulo}</Typography>
            <Typography variant="body2" fontWeight={700} noWrap sx={{ fontVariantNumeric: 'tabular-nums' }}>{valor}</Typography>
        </Box>
    );
}

function Titulo({ children }: { children: React.ReactNode }) {
    return <Typography variant="overline" color="text.secondary" sx={{ display: 'block', lineHeight: 2, mb: 0.5 }}>{children}</Typography>;
}
