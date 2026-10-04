import React from 'react';
import { Alert, Box, Button, Chip, Divider, Drawer, IconButton, Typography } from '@mui/material';
import CloseRoundedIcon from '@mui/icons-material/CloseRounded';
import PlayArrowRoundedIcon from '@mui/icons-material/PlayArrowRounded';
import BlockRoundedIcon from '@mui/icons-material/BlockRounded';
import InboxRoundedIcon from '@mui/icons-material/InboxRounded';
import { CardFunil, moeda } from './tipos';

interface Props {
    aberta: boolean;
    itens: CardFunil[];
    diasLimite: number;
    mostrarConsultor: boolean;
    onFechar: () => void;
    onIniciar: (card: CardFunil) => void;
    onDescartar: (card: CardFunil) => void;
    onAbrir: (card: CardFunil) => void;
}

/**
 * Orçamentos recém-gerados que ainda não começaram a ser trabalhados. Ficam fora do quadro
 * para não poluí-lo; o mais antigo aparece primeiro (docs/funil-de-vendas.md, seção 8).
 */
export function CaixaEntrada({ aberta, itens, diasLimite, mostrarConsultor, onFechar, onIniciar, onDescartar, onAbrir }: Props) {
    const esfriando = itens.filter((i) => i.esfriando).length;

    return (
        <Drawer anchor="right" open={aberta} onClose={onFechar} PaperProps={{ sx: { width: { xs: '100%', sm: 420 } } }}>
            <Box sx={{ px: 2.5, py: 2, display: 'flex', alignItems: 'center', gap: 1 }}>
                <InboxRoundedIcon color="primary" />
                <Box sx={{ flex: 1 }}>
                    <Typography variant="subtitle1" fontWeight={700}>Caixa de entrada</Typography>
                    <Typography variant="caption" color="text.secondary">Orçamentos gerados aguardando o primeiro atendimento</Typography>
                </Box>
                <IconButton onClick={onFechar} aria-label="Fechar"><CloseRoundedIcon /></IconButton>
            </Box>
            <Divider />

            {esfriando > 0 && (
                <Alert severity="warning" sx={{ m: 2, mb: 0 }}>
                    {esfriando} {esfriando === 1 ? 'orçamento aguarda' : 'orçamentos aguardam'} há {diasLimite} dias ou mais.
                    Inicie o atendimento ou descarte com um motivo.
                </Alert>
            )}

            <Box sx={{ p: 2, display: 'flex', flexDirection: 'column', gap: 1.25, overflowY: 'auto' }}>
                {itens.length === 0 && (
                    <Box sx={{ textAlign: 'center', py: 6, color: 'text.secondary' }}>
                        <InboxRoundedIcon sx={{ fontSize: 48, color: 'text.disabled' }} />
                        <Typography variant="body2">Nenhum orçamento aguardando atendimento.</Typography>
                    </Box>
                )}
                {itens.map((card) => (
                    <Box
                        key={card.id}
                        sx={{ border: '1px solid', borderColor: card.esfriando ? 'warning.light' : 'divider', borderRadius: 2, p: 1.5, bgcolor: 'background.paper' }}
                    >
                        <Box sx={{ display: 'flex', alignItems: 'flex-start', gap: 1, cursor: 'pointer' }} onClick={() => onAbrir(card)}>
                            <Box sx={{ flex: 1, minWidth: 0 }}>
                                <Typography variant="body2" fontWeight={600} noWrap>{card.cliente}</Typography>
                                <Typography variant="caption" color="text.secondary" component="div">
                                    #{card.id} · {moeda(card.valor)}{card.potencia_kwp ? ` · ${card.potencia_kwp.toLocaleString('pt-BR')} kWp` : ''}
                                    {card.cidade ? ` · ${card.cidade}` : ''}
                                </Typography>
                                {mostrarConsultor && card.consultor && (
                                    <Typography variant="caption" color="text.secondary" component="div">{card.consultor.nome}</Typography>
                                )}
                            </Box>
                            <Chip
                                size="small"
                                label={card.dias_na_etapa === 0 ? 'hoje' : `há ${card.dias_na_etapa}d`}
                                color={card.esfriando ? 'warning' : 'default'}
                                variant={card.esfriando ? 'filled' : 'outlined'}
                                sx={{ height: 22, fontSize: '0.7rem' }}
                            />
                        </Box>
                        <Box sx={{ display: 'flex', gap: 1, mt: 1.25 }}>
                            <Button size="small" variant="contained" startIcon={<PlayArrowRoundedIcon />} onClick={() => onIniciar(card)} sx={{ flex: 1 }}>
                                Iniciar atendimento
                            </Button>
                            <Button size="small" color="inherit" startIcon={<BlockRoundedIcon />} onClick={() => onDescartar(card)}>
                                Descartar
                            </Button>
                        </Box>
                    </Box>
                ))}
            </Box>
        </Drawer>
    );
}
