import React from 'react';
import { Box, IconButton, Tooltip, Typography, alpha } from '@mui/material';
import { useDroppable } from '@dnd-kit/core';
import ChevronLeftRoundedIcon from '@mui/icons-material/ChevronLeftRounded';
import ChevronRightRoundedIcon from '@mui/icons-material/ChevronRightRounded';
import LockRoundedIcon from '@mui/icons-material/LockRounded';
import GavelRoundedIcon from '@mui/icons-material/GavelRounded';
import EmojiEventsRoundedIcon from '@mui/icons-material/EmojiEventsRounded';
import ThumbDownAltRoundedIcon from '@mui/icons-material/ThumbDownAltRounded';
import MoveDownRoundedIcon from '@mui/icons-material/MoveDownRounded';
import { ColunaFunil as Coluna, moedaCompacta } from './tipos';

interface Props {
    coluna: Coluna;
    /** Durante um arraste: true = pode soltar aqui, false = não pode, null = nada sendo arrastado. */
    aceita: boolean | null;
    recolhida: boolean;
    /** Valor das negociações abertas + Em aprovação, para a barra de participação da etapa. */
    valorDoFunil: number;
    /** No celular a coluna ocupa a largura toda (uma etapa por vez). */
    cheia?: boolean;
    onAlternar: () => void;
    children: React.ReactNode;
}

const DESCRICAO_SISTEMA: Record<string, string> = {
    aprovacao: 'Soltar aqui envia o orçamento para aprovação do administrador',
    ganho: 'Vendas aprovadas nos últimos 30 dias. Só o administrador aprova',
    perdido: 'Vendas perdidas nos últimos 30 dias. Arraste para uma etapa para reativar',
};

const VAZIO: Record<string, { icone: React.ElementType; texto: string }> = {
    aberta: { icone: MoveDownRoundedIcon, texto: 'Arraste negociações para esta etapa' },
    aprovacao: { icone: GavelRoundedIcon, texto: 'Arraste aqui para enviar para aprovação' },
    ganho: { icone: EmojiEventsRoundedIcon, texto: 'Nenhuma venda nos últimos 30 dias' },
    perdido: { icone: ThumbDownAltRoundedIcon, texto: 'Nenhuma perda nos últimos 30 dias' },
};

