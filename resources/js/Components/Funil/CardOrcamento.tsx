import React from 'react';
import { Avatar, Box, Checkbox, Chip, IconButton, LinearProgress, Tooltip, Typography, alpha } from '@mui/material';
import { useDraggable } from '@dnd-kit/core';
import MoreVertRoundedIcon from '@mui/icons-material/MoreVertRounded';
import EventRoundedIcon from '@mui/icons-material/EventRounded';
import EventBusyRoundedIcon from '@mui/icons-material/EventBusyRounded';
import BoltRoundedIcon from '@mui/icons-material/BoltRounded';
import WhatsAppIcon from '@mui/icons-material/WhatsApp';
import PhoneRoundedIcon from '@mui/icons-material/PhoneRounded';
import { CanalContato, CardFunil, Densidade, SAUDE, dataContato, iniciais, moeda } from './tipos';

interface VisualProps {
    card: CardFunil;
    cor: string;
    /** SLA da coluna, para a barra de tempo na etapa (null = sem barra). */
    slaDias?: number | null;
    mostrarConsultor: boolean;
    densidade?: Densidade;
    salvando?: boolean;
    arrastando?: boolean;
    sobreposto?: boolean;
    /** Modo de seleção em lote: true/false = marcado ou não; undefined = modo desligado. */
    selecionado?: boolean;
    onMenu?: (e: React.MouseEvent<HTMLElement>) => void;
    onAbrir?: () => void;
    onContato?: (canal: CanalContato) => void;
}

