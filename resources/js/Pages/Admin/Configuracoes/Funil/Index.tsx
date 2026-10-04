import React, { useState } from 'react';
import {
    Alert, Box, Button, Card, CardContent, CardHeader, Chip, Dialog, DialogActions, DialogContent, DialogTitle, Divider,
    FormControlLabel, Grid, IconButton, InputAdornment, MenuItem, Switch, Table, TableBody, TableCell, TableHead, TableRow,
    TextField, Tooltip, Typography,
} from '@mui/material';
import AddRoundedIcon from '@mui/icons-material/AddRounded';
import EditRoundedIcon from '@mui/icons-material/EditRounded';
import DeleteRoundedIcon from '@mui/icons-material/DeleteRounded';
import ArrowUpwardRoundedIcon from '@mui/icons-material/ArrowUpwardRounded';
import ArrowDownwardRoundedIcon from '@mui/icons-material/ArrowDownwardRounded';
import LockRoundedIcon from '@mui/icons-material/LockRounded';
import ViewKanbanRoundedIcon from '@mui/icons-material/ViewKanbanRounded';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { ConfirmDialog } from '@/Components/UI/ConfirmDialog';

type TipoEtapa = 'aberta' | 'aprovacao' | 'ganho' | 'perdido';

interface Etapa {
    id: number; nome: string; cor: string; tipo: TipoEtapa; ordem: number;
    probabilidade: number | null; sla_dias: number | null; ativa: boolean; orcamentos_count: number;
}
interface Motivo { id: number; nome: string; reativavel: boolean; reativar_apos_dias: number | null; ativo: boolean; orcamentos_count: number }
interface Props {
    etapas: Etapa[];
    motivos: Motivo[];
    parametros: { dias_caixa_entrada: number; max_tentativas_reativacao: number };
}

const PALETA = ['#64748B', '#3B82F6', '#6366F1', '#8B5CF6', '#EC4899', '#EF4444', '#F97316', '#F59E0B', '#EAB308', '#84CC16', '#10B981', '#06B6D4'];

const ROTULO_TIPO: Record<TipoEtapa, string> = {
    aberta: 'Etapa de venda', aprovacao: 'Sistema · aprovação', ganho: 'Sistema · ganho', perdido: 'Sistema · perdido',
};