export function ColunaFunil({ coluna, aceita, recolhida, valorDoFunil, cheia, onAlternar, children }: Props) {
    const { setNodeRef, isOver } = useDroppable({ id: `etapa-${coluna.id}`, data: { coluna }, disabled: aceita === false });
    const destaque = aceita === true && isOver;
    const sistema = coluna.tipo !== 'aberta';
    const noFunil = coluna.tipo === 'aberta' || coluna.tipo === 'aprovacao';
    const participacao = noFunil && valorDoFunil > 0 ? coluna.valor / valorDoFunil : 0;

    if (recolhida && !cheia) {
        return (
            <Box
                id={`coluna-${coluna.id}`}
                ref={setNodeRef}
                onClick={onAlternar}
                role="button"
                aria-label={`Expandir ${coluna.nome}`}
                sx={{
                    flex: '0 0 48px', alignSelf: 'stretch', borderRadius: 3, cursor: 'pointer',
                    bgcolor: destaque ? alpha(coluna.cor, 0.18) : alpha(coluna.cor, 0.06),
                    border: '1px solid', borderColor: destaque ? coluna.cor : alpha(coluna.cor, 0.2),
                    display: 'flex', flexDirection: 'column', alignItems: 'center', py: 1.5, gap: 1,
                    opacity: aceita === false ? 0.4 : 1, transition: 'all .15s',
                    '&:hover': { bgcolor: alpha(coluna.cor, 0.12) },
                }}
            >
                <ChevronLeftRoundedIcon fontSize="small" sx={{ color: coluna.cor }} />
                <Typography variant="caption" fontWeight={700} sx={{ color: coluna.cor }}>{coluna.quantidade}</Typography>
                <Typography variant="caption" fontWeight={600} sx={{ writingMode: 'vertical-rl', transform: 'rotate(180deg)', color: 'text.secondary' }}>
                    {coluna.nome}
                </Typography>
            </Box>
        );
    }

    const vazio = VAZIO[coluna.tipo];
    const IconeVazio = vazio.icone;

    return (
        <Box
            id={`coluna-${coluna.id}`}
            sx={{
                flex: cheia ? '1 1 100%' : '0 0 300px', display: 'flex', flexDirection: 'column', maxHeight: '100%', minHeight: 0,
                borderRadius: 3,
                bgcolor: destaque ? alpha(coluna.cor, 0.12) : alpha(coluna.cor, 0.045),
                border: '1px solid', borderColor: destaque ? coluna.cor : alpha(coluna.cor, 0.14),
                boxShadow: destaque ? `0 0 0 3px ${alpha(coluna.cor, 0.18)}` : 'none',
                opacity: aceita === false ? 0.45 : 1, transition: 'background-color .15s, border-color .15s, opacity .15s, box-shadow .15s',
            }}
        >
            <Box sx={{ px: 1.5, pt: 1.25, pb: 1.1 }}>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                    <Box sx={{ width: 10, height: 10, borderRadius: '3px', bgcolor: coluna.cor, flexShrink: 0 }} />
                    <Typography variant="subtitle2" fontWeight={700} noWrap sx={{ flex: 1, color: '#0F172A' }}>{coluna.nome}</Typography>
                    {sistema && (
                        <Tooltip title={DESCRICAO_SISTEMA[coluna.tipo]}>
                            <LockRoundedIcon sx={{ fontSize: 14, color: 'text.disabled' }} />
                        </Tooltip>
                    )}
                    <Box sx={{ minWidth: 24, px: 0.75, py: 0.1, borderRadius: 10, bgcolor: 'background.paper', border: '1px solid', borderColor: alpha(coluna.cor, 0.3), color: coluna.cor, textAlign: 'center' }}>
                        <Typography variant="caption" fontWeight={700}>{coluna.quantidade}</Typography>
                    </Box>
                    {!cheia && (coluna.tipo === 'ganho' || coluna.tipo === 'perdido') && (
                        <IconButton size="small" onClick={onAlternar} aria-label="Recolher coluna" sx={{ mr: -0.75 }}>
                            <ChevronRightRoundedIcon fontSize="small" />
                        </IconButton>
                    )}
                </Box>
                <Box sx={{ display: 'flex', alignItems: 'baseline', gap: 0.75, mt: 0.5, pl: 2.25 }}>
                    <Typography variant="body2" fontWeight={700} sx={{ fontVariantNumeric: 'tabular-nums' }}>{moedaCompacta(coluna.valor)}</Typography>
                    {noFunil && coluna.probabilidade !== null && (
                        <Tooltip title={`Valor ponderado: ${coluna.probabilidade}% de chance de fechamento`}>
                            <Typography variant="caption" color="text.secondary">
                                ≈ {moedaCompacta(coluna.ponderado)} · {coluna.probabilidade}%
                            </Typography>
                        </Tooltip>
                    )}
                </Box>
                {noFunil ? (
                    <Tooltip title={`${Math.round(participacao * 100)}% do valor em negociação`}>
                        <Box sx={{ height: 4, borderRadius: 2, bgcolor: alpha(coluna.cor, 0.15), mt: 1, overflow: 'hidden' }}>
                            <Box sx={{ width: `${Math.max(participacao > 0 ? 3 : 0, participacao * 100)}%`, height: '100%', bgcolor: coluna.cor, borderRadius: 2, transition: 'width .3s' }} />
                        </Box>
                    </Tooltip>
                ) : (
                    <Box sx={{ height: 4, borderRadius: 2, bgcolor: coluna.cor, mt: 1, opacity: 0.6 }} />
                )}
            </Box>

            <Box
                ref={setNodeRef}
                sx={{
                    flex: 1, minHeight: 120, overflowY: 'auto', px: 1, pb: 1.25, display: 'flex', flexDirection: 'column', gap: 1,
                    '&::-webkit-scrollbar': { width: 6 }, '&::-webkit-scrollbar-thumb': { bgcolor: 'rgba(15,23,42,.15)', borderRadius: 3 },
                }}
            >
                {children}
                {coluna.quantidade === 0 && (
                    <Box sx={{ border: '1.5px dashed', borderColor: alpha(coluna.cor, 0.3), borderRadius: 2, py: 3, px: 2, textAlign: 'center', color: alpha(coluna.cor, 0.7) }}>
                        <IconeVazio sx={{ fontSize: 26, mb: 0.5 }} />
                        <Typography variant="caption" color="text.secondary" component="div">{vazio.texto}</Typography>
                    </Box>
                )}
                {coluna.cards.length < coluna.quantidade && (
                    <Typography variant="caption" color="text.secondary" align="center" sx={{ py: 0.5 }}>
                        + {coluna.quantidade - coluna.cards.length} não exibidos — use os filtros
                    </Typography>
                )}
            </Box>
        </Box>
    );
}