/** Aparência do card — usada na coluna e no "fantasma" durante o arraste. */
export function CardVisual({
    card, cor, slaDias, mostrarConsultor, densidade = 'confortavel', salvando, arrastando, sobreposto, selecionado, onMenu, onAbrir, onContato,
}: VisualProps) {
    const emSelecao = selecionado !== undefined;
    const saude = card.saude ? SAUDE[card.saude] : null;
    const compacta = densidade === 'compacta';
    const linhaTecnica = [
        card.potencia_kwp ? `${card.potencia_kwp.toLocaleString('pt-BR')} kWp` : null,
        card.grupo,
        card.cidade,
    ].filter(Boolean).join(' · ');
    const temSelos = card.reprovado || card.tem_contrato || card.motivo_perda || card.esfriando || card.tentativas_reativacao > 0;

    return (
        <Box
            onClick={onAbrir}
            sx={{
                position: 'relative',
                bgcolor: 'background.paper',
                borderRadius: 2,
                border: '1px solid',
                borderColor: selecionado ? 'primary.main' : '#E2E8F0',
                outline: selecionado ? '1px solid' : 'none', outlineColor: 'primary.main',
                boxShadow: sobreposto ? '0 16px 32px rgba(15, 23, 42, 0.20)' : '0 1px 2px rgba(15, 23, 42, 0.04)',
                opacity: arrastando ? 0.35 : salvando ? 0.7 : 1,
                transform: sobreposto ? 'rotate(1.5deg) scale(1.02)' : undefined,
                cursor: sobreposto ? 'grabbing' : emSelecao ? 'pointer' : 'grab',
                overflow: 'hidden',
                transition: 'box-shadow .15s, border-color .15s, transform .15s, opacity .15s',
                // Botões de contato aparecem ao passar o mouse; em telas de toque ficam sempre visíveis.
                '& .contato-rapido': { opacity: { xs: 1, md: 0 }, transition: 'opacity .15s' },
                '&:hover .contato-rapido, &:focus-within .contato-rapido': { opacity: 1 },
                '&:hover': { boxShadow: '0 6px 16px rgba(15, 23, 42, 0.08)', borderColor: alpha(cor, 0.55), transform: sobreposto ? undefined : 'translateY(-1px)' },
                pl: 1.75, pr: 1, py: compacta ? 0.9 : 1.25,
                '&::before': { content: '""', position: 'absolute', left: 0, top: 0, bottom: 0, width: 3, bgcolor: cor },
            }}
        >
            {salvando && <LinearProgress sx={{ position: 'absolute', top: 0, left: 0, right: 0, height: 2 }} />}

            {/* Cliente · número · ações */}
            <Box sx={{ display: 'flex', alignItems: 'flex-start', gap: 0.75 }}>
                {emSelecao && (
                    <Checkbox size="small" checked={selecionado} tabIndex={-1} sx={{ p: 0, mt: 0.1, mr: 0.25 }} inputProps={{ 'aria-label': `Selecionar #${card.id}` }} />
                )}
                {saude && (
                    <Tooltip title={`${saude.rotulo} — ${saude.descricao}`}>
                        <Box
                            aria-label={saude.rotulo}
                            sx={{
                                width: 8, height: 8, borderRadius: '50%', bgcolor: saude.cor, flexShrink: 0, mt: 0.75,
                                boxShadow: `0 0 0 3px ${alpha(saude.cor, 0.15)}`,
                            }}
                        />
                    </Tooltip>
                )}
                <Typography variant="body2" fontWeight={600} sx={{ flex: 1, lineHeight: 1.35, wordBreak: 'break-word', color: '#0F172A' }}>
                    {card.cliente}
                </Typography>
                <Typography variant="caption" sx={{ mt: 0.2, color: 'text.disabled', fontVariantNumeric: 'tabular-nums' }}>#{card.id}</Typography>
                {onMenu && !emSelecao && (
                    <IconButton
                        size="small"
                        aria-label="Ações do orçamento"
                        onPointerDown={(e) => e.stopPropagation()}
                        onClick={(e) => { e.stopPropagation(); onMenu(e); }}
                        sx={{ mt: -0.6, mr: -0.5, p: 0.4, color: 'text.secondary' }}
                    >
                        <MoreVertRoundedIcon sx={{ fontSize: 18 }} />
                    </IconButton>
                )}
            </Box>

            {/* Valor e dados técnicos */}
            <Typography sx={{ mt: compacta ? 0.1 : 0.5, fontSize: compacta ? '0.875rem' : '1rem', fontWeight: 700, letterSpacing: '-0.01em', fontVariantNumeric: 'tabular-nums' }}>
                {moeda(card.valor)}
            </Typography>
            {!compacta && linhaTecnica && (
                <Typography variant="caption" color="text.secondary" component="div" noWrap title={linhaTecnica}>{linhaTecnica}</Typography>
            )}

            {/* Próximo passo · tempo na etapa · consultor */}
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mt: compacta ? 0.5 : 1 }}>
                <ProximoPasso card={card} />
                <Box sx={{ flex: 1 }} />
                {onContato && !emSelecao && (card.whatsapp || card.telefone) && (
                    <Box className="contato-rapido" sx={{ display: 'flex', gap: 0.25 }}>
                        {card.whatsapp && (
                            <Tooltip title="WhatsApp">
                                <IconButton size="small" aria-label="WhatsApp" onPointerDown={(e) => e.stopPropagation()}
                                    onClick={(e) => { e.stopPropagation(); onContato('whatsapp'); }} sx={{ p: 0.4, color: '#16A34A' }}>
                                    <WhatsAppIcon sx={{ fontSize: 17 }} />
                                </IconButton>
                            </Tooltip>
                        )}
                        {card.telefone && (
                            <Tooltip title="Ligar">
                                <IconButton size="small" aria-label="Ligar" onPointerDown={(e) => e.stopPropagation()}
                                    onClick={(e) => { e.stopPropagation(); onContato('telefone'); }} sx={{ p: 0.4, color: 'primary.main' }}>
                                    <PhoneRoundedIcon sx={{ fontSize: 16 }} />
                                </IconButton>
                            </Tooltip>
                        )}
                    </Box>
                )}
                {mostrarConsultor && card.consultor && (
                    <Tooltip title={card.consultor.nome}>
                        <Avatar sx={{ width: 22, height: 22, fontSize: '0.6rem', bgcolor: alpha(cor, 0.16), color: cor, fontWeight: 700 }}>
                            {iniciais(card.consultor.nome)}
                        </Avatar>
                    </Tooltip>
                )}
            </Box>
            <TempoNaEtapa dias={card.dias_na_etapa} sla={slaDias ?? null} estourado={card.sla_estourado} compacta={compacta} />

            {!compacta && temSelos && (
                <Box sx={{ display: 'flex', gap: 0.5, mt: 0.9, flexWrap: 'wrap' }}>
                    {card.reprovado && <Selo cor="#DC2626" texto="Reprovado" />}
                    {card.esfriando && <Selo cor="#EA580C" texto="Esfriando" />}
                    {card.tem_contrato && <Selo cor="#16A34A" texto="Contrato" />}
                    {card.motivo_perda && <Selo cor="#64748B" texto={card.motivo_perda} />}
                    {card.tentativas_reativacao > 0 && (
                        <Tooltip title="Vezes que a negociação foi reativada">
                            <span><Selo cor="#7C3AED" texto={`${card.tentativas_reativacao}ª reativação`} icone /></span>
                        </Tooltip>
                    )}
                </Box>
            )}
            {compacta && card.reprovado && <Box sx={{ mt: 0.5 }}><Selo cor="#DC2626" texto="Reprovado" /></Box>}
        </Box>
    );
}

