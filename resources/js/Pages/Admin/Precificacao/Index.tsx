import React, { useMemo, useState } from 'react';
import {
    Box,
    Button,
    Card,
    Chip,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    Divider,
    IconButton,
    MenuItem,
    Stack,
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableRow,
    Tab,
    Tabs,
    TextField,
    Tooltip,
    Typography,
} from '@mui/material';
import AddRoundedIcon from '@mui/icons-material/AddRounded';
import EditRoundedIcon from '@mui/icons-material/EditRounded';
import DeleteRoundedIcon from '@mui/icons-material/DeleteRounded';
import CalculateRoundedIcon from '@mui/icons-material/CalculateRounded';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { ConfirmDialog } from '@/Components/UI/ConfirmDialog';
import { PageProps } from '@/types';

interface Faixa {
    id: number;
    nome: string;
    potencia_min: string;
    potencia_max: string | null;
    margem: string;
    ordem: number;
}

interface EstadoRow {
    id: number | null;
    estado: string;
    nome_estado: string;
    margem: number;
}

interface FornecedorRow {
    id: number;
    nome: string;
    ativo: boolean;
    margem: number;
}

interface Props extends PageProps {
    faixas: Faixa[];
    estados: EstadoRow[];
    fornecedores: FornecedorRow[];
}

const fmtMoney = (v: number) => v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
const fmtPct = (v: number) => `${v.toFixed(2)}%`;

function faixaLabel(f: Faixa) {
    const min = parseFloat(f.potencia_min);
    const max = f.potencia_max ? parseFloat(f.potencia_max) : null;
    if (min === 0 && max) return `Até ${max} kWp`;
    if (max) return `${min} – ${max} kWp`;
    return `Acima de ${min} kWp`;
}

function encontrarFaixa(potencia: number, faixas: Faixa[]): Faixa | null {
    const ordenadas = [...faixas].sort((a, b) => parseFloat(a.potencia_min) - parseFloat(b.potencia_min));
    const match = ordenadas.find((f) => {
        const min = parseFloat(f.potencia_min);
        const max = f.potencia_max ? parseFloat(f.potencia_max) : null;
        return potencia >= min && (max === null || potencia <= max);
    });
    return match ?? ordenadas[ordenadas.length - 1] ?? null;
}

