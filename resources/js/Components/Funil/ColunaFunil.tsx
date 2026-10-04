import React from 'react';
import { Box, IconButton, Tooltip, Typography, alpha } from '@mui/material';
import { useDroppable } from '@dnd-kit/core';
import ChevronLeftRoundedIcon from '@mui/icons-material/ChevronLeftRounded';
import ChevronRightRoundedIcon from '@mui/icons-material/ChevronRightRounded';
import LockRoundedIcon from '@mui/icons-material/LockRounded';
import { ColunaFunil as Coluna, moedaCompacta } from './tipos';

interface Props {
    coluna: Coluna;
    /** Durante um arraste: true = pode soltar aqui, false = não pode, null = nada sendo arrastado. */
    aceita: boolean | null;
    recolhida: boolean;
    onAlternar: () => void;
    children: React.ReactNode;
}

const DESCRICAO_SISTEMA: Record<string, string> = {
    aprovacao: 'Soltar aqui envia o orçamento para aprovação do administrador',
    ganho: 'Vendas aprovadas nos últimos 30 dias. Só o administrador aprova',
    perdido: 'Vendas perdidas nos últimos 30 dias. Arraste para uma etapa para reativar',
};

export function ColunaFunil({ coluna, aceita, recolhida, onAlternar, children }: Props) {
    const { setNodeRef, isOver } = useDroppable({ id: `etapa-${coluna.id}`, data: { coluna }, disabled: aceita === false });
    const destaque = aceita === true && isOver;
    const sistema = coluna.tipo !== 'aberta';

    if (recolhida) {
        return (
            <Box
                ref={setNodeRef}
                onClick={onAlternar}
                sx={{
                    flex: '0 0 48px', alignSelf: 'stretch', borderRadius: 3, cursor: 'pointer',
                    bgcolor: destaque ? alpha(coluna.cor, 0.18) : alpha(coluna.cor, 0.06),
                    border: '1px solid', borderColor: destaque ? coluna.cor : alpha(coluna.cor, 0.2),
                    display: 'flex', flexDirection: 'column', alignItems: 'center', py: 1.5, gap: 1,
                    opacity: aceita === false ? 0.4 : 1, transition: 'all .15s',
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

    return (
        <Box
            sx={{
                flex: '0 0 296px', display: 'flex', flexDirection: 'column', maxHeight: '100%', minHeight: 0,
                borderRadius: 3, bgcolor: destaque ? alpha(coluna.cor, 0.1) : '#EEF2F6',
                border: '2px solid', borderColor: destaque ? coluna.cor : 'transparent',
                opacity: aceita === false ? 0.45 : 1, transition: 'background-color .15s, border-color .15s, opacity .15s',
            }}
        >
            <Box sx={{ px: 1.5, pt: 1.25, pb: 1 }}>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                    <Box sx={{ width: 10, height: 10, borderRadius: '50%', bgcolor: coluna.cor, flexShrink: 0 }} />
                    <Typography variant="subtitle2" fontWeight={700} noWrap sx={{ flex: 1 }}>{coluna.nome}</Typography>
                    {sistema && (
                        <Tooltip title={DESCRICAO_SISTEMA[coluna.tipo]}>
                            <LockRoundedIcon sx={{ fontSize: 14, color: 'text.disabled' }} />
                        </Tooltip>
                    )}
                    <Box sx={{ px: 0.9, py: 0.1, borderRadius: 10, bgcolor: alpha(coluna.cor, 0.14), color: coluna.cor }}>
                        <Typography variant="caption" fontWeight={700}>{coluna.quantidade}</Typography>
                    </Box>
                    {(coluna.tipo === 'ganho' || coluna.tipo === 'perdido') && (
                        <IconButton size="small" onClick={onAlternar} aria-label="Recolher coluna" sx={{ mr: -0.75 }}>
                            <ChevronRightRoundedIcon fontSize="small" />
                        </IconButton>
                    )}
                </Box>
                <Box sx={{ display: 'flex', alignItems: 'baseline', gap: 0.75, mt: 0.5, pl: 2.25 }}>
                    <Typography variant="body2" fontWeight={700}>{moedaCompacta(coluna.valor)}</Typography>
                    {coluna.tipo === 'aberta' && coluna.probabilidade !== null && (
                        <Tooltip title={`Valor ponderado: ${coluna.probabilidade}% de chance de fechamento`}>
                            <Typography variant="caption" color="text.secondary">
                                ≈ {moedaCompacta(coluna.ponderado)} · {coluna.probabilidade}%
                            </Typography>
                        </Tooltip>
                    )}
                </Box>
                <Box sx={{ height: 3, borderRadius: 2, bgcolor: coluna.cor, mt: 1, opacity: 0.85 }} />
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
                    <Box sx={{ border: '1.5px dashed', borderColor: alpha(coluna.cor, 0.35), borderRadius: 2, py: 3, textAlign: 'center' }}>
                        <Typography variant="caption" color="text.disabled">
                            {coluna.tipo === 'aprovacao' ? 'Arraste aqui para enviar para aprovação' : 'Nenhum orçamento'}
                        </Typography>
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