/** Próximo contato agendado, ou o aviso de que falta um (só onde há acompanhamento comercial). */
function ProximoPasso({ card }: { card: CardFunil }) {
    if (card.proximo_contato_em && card.saude) {
        const cor = card.contato_atrasado ? SAUDE.atrasado.cor : card.saude === 'atencao' ? SAUDE.atencao.cor : '#2563EB';
        return (
            <Tooltip title={card.contato_atrasado ? 'Contato atrasado' : 'Próximo contato'}>
                <Box sx={{ display: 'inline-flex', alignItems: 'center', gap: 0.4, px: 0.75, py: 0.2, borderRadius: 1, bgcolor: alpha(cor, 0.08), color: cor }}>
                    <EventRoundedIcon sx={{ fontSize: 13 }} />
                    <Typography variant="caption" fontWeight={600} color="inherit" sx={{ lineHeight: 1.4 }}>{dataContato(card.proximo_contato_em)}</Typography>
                </Box>
            </Tooltip>
        );
    }
    if (card.saude === 'sem_passo') {
        return (
            <Box sx={{ display: 'inline-flex', alignItems: 'center', gap: 0.4, color: 'text.secondary' }}>
                <EventBusyRoundedIcon sx={{ fontSize: 13 }} />
                <Typography variant="caption" color="inherit" sx={{ fontStyle: 'italic' }}>Sem próximo passo</Typography>
            </Box>
        );
    }
    return null;
}

/** Dias na etapa; com SLA vira uma barra que enche até o limite e fica vermelha ao estourar. */
function TempoNaEtapa({ dias, sla, estourado, compacta }: { dias: number; sla: number | null; estourado: boolean; compacta: boolean }) {
    const texto = dias === 0 ? 'hoje' : `${dias}d`;
    const cor = estourado ? SAUDE.atrasado.cor : sla && dias * 10 >= sla * 7 ? SAUDE.atencao.cor : '#64748B';

    return (
        <Tooltip title={sla ? `${texto === 'hoje' ? 'Entrou hoje' : `${dias} dia(s)`} nesta etapa · esperado até ${sla} dia(s)` : 'Tempo nesta etapa'}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.75, mt: compacta ? 0.5 : 0.75 }}>
                {sla ? (
                    <Box sx={{ flex: 1, height: 4, borderRadius: 2, bgcolor: '#EEF2F6', overflow: 'hidden' }}>
                        <Box sx={{ width: `${Math.min(100, Math.max(4, (dias / sla) * 100))}%`, height: '100%', bgcolor: cor, borderRadius: 2, transition: 'width .3s' }} />
                    </Box>
                ) : <Box sx={{ flex: 1 }} />}
                <Typography variant="caption" sx={{ color: cor, fontWeight: estourado ? 700 : 500, fontVariantNumeric: 'tabular-nums', lineHeight: 1 }}>
                    {sla ? `${texto} / ${sla}d` : texto}
                </Typography>
            </Box>
        </Tooltip>
    );
}

function Selo({ cor, texto, icone }: { cor: string; texto: string; icone?: boolean }) {
    return (
        <Chip
            size="small"
            label={texto}
            icon={icone ? <BoltRoundedIcon sx={{ fontSize: '12px !important', color: `${cor} !important` }} /> : undefined}
            sx={{ height: 20, fontSize: '0.68rem', fontWeight: 600, bgcolor: alpha(cor, 0.1), color: cor, '& .MuiChip-label': { px: 0.75 } }}
        />
    );
}

interface Props extends Omit<VisualProps, 'arrastando' | 'sobreposto'> {
    origem: string;
    arrastavel: boolean;
}

/** Card arrastável. O id de arraste carrega a coluna de origem para o servidor conferir conflitos. */
export function CardOrcamento({ origem, arrastavel, ...visual }: Props) {
    // O card que acompanha o cursor é o DragOverlay da página; este fica no lugar, esmaecido.
    const { attributes, listeners, setNodeRef, isDragging } = useDraggable({
        id: `orcamento-${visual.card.id}`,
        data: { card: visual.card, origem, cor: visual.cor },
        disabled: !arrastavel,
    });

    return (
        <Box
            ref={setNodeRef}
            {...attributes}
            {...listeners}
            aria-roledescription="card arrastável"
            sx={{ touchAction: 'manipulation', outline: 'none',
                  '&:focus-visible > div': { boxShadow: (t) => `0 0 0 2px ${t.palette.primary.main}` } }}
        >
            <CardVisual {...visual} arrastando={isDragging} />
        </Box>
    );
}