export default function PrecificacaoIndex({ faixas, estados, fornecedores }: Props) {
    const [tab, setTab] = useState(0);

    // ── Simulador ────────────────────────────────────────────────────────
    const [simCusto, setSimCusto] = useState('20000');
    const [simPotencia, setSimPotencia] = useState('10');
    const [simEstado, setSimEstado] = useState(estados[0]?.estado ?? 'SP');
    const [simFornecedor, setSimFornecedor] = useState<number | ''>(fornecedores[0]?.id ?? '');

    const simulacao = useMemo(() => {
        const custo = parseFloat(simCusto) || 0;
        const potencia = parseFloat(simPotencia) || 0;
        const faixa = encontrarFaixa(potencia, faixas);
        const estado = estados.find((e) => e.estado === simEstado) ?? null;
        const fornecedor = fornecedores.find((f) => f.id === simFornecedor) ?? null;

        const mPrincipal = faixa ? parseFloat(faixa.margem) : 0;
        const mEstado = estado?.margem ?? 0;
        const mFornecedor = fornecedor?.margem ?? 0;
        const margemTotal = mPrincipal + mEstado + mFornecedor;
        const precoVenda = custo * (1 + margemTotal / 100);

        return {
            custo,
            faixa,
            estado,
            fornecedor,
            mPrincipal,
            mEstado,
            mFornecedor,
            margemTotal,
            valorPrincipal: (custo * mPrincipal) / 100,
            valorEstado: (custo * mEstado) / 100,
            valorFornecedor: (custo * mFornecedor) / 100,
            precoVenda,
        };
    }, [simCusto, simPotencia, simEstado, simFornecedor, faixas, estados, fornecedores]);

    // ── Margem Principal (faixas) ───────────────────────────────────────
    const [faixaModalOpen, setFaixaModalOpen] = useState(false);
    const [faixaEditTarget, setFaixaEditTarget] = useState<Faixa | null>(null);
    const [faixaDeleteTarget, setFaixaDeleteTarget] = useState<Faixa | null>(null);

    const faixaForm = useForm({
        nome: '',
        potencia_min: '0',
        potencia_max: '',
        margem: '0',
        ordem: '0',
    });

    function openCreateFaixa() {
        setFaixaEditTarget(null);
        faixaForm.reset();
        faixaForm.setData({ nome: '', potencia_min: '0', potencia_max: '', margem: '0', ordem: String(faixas.length) });
        setFaixaModalOpen(true);
    }

    function openEditFaixa(f: Faixa) {
        setFaixaEditTarget(f);
        faixaForm.setData({
            nome: f.nome,
            potencia_min: f.potencia_min,
            potencia_max: f.potencia_max ?? '',
            margem: f.margem,
            ordem: String(f.ordem),
        });
        setFaixaModalOpen(true);
    }

    function handleFaixaSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (faixaEditTarget) {
            faixaForm.put(route('admin.precificacao.faixas.update', faixaEditTarget.id), {
                onSuccess: () => setFaixaModalOpen(false),
            });
        } else {
            faixaForm.post(route('admin.precificacao.faixas.store'), {
                onSuccess: () => setFaixaModalOpen(false),
            });
        }
    }

    function handleFaixaDelete() {
        if (!faixaDeleteTarget) return;
        router.delete(route('admin.precificacao.faixas.destroy', faixaDeleteTarget.id), {
            onSuccess: () => setFaixaDeleteTarget(null),
        });
    }

    // ── Por Estado ───────────────────────────────────────────────────────
    const [estadoEditTarget, setEstadoEditTarget] = useState<EstadoRow | null>(null);
    const estadoForm = useForm({ estado: '', margem: '0' });

    function openEditEstado(e: EstadoRow) {
        setEstadoEditTarget(e);
        estadoForm.setData({ estado: e.estado, margem: String(e.margem) });
    }

    function handleEstadoSubmit(e: React.FormEvent) {
        e.preventDefault();
        estadoForm.post(route('admin.precificacao.estados.update'), {
            onSuccess: () => setEstadoEditTarget(null),
        });
    }

    // ── Por Fornecedor ───────────────────────────────────────────────────
    const [fornecedorEditTarget, setFornecedorEditTarget] = useState<FornecedorRow | null>(null);
    const fornecedorForm = useForm({ fornecedor_id: 0, margem: '0' });

    function openEditFornecedor(f: FornecedorRow) {
        setFornecedorEditTarget(f);
        fornecedorForm.setData({ fornecedor_id: f.id, margem: String(f.margem) });
    }

    function handleFornecedorSubmit(e: React.FormEvent) {
        e.preventDefault();
        fornecedorForm.post(route('admin.precificacao.fornecedores.update'), {
            onSuccess: () => setFornecedorEditTarget(null),
        });
    }

    return (
        <AppLayout>
            <Head title="Precificação" />

            <PageHeader
                title="Precificação"
                subtitle="Preço de venda = preço de custo × (1 + Margem Principal + Margem por Estado + Margem por Fornecedor)"
                breadcrumbs={[{ label: 'Precificação' }]}
            />

            {/* ── Simulador ─────────────────────────────────────────────── */}
            <Card sx={{ p: 3, mb: 3 }}>
                <Stack direction="row" alignItems="center" gap={1} sx={{ mb: 2 }}>
                    <CalculateRoundedIcon color="primary" />
                    <Typography variant="h6" fontWeight={700}>Simulador de Preço</Typography>
                </Stack>
                <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
                    Preencha os campos abaixo para ver, em tempo real, como cada camada de margem impacta o preço final.
                </Typography>

                <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', sm: 'repeat(2, 1fr)', md: 'repeat(4, 1fr)' }, gap: 2, mb: 3 }}>
                    <TextField
                        label="Preço de Custo (R$)"
                        type="number"
                        value={simCusto}
                        onChange={(e) => setSimCusto(e.target.value)}
                        inputProps={{ step: '0.01', min: '0' }}
                        fullWidth
                    />
                    <TextField
                        label="Potência Total (kWp)"
                        type="number"
                        value={simPotencia}
                        onChange={(e) => setSimPotencia(e.target.value)}
                        inputProps={{ step: '0.01', min: '0' }}
                        fullWidth
                    />
                    <TextField
                        select
                        label="Estado"
                        value={simEstado}
                        onChange={(e) => setSimEstado(e.target.value)}
                        fullWidth
                    >
                        {estados.map((e) => (
                            <MenuItem key={e.estado} value={e.estado}>{e.estado} — {e.nome_estado}</MenuItem>
                        ))}
                    </TextField>
                    <TextField
                        select
                        label="Fornecedor"
                        value={simFornecedor}
                        onChange={(e) => setSimFornecedor(Number(e.target.value))}
                        fullWidth
                    >
                        {fornecedores.map((f) => (
                            <MenuItem key={f.id} value={f.id}>{f.nome}</MenuItem>
                        ))}
                    </TextField>
                </Box>

                <Card variant="outlined" sx={{ p: 2.5, bgcolor: 'action.hover' }}>
                    <Stack gap={1.25}>
                        <Stack direction="row" justifyContent="space-between">
                            <Typography variant="body2">Preço de custo</Typography>
                            <Typography variant="body2" fontWeight={600}>{fmtMoney(simulacao.custo)}</Typography>
                        </Stack>
                        <Stack direction="row" justifyContent="space-between">
                            <Typography variant="body2" color="text.secondary">
                                + Margem Principal ({simulacao.faixa ? faixaLabel(simulacao.faixa) : 'sem faixa'} · {fmtPct(simulacao.mPrincipal)})
                            </Typography>
                            <Typography variant="body2">{fmtMoney(simulacao.valorPrincipal)}</Typography>
                        </Stack>
                        <Stack direction="row" justifyContent="space-between">
                            <Typography variant="body2" color="text.secondary">
                                + Margem por Estado ({simulacao.estado?.nome_estado ?? '—'} · {fmtPct(simulacao.mEstado)})
                            </Typography>
                            <Typography variant="body2">{fmtMoney(simulacao.valorEstado)}</Typography>
                        </Stack>
                        <Stack direction="row" justifyContent="space-between">
                            <Typography variant="body2" color="text.secondary">
                                + Margem por Fornecedor ({simulacao.fornecedor?.nome ?? '—'} · {fmtPct(simulacao.mFornecedor)})
                            </Typography>
                            <Typography variant="body2">{fmtMoney(simulacao.valorFornecedor)}</Typography>
                        </Stack>
                        <Divider sx={{ my: 0.5 }} />
                        <Stack direction="row" justifyContent="space-between" alignItems="center">
                            <Typography variant="body2" color="text.secondary">Margem total aplicada</Typography>
                            <Chip label={fmtPct(simulacao.margemTotal)} size="small" color="primary" variant="outlined" />
                        </Stack>
                        <Stack direction="row" justifyContent="space-between" alignItems="center">
                            <Typography variant="subtitle1" fontWeight={700}>Preço de venda final</Typography>
                            <Typography variant="h6" fontWeight={700} color="success.main">{fmtMoney(simulacao.precoVenda)}</Typography>
                        </Stack>
                    </Stack>
                </Card>
            </Card>

            {/* ── Configuração das camadas de margem ───────────────────────── */}
            <Card>
                <Tabs value={tab} onChange={(_, v) => setTab(v)} sx={{ px: 2, borderBottom: 1, borderColor: 'divider' }}>
                    <Tab label="Margem Principal" />
                    <Tab label="Por Estado" />
                    <Tab label="Por Fornecedor" />
                </Tabs>

                {tab === 0 && (
                    <Box sx={{ p: 2 }}>
                        <Stack direction="row" justifyContent="flex-end" sx={{ mb: 2 }}>
                            <Button variant="contained" startIcon={<AddRoundedIcon />} onClick={openCreateFaixa}>
                                Nova Faixa
                            </Button>
                        </Stack>
                        <Table>
                            <TableHead>
                                <TableRow>
                                    <TableCell>Ordem</TableCell>
                                    <TableCell>Nome</TableCell>
                                    <TableCell>Faixa de Potência</TableCell>
                                    <TableCell align="right">Margem</TableCell>
                                    <TableCell align="right">Ações</TableCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {faixas.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={5} align="center" sx={{ py: 4 }}>
                                            <Typography color="text.secondary">Nenhuma faixa cadastrada</Typography>
                                        </TableCell>
                                    </TableRow>
                                )}
                                {faixas.map((f) => (
                                    <TableRow key={f.id} hover>
                                        <TableCell><Chip label={f.ordem} size="small" variant="outlined" /></TableCell>
                                        <TableCell><Typography variant="body2" fontWeight={600}>{f.nome}</Typography></TableCell>
                                        <TableCell><Typography variant="body2" color="text.secondary">{faixaLabel(f)}</Typography></TableCell>
                                        <TableCell align="right">
                                            <Chip label={fmtPct(parseFloat(f.margem))} size="small" color="primary" variant="outlined" />
                                        </TableCell>
                                        <TableCell align="right">
                                            <Tooltip title="Editar">
                                                <IconButton size="small" onClick={() => openEditFaixa(f)}>
                                                    <EditRoundedIcon fontSize="small" />
                                                </IconButton>
                                            </Tooltip>
                                            <Tooltip title="Excluir">
                                                <IconButton size="small" color="error" onClick={() => setFaixaDeleteTarget(f)}>
                                                    <DeleteRoundedIcon fontSize="small" />
                                                </IconButton>
                                            </Tooltip>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </Box>
                )}

                {tab === 1 && (
                    <Box sx={{ p: 2 }}>
                        <Table>
                            <TableHead>
                                <TableRow>
                                    <TableCell>UF</TableCell>
                                    <TableCell>Estado</TableCell>
                                    <TableCell align="right">Margem Adicional</TableCell>
                                    <TableCell align="right">Ações</TableCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {estados.map((e) => (
                                    <TableRow key={e.estado} hover>
                                        <TableCell><Chip label={e.estado} size="small" variant="outlined" /></TableCell>
                                        <TableCell><Typography variant="body2">{e.nome_estado}</Typography></TableCell>
                                        <TableCell align="right">
                                            <Chip
                                                label={`${e.margem.toFixed(3)}%`}
                                                size="small"
                                                color={e.margem > 0 ? 'primary' : 'default'}
                                                variant={e.margem > 0 ? 'filled' : 'outlined'}
                                            />
                                        </TableCell>
                                        <TableCell align="right">
                                            <Tooltip title="Editar margem">
                                                <IconButton size="small" onClick={() => openEditEstado(e)}>
                                                    <EditRoundedIcon fontSize="small" />
                                                </IconButton>
                                            </Tooltip>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </Box>
                )}

                {tab === 2 && (
                    <Box sx={{ p: 2 }}>
                        <Table>
                            <TableHead>
                                <TableRow>
                                    <TableCell>Fornecedor</TableCell>
                                    <TableCell>Status</TableCell>
                                    <TableCell align="right">Margem Adicional</TableCell>
                                    <TableCell align="right">Ações</TableCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {fornecedores.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={4} align="center" sx={{ py: 4 }}>
                                            <Typography color="text.secondary">Nenhum fornecedor cadastrado</Typography>
                                        </TableCell>
                                    </TableRow>
                                )}
                                {fornecedores.map((f) => (
                                    <TableRow key={f.id} hover>
                                        <TableCell><Typography variant="body2" fontWeight={600}>{f.nome}</Typography></TableCell>
                                        <TableCell>
                                            <Chip
                                                label={f.ativo ? 'Ativo' : 'Inativo'}
                                                size="small"
                                                color={f.ativo ? 'success' : 'default'}
                                                variant={f.ativo ? 'filled' : 'outlined'}
                                            />
                                        </TableCell>
                                        <TableCell align="right">
                                            <Chip
                                                label={`${f.margem.toFixed(3)}%`}
                                                size="small"
                                                color={f.margem > 0 ? 'primary' : 'default'}
                                                variant={f.margem > 0 ? 'filled' : 'outlined'}
                                            />
                                        </TableCell>
                                        <TableCell align="right">
                                            <Tooltip title="Editar margem">
                                                <IconButton size="small" onClick={() => openEditFornecedor(f)}>
                                                    <EditRoundedIcon fontSize="small" />
                                                </IconButton>
                                            </Tooltip>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </Box>
                )}
            </Card>

            {/* ── Modais ────────────────────────────────────────────────────── */}
            <Dialog open={faixaModalOpen} onClose={() => setFaixaModalOpen(false)} maxWidth="sm" fullWidth>
                <form onSubmit={handleFaixaSubmit}>
                    <DialogTitle>{faixaEditTarget ? 'Editar Faixa' : 'Nova Faixa de Margem'}</DialogTitle>
                    <DialogContent sx={{ pt: '16px !important', display: 'flex', flexDirection: 'column', gap: 2 }}>
                        <TextField
                            label="Nome *"
                            placeholder="Ex: Residencial Pequeno"
                            value={faixaForm.data.nome}
                            onChange={(e) => faixaForm.setData('nome', e.target.value)}
                            error={!!faixaForm.errors.nome}
                            helperText={faixaForm.errors.nome}
                            fullWidth
                            autoFocus
                        />
                        <Box sx={{ display: 'flex', gap: 2 }}>
                            <TextField
                                label="Potência Mínima (kWp) *"
                                type="number"
                                value={faixaForm.data.potencia_min}
                                onChange={(e) => faixaForm.setData('potencia_min', e.target.value)}
                                error={!!faixaForm.errors.potencia_min}
                                helperText={faixaForm.errors.potencia_min}
                                inputProps={{ step: '0.001', min: '0' }}
                                fullWidth
                            />
                            <TextField
                                label="Potência Máxima (kWp)"
                                type="number"
                                value={faixaForm.data.potencia_max}
                                onChange={(e) => faixaForm.setData('potencia_max', e.target.value)}
                                error={!!faixaForm.errors.potencia_max}
                                helperText={faixaForm.errors.potencia_max ?? 'Deixe em branco para sem limite'}
                                inputProps={{ step: '0.001', min: '0' }}
                                fullWidth
                            />
                        </Box>
                        <Box sx={{ display: 'flex', gap: 2 }}>
                            <TextField
                                label="Margem (%) *"
                                type="number"
                                value={faixaForm.data.margem}
                                onChange={(e) => faixaForm.setData('margem', e.target.value)}
                                error={!!faixaForm.errors.margem}
                                helperText={faixaForm.errors.margem}
                                inputProps={{ step: '0.001', min: '0', max: '100' }}
                                fullWidth
                            />
                            <TextField
                                label="Ordem *"
                                type="number"
                                value={faixaForm.data.ordem}
                                onChange={(e) => faixaForm.setData('ordem', e.target.value)}
                                error={!!faixaForm.errors.ordem}
                                helperText={faixaForm.errors.ordem}
                                inputProps={{ min: '0' }}
                                fullWidth
                            />
                        </Box>
                    </DialogContent>
                    <DialogActions sx={{ px: 3, pb: 2 }}>
                        <Button onClick={() => setFaixaModalOpen(false)}>Cancelar</Button>
                        <Button type="submit" variant="contained" disabled={faixaForm.processing}>
                            {faixaEditTarget ? 'Salvar' : 'Criar'}
                        </Button>
                    </DialogActions>
                </form>
            </Dialog>

            <ConfirmDialog
                open={!!faixaDeleteTarget}
                title="Excluir Faixa"
                message={`Deseja excluir a faixa "${faixaDeleteTarget?.nome}"? Esta ação não pode ser desfeita.`}
                confirmLabel="Excluir"
                onConfirm={handleFaixaDelete}
                onCancel={() => setFaixaDeleteTarget(null)}
            />

            <Dialog open={!!estadoEditTarget} onClose={() => setEstadoEditTarget(null)} maxWidth="xs" fullWidth>
                <form onSubmit={handleEstadoSubmit}>
                    <DialogTitle>Editar Margem — {estadoEditTarget?.nome_estado}</DialogTitle>
                    <DialogContent sx={{ pt: '16px !important' }}>
                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
                            <Chip label={estadoEditTarget?.estado} variant="outlined" />
                            <TextField
                                label="Margem Adicional (%)"
                                type="number"
                                value={estadoForm.data.margem}
                                onChange={(e) => estadoForm.setData('margem', e.target.value)}
                                error={!!estadoForm.errors.margem}
                                helperText={estadoForm.errors.margem ?? 'Use 0 para sem acréscimo'}
                                inputProps={{ step: '0.001', min: '0', max: '100' }}
                                fullWidth
                                autoFocus
                            />
                        </Box>
                    </DialogContent>
                    <DialogActions sx={{ px: 3, pb: 2 }}>
                        <Button onClick={() => setEstadoEditTarget(null)}>Cancelar</Button>
                        <Button type="submit" variant="contained" disabled={estadoForm.processing}>Salvar</Button>
                    </DialogActions>
                </form>
            </Dialog>

            <Dialog open={!!fornecedorEditTarget} onClose={() => setFornecedorEditTarget(null)} maxWidth="xs" fullWidth>
                <form onSubmit={handleFornecedorSubmit}>
                    <DialogTitle>Editar Margem — {fornecedorEditTarget?.nome}</DialogTitle>
                    <DialogContent sx={{ pt: '16px !important' }}>
                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
                            <TextField
                                label="Margem Adicional (%)"
                                type="number"
                                value={fornecedorForm.data.margem}
                                onChange={(e) => fornecedorForm.setData('margem', e.target.value)}
                                error={!!fornecedorForm.errors.margem}
                                helperText={fornecedorForm.errors.margem ?? 'Use 0 para sem acréscimo'}
                                inputProps={{ step: '0.001', min: '0', max: '100' }}
                                fullWidth
                                autoFocus
                            />
                        </Box>
                    </DialogContent>
                    <DialogActions sx={{ px: 3, pb: 2 }}>
                        <Button onClick={() => setFornecedorEditTarget(null)}>Cancelar</Button>
                        <Button type="submit" variant="contained" disabled={fornecedorForm.processing}>Salvar</Button>
                    </DialogActions>
                </form>
            </Dialog>
        </AppLayout>
    );
}
