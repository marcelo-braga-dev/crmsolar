import React from 'react';
import { Avatar, Box, Chip, IconButton, Tooltip, Typography, alpha } from '@mui/material';
import { useDraggable } from '@dnd-kit/core';
import MoreVertRoundedIcon from '@mui/icons-material/MoreVertRounded';
import ScheduleRoundedIcon from '@mui/icons-material/ScheduleRounded';
import EventRoundedIcon from '@mui/icons-material/EventRounded';
import BoltRoundedIcon from '@mui/icons-material/BoltRounded';
import { CardFunil, dataContato, iniciais, moeda } from './tipos';

interface VisualProps {
    card: CardFunil;
    cor: string;
    mostrarConsultor: boolean;
    arrastando?: boolean;
    sobreposto?: boolean;
    onMenu?: (e: React.MouseEvent<HTMLElement>) => void;
    onAbrir?: () => void;
}

/** Aparência do card — usada na coluna e no "fantasma" durante o arraste. */
export function CardVisual({ card, cor, mostrarConsultor, arrastando, sobreposto, onMenu, onAbrir }: VisualProps) {
    const corAging = card.sla_estourado ? 'error.main' : card.dias_na_etapa > 0 ? 'text.secondary' : 'success.main';

    return (
        <Box
            onClick={onAbrir}
            sx={{
                position: 'relative',
                bgcolor: 'background.paper',
                borderRadius: 2,
                border: '1px solid',
                borderColor: card.contato_atrasado ? alpha('#DC2626', 0.45) : 'divider',
                boxShadow: sobreposto ? '0 12px 28px rgba(15, 23, 42, 0.18)' : '0 1px 2px rgba(15, 23, 42, 0.05)',
                opacity: arrastando ? 0.35 : 1,
                transform: sobreposto ? 'rotate(2deg)' : undefined,
                cursor: sobreposto ? 'grabbing' : 'grab',
                overflow: 'hidden',
                transition: 'box-shadow .15s, border-color .15s',
                '&:hover': { boxShadow: '0 4px 12px rgba(15, 23, 42, 0.10)', borderColor: alpha(cor, 0.5) },
                pl: 1.75, pr: 1, py: 1.25,
                '&::before': { content: '""', position: 'absolute', left: 0, top: 0, bottom: 0, width: 4, bgcolor: cor },
            }}
        >
            <Box sx={{ display: 'flex', alignItems: 'flex-start', gap: 0.5 }}>
                <Typography variant="body2" fontWeight={600} sx={{ flex: 1, lineHeight: 1.3, wordBreak: 'break-word' }}>
                    {card.cliente}
                </Typography>
                <Typography variant="caption" color="text.disabled" sx={{ mt: 0.15 }}>#{card.id}</Typography>
                {onMenu && (
                    <IconButton
                        size="small"
                        aria-label="Ações do orçamento"
                        onPointerDown={(e) => e.stopPropagation()}
                        onClick={(e) => { e.stopPropagation(); onMenu(e); }}
                        sx={{ mt: -0.5, mr: -0.5 }}
                    >
                        <MoreVertRoundedIcon fontSize="small" />
                    </IconButton>
                )}
            </Box>

            <Typography variant="body2" fontWeight={700} sx={{ mt: 0.25 }}>
                {moeda(card.valor)}
                <Typography component="span" variant="caption" color="text.secondary" fontWeight={500}>
                    {card.potencia_kwp ? ` · ${card.potencia_kwp.toLocaleString('pt-BR')} kWp` : ''}
                    {card.grupo ? ` · ${card.grupo}` : ''}
                </Typography>
            </Typography>
            {card.cidade && <Typography variant="caption" color="text.secondary" component="div">{card.cidade}</Typography>}

            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.25, mt: 0.75, flexWrap: 'wrap' }}>
                <Tooltip title={card.sla_estourado ? 'Acima do tempo esperado nesta etapa' : 'Dias nesta etapa'}>
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.3, color: corAging }}>
                        <ScheduleRoundedIcon sx={{ fontSize: 14 }} />
                        <Typography variant="caption" fontWeight={card.sla_estourado ? 700 : 500} color="inherit">
                            {card.dias_na_etapa === 0 ? 'hoje' : `${card.dias_na_etapa}d`}
                        </Typography>
                    </Box>
                </Tooltip>
                {card.proximo_contato_em && (
                    <Tooltip title={card.contato_atrasado ? 'Contato atrasado' : 'Próximo contato'}>
                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.3, color: card.contato_atrasado ? 'error.main' : 'primary.main' }}>
                            <EventRoundedIcon sx={{ fontSize: 14 }} />
                            <Typography variant="caption" fontWeight={600} color="inherit">{dataContato(card.proximo_contato_em)}</Typography>
                        </Box>
                    </Tooltip>
                )}
                <Box sx={{ flex: 1 }} />
                {mostrarConsultor && card.consultor && (
                    <Tooltip title={card.consultor.nome}>
                        <Avatar sx={{ width: 22, height: 22, fontSize: '0.62rem', bgcolor: alpha(cor, 0.18), color: cor, fontWeight: 700 }}>
                            {iniciais(card.consultor.nome)}
                        </Avatar>
                    </Tooltip>
                )}
            </Box>

            {(card.reprovado || card.tem_contrato || card.motivo_perda || card.esfriando || card.tentativas_reativacao > 0) && (
                <Box sx={{ display: 'flex', gap: 0.5, mt: 0.75, flexWrap: 'wrap' }}>
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
        </Box>
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