export default function FunilConfigIndex({ etapas, motivos, parametros }: Props) {
    const abertas = etapas.filter((e) => e.tipo === 'aberta');
    const sistema = etapas.filter((e) => e.tipo !== 'aberta');

    const [etapaEdit, setEtapaEdit] = useState<Etapa | 'nova' | null>(null);
    const [etapaExcluir, setEtapaExcluir] = useState<Etapa | null>(null);
    const [motivoEdit, setMotivoEdit] = useState<Motivo | 'novo' | null>(null);
    const [motivoExcluir, setMotivoExcluir] = useState<Motivo | null>(null);

    const params = useForm(parametros);

    const reordenar = (indice: number, direcao: -1 | 1) => {
        const ids = abertas.map((e) => e.id);
        [ids[indice], ids[indice + direcao]] = [ids[indice + direcao], ids[indice]];
        router.put(route('admin.configuracoes.funil.etapas.reordenar'), { ids }, { preserveScroll: true });
    };

    return (
        <AppLayout title="Funil de vendas">
            <Head title="Configurar funil de vendas" />
            <PageHeader
                title="Funil de vendas"
                subtitle="Etapas do Kanban, motivos de perda e regras de acompanhamento"
                breadcrumbs={[{ label: 'Configurações' }, { label: 'Funil de vendas' }]}
                action={<Button component={Link} href={route('admin.funil.index')} startIcon={<ViewKanbanRoundedIcon />}>Ver quadro</Button>}
            />

            <Grid container spacing={3}>
                {/* Etapas */}
                <Grid size={{ xs: 12, lg: 7 }}>
                    <Card>
                        <CardHeader
                            title="Etapas do funil" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }}
                            subheader="A ordem abaixo é a ordem das colunas no quadro"
                            action={<Button size="small" variant="contained" startIcon={<AddRoundedIcon />} onClick={() => setEtapaEdit('nova')}>Nova etapa</Button>}
                        />
                        <Divider />
                        <Box sx={{ overflowX: 'auto' }}>
                            <Table size="small">
                                <TableHead>
                                    <TableRow>
                                        <TableCell sx={{ width: 72 }}>Ordem</TableCell>
                                        <TableCell>Etapa</TableCell>
                                        <TableCell align="center">Prob.</TableCell>
                                        <TableCell align="center">SLA</TableCell>
                                        <TableCell align="center">Em negociação</TableCell>
                                        <TableCell align="right">Ações</TableCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {[...abertas, ...sistema].map((e) => {
                                        const i = abertas.indexOf(e);
                                        return (
                                            <TableRow key={e.id} hover sx={{ opacity: e.ativa ? 1 : 0.5 }}>
                                                <TableCell>
                                                    {e.tipo === 'aberta' ? (
                                                        <Box sx={{ display: 'flex' }}>
                                                            <IconButton size="small" disabled={i === 0} onClick={() => reordenar(i, -1)} aria-label="Subir"><ArrowUpwardRoundedIcon fontSize="inherit" /></IconButton>
                                                            <IconButton size="small" disabled={i === abertas.length - 1} onClick={() => reordenar(i, 1)} aria-label="Descer"><ArrowDownwardRoundedIcon fontSize="inherit" /></IconButton>
                                                        </Box>
                                                    ) : <LockRoundedIcon sx={{ fontSize: 16, color: 'text.disabled', ml: 1 }} />}
                                                </TableCell>
                                                <TableCell>
                                                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                                                        <Box sx={{ width: 14, height: 14, borderRadius: '4px', bgcolor: e.cor, flexShrink: 0 }} />
                                                        <Box>
                                                            <Typography variant="body2" fontWeight={600}>{e.nome}</Typography>
                                                            <Typography variant="caption" color="text.secondary">
                                                                {ROTULO_TIPO[e.tipo]}{!e.ativa && ' · inativa'}
                                                            </Typography>
                                                        </Box>
                                                    </Box>
                                                </TableCell>
                                                <TableCell align="center">{e.probabilidade !== null ? `${e.probabilidade}%` : '—'}</TableCell>
                                                <TableCell align="center">{e.sla_dias ? `${e.sla_dias}d` : '—'}</TableCell>
                                                <TableCell align="center">{e.tipo === 'aberta' ? <Chip label={e.orcamentos_count} size="small" variant="outlined" /> : '—'}</TableCell>
                                                <TableCell align="right" sx={{ whiteSpace: 'nowrap' }}>
                                                    <Tooltip title="Editar"><IconButton size="small" onClick={() => setEtapaEdit(e)}><EditRoundedIcon fontSize="small" /></IconButton></Tooltip>
                                                    {e.tipo === 'aberta' && (
                                                        <Tooltip title="Excluir"><IconButton size="small" color="error" onClick={() => setEtapaExcluir(e)}><DeleteRoundedIcon fontSize="small" /></IconButton></Tooltip>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })}
                                </TableBody>
                            </Table>
                        </Box>
                        <CardContent>
                            <Typography variant="caption" color="text.secondary">
                                Etapas de sistema (cadeado) têm comportamento fixo: <b>Em aprovação</b> envia para o administrador,
                                <b> Ganho</b> reúne vendas aprovadas e <b>Perdido</b> exige motivo. Podem ser renomeadas e recoloridas.
                            </Typography>
                        </CardContent>
                    </Card>
                </Grid>

                <Grid size={{ xs: 12, lg: 5 }}>
                    {/* Parâmetros */}
                    <Card sx={{ mb: 3 }}>
                        <CardHeader title="Acompanhamento" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                        <Divider />
                        <CardContent>
                            <Box component="form" onSubmit={(ev) => { ev.preventDefault(); params.put(route('admin.configuracoes.funil.parametros'), { preserveScroll: true }); }}
                                sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
                                <TextField
                                    label="Alerta na caixa de entrada após" type="number" value={params.data.dias_caixa_entrada}
                                    onChange={(e) => params.setData('dias_caixa_entrada', Number(e.target.value))}
                                    InputProps={{ endAdornment: <InputAdornment position="end">dias</InputAdornment> }}
                                    error={!!params.errors.dias_caixa_entrada}
                                    helperText={params.errors.dias_caixa_entrada ?? 'Orçamento sem atendimento recebe o selo "Esfriando"'}
                                />
                                <TextField
                                    label="Máximo de reativações" type="number" value={params.data.max_tentativas_reativacao}
                                    onChange={(e) => params.setData('max_tentativas_reativacao', Number(e.target.value))}
                                    error={!!params.errors.max_tentativas_reativacao}
                                    helperText={params.errors.max_tentativas_reativacao ?? 'Depois disso, o orçamento deixa de ser sugerido para recuperação'}
                                />
                                <Box><Button type="submit" variant="contained" disabled={params.processing}>Salvar</Button></Box>
                            </Box>
                        </CardContent>
                    </Card>

                    {/* Motivos */}
                    <Card>
                        <CardHeader
                            title="Motivos de perda" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }}
                            action={<Button size="small" startIcon={<AddRoundedIcon />} onClick={() => setMotivoEdit('novo')}>Novo</Button>}
                        />
                        <Divider />
                        <Table size="small">
                            <TableBody>
                                {motivos.map((m) => (
                                    <TableRow key={m.id} hover sx={{ opacity: m.ativo ? 1 : 0.5 }}>
                                        <TableCell>
                                            <Typography variant="body2" fontWeight={500}>{m.nome}</Typography>
                                            <Typography variant="caption" color="text.secondary">
                                                {m.reativavel ? `Sugerir recuperação após ${m.reativar_apos_dias} dias` : 'Perda definitiva'}
                                                {!m.ativo && ' · inativo'}{m.orcamentos_count > 0 && ` · ${m.orcamentos_count} uso(s)`}
                                            </Typography>
                                        </TableCell>
                                        <TableCell align="right" sx={{ whiteSpace: 'nowrap' }}>
                                            <IconButton size="small" onClick={() => setMotivoEdit(m)}><EditRoundedIcon fontSize="small" /></IconButton>
                                            <IconButton size="small" color="error" onClick={() => setMotivoExcluir(m)}><DeleteRoundedIcon fontSize="small" /></IconButton>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </Card>
                </Grid>
            </Grid>

            <DialogEtapa etapa={etapaEdit} onFechar={() => setEtapaEdit(null)} />
            <DialogExcluirEtapa etapa={etapaExcluir} destinos={abertas.filter((e) => e.ativa && e.id !== etapaExcluir?.id)} onFechar={() => setEtapaExcluir(null)} />
            <DialogMotivo motivo={motivoEdit} onFechar={() => setMotivoEdit(null)} />
            <ConfirmDialog
                open={!!motivoExcluir}
                title="Excluir motivo"
                message={`Excluir o motivo "${motivoExcluir?.nome}"? Motivos já usados não podem ser excluídos — desative-os.`}
                confirmLabel="Excluir"
                onConfirm={() => motivoExcluir && router.delete(route('admin.configuracoes.funil.motivos.destroy', motivoExcluir.id), { preserveScroll: true, onFinish: () => setMotivoExcluir(null) })}
                onCancel={() => setMotivoExcluir(null)}
            />
        </AppLayout>
    );
}

function DialogEtapa({ etapa, onFechar }: { etapa: Etapa | 'nova' | null; onFechar: () => void }) {
    const nova = etapa === 'nova';
    const atual = etapa && etapa !== 'nova' ? etapa : null;
    const sistema = !!atual && atual.tipo !== 'aberta';

    const form = useForm({
        nome: '', cor: PALETA[1], probabilidade: '' as number | '', sla_dias: '' as number | '', ativa: true,
    });

    React.useEffect(() => {
        if (!etapa) return;
        form.clearErrors();
        form.setData(atual
            ? { nome: atual.nome, cor: atual.cor, probabilidade: atual.probabilidade ?? '', sla_dias: atual.sla_dias ?? '', ativa: atual.ativa }
            : { nome: '', cor: PALETA[1], probabilidade: '', sla_dias: '', ativa: true });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [etapa]);

    const salvar = (ev: React.FormEvent) => {
        ev.preventDefault();
        const ok = { preserveScroll: true, onSuccess: onFechar };
        if (atual) form.put(route('admin.configuracoes.funil.etapas.update', atual.id), ok);
        else form.post(route('admin.configuracoes.funil.etapas.store'), ok);
    };

    return (
        <Dialog open={!!etapa} onClose={onFechar} maxWidth="xs" fullWidth>
            <form onSubmit={salvar}>
                <DialogTitle>{nova ? 'Nova etapa' : 'Editar etapa'}</DialogTitle>
                <DialogContent sx={{ display: 'flex', flexDirection: 'column', gap: 2, pt: '8px !important' }}>
                    {sistema && <Alert severity="info" sx={{ py: 0 }}>Etapa de sistema: só nome e cor{atual?.tipo === 'aprovacao' ? ' e SLA' : ''} podem ser alterados.</Alert>}
                    <TextField label="Nome *" value={form.data.nome} onChange={(e) => form.setData('nome', e.target.value)}
                        error={!!form.errors.nome} helperText={form.errors.nome} autoFocus fullWidth inputProps={{ maxLength: 60 }} />
                    <Box>
                        <Typography variant="caption" color="text.secondary">Cor</Typography>
                        <Box sx={{ display: 'flex', gap: 0.75, flexWrap: 'wrap', mt: 0.5, alignItems: 'center' }}>
                            {PALETA.map((c) => (
                                <Box key={c} onClick={() => form.setData('cor', c)} role="button" aria-label={`Cor ${c}`}
                                    sx={{ width: 26, height: 26, borderRadius: '50%', bgcolor: c, cursor: 'pointer',
                                          outline: form.data.cor.toUpperCase() === c ? `3px solid ${c}55` : 'none', outlineOffset: 2,
                                          border: form.data.cor.toUpperCase() === c ? '2px solid #fff' : 'none' }} />
                            ))}
                            <TextField size="small" value={form.data.cor} onChange={(e) => form.setData('cor', e.target.value)} sx={{ width: 110 }}
                                error={!!form.errors.cor} InputProps={{ startAdornment: <Box sx={{ width: 14, height: 14, borderRadius: '4px', bgcolor: form.data.cor, mr: 1 }} /> }} />
                        </Box>
                        {form.errors.cor && <Typography variant="caption" color="error">Use o formato #RRGGBB.</Typography>}
                    </Box>
                    {!sistema && (
                        <TextField label="Probabilidade de fechamento" type="number" value={form.data.probabilidade}
                            onChange={(e) => form.setData('probabilidade', e.target.value === '' ? '' : Number(e.target.value))}
                            InputProps={{ endAdornment: <InputAdornment position="end">%</InputAdornment> }}
                            error={!!form.errors.probabilidade} helperText={form.errors.probabilidade ?? 'Usada no valor ponderado (previsão de vendas)'} />
                    )}
                    {(!sistema || atual?.tipo === 'aprovacao') && (
                        <TextField label="Tempo esperado na etapa (SLA)" type="number" value={form.data.sla_dias}
                            onChange={(e) => form.setData('sla_dias', e.target.value === '' ? '' : Number(e.target.value))}
                            InputProps={{ endAdornment: <InputAdornment position="end">dias</InputAdornment> }}
                            error={!!form.errors.sla_dias} helperText={form.errors.sla_dias ?? 'Acima disso o card fica em alerta'} />
                    )}
                    {atual && !sistema && (
                        <FormControlLabel control={<Switch checked={form.data.ativa} onChange={(e) => form.setData('ativa', e.target.checked)} />}
                            label="Etapa ativa (aparece no quadro)" />
                    )}
                </DialogContent>
                <DialogActions sx={{ px: 3, pb: 2 }}>
                    <Button onClick={onFechar}>Cancelar</Button>
                    <Button type="submit" variant="contained" disabled={form.processing}>{nova ? 'Criar' : 'Salvar'}</Button>
                </DialogActions>
            </form>
        </Dialog>
    );
}

function DialogExcluirEtapa({ etapa, destinos, onFechar }: { etapa: Etapa | null; destinos: Etapa[]; onFechar: () => void }) {
    const form = useForm({ destino_id: '' as number | '' });

    React.useEffect(() => { if (etapa) { form.reset(); form.clearErrors(); } }, [etapa]); // eslint-disable-line react-hooks/exhaustive-deps

    return (
        <Dialog open={!!etapa} onClose={onFechar} maxWidth="xs" fullWidth>
            <DialogTitle>Excluir etapa</DialogTitle>
            <DialogContent sx={{ display: 'flex', flexDirection: 'column', gap: 2, pt: '8px !important' }}>
                <Typography variant="body2">
                    Excluir <b>{etapa?.nome}</b>? Os orçamentos que estiverem nela (inclusive perdidos e em aprovação que saíram dela) serão movidos para a etapa escolhida.
                </Typography>
                <TextField select label="Mover orçamentos para" value={form.data.destino_id}
                    onChange={(e) => form.setData('destino_id', Number(e.target.value))}
                    error={!!form.errors.destino_id} helperText={form.errors.destino_id ?? 'Obrigatório se houver orçamentos vinculados'} fullWidth>
                    {destinos.map((d) => <MenuItem key={d.id} value={d.id}>{d.nome}</MenuItem>)}
                </TextField>
            </DialogContent>
            <DialogActions sx={{ px: 3, pb: 2 }}>
                <Button onClick={onFechar}>Cancelar</Button>
                <Button color="error" variant="contained" disabled={form.processing}
                    onClick={() => etapa && form.delete(route('admin.configuracoes.funil.etapas.destroy', etapa.id), { preserveScroll: true, onSuccess: onFechar })}>
                    Excluir
                </Button>
            </DialogActions>
        </Dialog>
    );
}

function DialogMotivo({ motivo, onFechar }: { motivo: Motivo | 'novo' | null; onFechar: () => void }) {
    const atual = motivo && motivo !== 'novo' ? motivo : null;
    const form = useForm({ nome: '', reativavel: true, reativar_apos_dias: 30 as number | '', ativo: true });

    React.useEffect(() => {
        if (!motivo) return;
        form.clearErrors();
        form.setData(atual
            ? { nome: atual.nome, reativavel: atual.reativavel, reativar_apos_dias: atual.reativar_apos_dias ?? '', ativo: atual.ativo }
            : { nome: '', reativavel: true, reativar_apos_dias: 30, ativo: true });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [motivo]);

    const salvar = (ev: React.FormEvent) => {
        ev.preventDefault();
        const ok = { preserveScroll: true, onSuccess: onFechar };
        if (atual) form.put(route('admin.configuracoes.funil.motivos.update', atual.id), ok);
        else form.post(route('admin.configuracoes.funil.motivos.store'), ok);
    };

    return (
        <Dialog open={!!motivo} onClose={onFechar} maxWidth="xs" fullWidth>
            <form onSubmit={salvar}>
                <DialogTitle>{atual ? 'Editar motivo de perda' : 'Novo motivo de perda'}</DialogTitle>
                <DialogContent sx={{ display: 'flex', flexDirection: 'column', gap: 2, pt: '8px !important' }}>
                    <TextField label="Motivo *" value={form.data.nome} onChange={(e) => form.setData('nome', e.target.value)}
                        error={!!form.errors.nome} helperText={form.errors.nome} autoFocus fullWidth inputProps={{ maxLength: 80 }} />
                    <FormControlLabel control={<Switch checked={form.data.reativavel} onChange={(e) => form.setData('reativavel', e.target.checked)} />}
                        label="Vale tentar recuperar depois" />
                    {form.data.reativavel && (
                        <TextField label="Sugerir recuperação após" type="number" value={form.data.reativar_apos_dias}
                            onChange={(e) => form.setData('reativar_apos_dias', e.target.value === '' ? '' : Number(e.target.value))}
                            InputProps={{ endAdornment: <InputAdornment position="end">dias</InputAdornment> }}
                            error={!!form.errors.reativar_apos_dias} helperText={form.errors.reativar_apos_dias} />
                    )}
                    {atual && (
                        <FormControlLabel control={<Switch checked={form.data.ativo} onChange={(e) => form.setData('ativo', e.target.checked)} />}
                            label="Ativo (aparece ao marcar perda)" />
                    )}
                </DialogContent>
                <DialogActions sx={{ px: 3, pb: 2 }}>
                    <Button onClick={onFechar}>Cancelar</Button>
                    <Button type="submit" variant="contained" disabled={form.processing}>{atual ? 'Salvar' : 'Criar'}</Button>
                </DialogActions>
            </form>
        </Dialog>
    );
}
