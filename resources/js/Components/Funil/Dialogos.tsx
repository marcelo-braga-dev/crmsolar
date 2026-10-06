import React, { useEffect, useState } from 'react';
import {
    Alert, Box, Button, Chip, Dialog, DialogActions, DialogContent, DialogTitle, MenuItem, TextField, Typography,
} from '@mui/material';
import { MotivoPerda, paraInputDataHora } from './tipos';

// ── Perda ──────────────────────────────────────────────────────────────────

interface PerdaProps {
    aberto: boolean;
    cliente?: string;
    motivos: MotivoPerda[];
    enviando: boolean;
    onFechar: () => void;
    onConfirmar: (motivoId: number, observacao: string) => void;
}

export function DialogPerda({ aberto, cliente, motivos, enviando, onFechar, onConfirmar }: PerdaProps) {
    const [motivo, setMotivo] = useState<number | ''>('');
    const [observacao, setObservacao] = useState('');

    useEffect(() => { if (aberto) { setMotivo(''); setObservacao(''); } }, [aberto]);

    const escolhido = motivos.find((m) => m.id === motivo);

    return (
        <Dialog open={aberto} onClose={onFechar} maxWidth="xs" fullWidth>
            <DialogTitle>Marcar como perdido</DialogTitle>
            <DialogContent sx={{ display: 'flex', flexDirection: 'column', gap: 2, pt: '8px !important' }}>
                {cliente && <Typography variant="body2" color="text.secondary">{cliente}</Typography>}
                <TextField select label="Motivo da perda *" value={motivo} onChange={(e) => setMotivo(Number(e.target.value))} fullWidth autoFocus>
                    {motivos.map((m) => <MenuItem key={m.id} value={m.id}>{m.nome}</MenuItem>)}
                </TextField>
                {escolhido && (
                    <Alert severity={escolhido.reativavel ? 'info' : 'warning'} sx={{ py: 0 }}>
                        {escolhido.reativavel
                            ? 'Este orçamento será sugerido para recuperação no futuro.'
                            : 'Motivo definitivo: o orçamento não será sugerido para recuperação.'}
                    </Alert>
                )}
                <TextField
                    label="Observação" value={observacao} onChange={(e) => setObservacao(e.target.value)}
                    multiline minRows={2} fullWidth inputProps={{ maxLength: 1000 }}
                    placeholder="Ex.: cliente fechou com concorrente por R$ 28 mil"
                />
            </DialogContent>
            <DialogActions sx={{ px: 3, pb: 2 }}>
                <Button onClick={onFechar}>Cancelar</Button>
                <Button
                    variant="contained" color="error" disabled={!motivo || enviando}
                    onClick={() => motivo && onConfirmar(motivo, observacao)}
                >
                    Marcar como perdido
                </Button>
            </DialogActions>
        </Dialog>
    );
}

// ── Agenda (registrar contato / iniciar atendimento / reativar) ────────────

interface AgendaProps {
    aberto: boolean;
    titulo: string;
    cliente?: string;
    /** Orientação exibida acima dos campos. */
    descricao?: string;
    /** Mostra o campo de anotação (registrar contato). */
    comNota?: boolean;
    textoConfirmar: string;
    enviando: boolean;
    onFechar: () => void;
    onConfirmar: (dados: { nota: string; data: string | null }) => void;
}

const atalhos = (): { rotulo: string; data: Date }[] => {
    const em = (dias: number, hora: number) => { const d = new Date(); d.setDate(d.getDate() + dias); d.setHours(hora, 0, 0, 0); return d; };
    const hoje17 = em(0, 17);
    return [
        ...(hoje17 > new Date() ? [{ rotulo: 'Hoje 17h', data: hoje17 }] : []),
        { rotulo: 'Amanhã 9h', data: em(1, 9) },
        { rotulo: 'Em 3 dias', data: em(3, 9) },
        { rotulo: 'Em 1 semana', data: em(7, 9) },
    ];
};

export function DialogAgenda({ aberto, titulo, cliente, descricao, comNota, textoConfirmar, enviando, onFechar, onConfirmar }: AgendaProps) {
    const [nota, setNota] = useState('');
    const [data, setData] = useState('');

    useEffect(() => { if (aberto) { setNota(''); setData(paraInputDataHora(atalhos()[0].data)); } }, [aberto]);

    const invalido = comNota ? !nota.trim() && !data : false;

    return (
        <Dialog open={aberto} onClose={onFechar} maxWidth="xs" fullWidth>
            <DialogTitle>{titulo}</DialogTitle>
            <DialogContent sx={{ display: 'flex', flexDirection: 'column', gap: 2, pt: '8px !important' }}>
                {cliente && <Typography variant="body2" color="text.secondary">{cliente}</Typography>}
                {descricao && <Alert severity="info" sx={{ py: 0 }}>{descricao}</Alert>}
                {comNota && (
                    <TextField
                        label="O que foi conversado" value={nota} onChange={(e) => setNota(e.target.value)}
                        multiline minRows={3} fullWidth autoFocus inputProps={{ maxLength: 1000 }}
                    />
                )}
                <Box>
                    <TextField
                        label="Próximo contato" type="datetime-local" value={data} onChange={(e) => setData(e.target.value)}
                        fullWidth InputLabelProps={{ shrink: true }} inputProps={{ min: paraInputDataHora(new Date()) }}
                    />
                    <Box sx={{ display: 'flex', gap: 0.75, mt: 1, flexWrap: 'wrap' }}>
                        {atalhos().map((a) => (
                            <Chip key={a.rotulo} label={a.rotulo} size="small" variant="outlined" onClick={() => setData(paraInputDataHora(a.data))} />
                        ))}
                        <Chip label="Sem data" size="small" variant="outlined" onClick={() => setData('')} />
                    </Box>
                </Box>
            </DialogContent>
            <DialogActions sx={{ px: 3, pb: 2 }}>
                <Button onClick={onFechar}>Cancelar</Button>
                <Button variant="contained" disabled={invalido || enviando} onClick={() => onConfirmar({ nota: nota.trim(), data: data || null })}>
                    {textoConfirmar}
                </Button>
            </DialogActions>
        </Dialog>
    );
}
