import React, { useState } from 'react';
import {
    Alert, Autocomplete, Box, Button, Card, CardContent, CardHeader, Chip, CircularProgress,
    Dialog, DialogActions, DialogContent, DialogTitle, Divider, Grid,
    IconButton, InputAdornment, Table, TableBody, TableCell, TableHead, TableRow,
    TextField, Tooltip, Typography, alpha,
} from '@mui/material';
import ArrowBackRoundedIcon from '@mui/icons-material/ArrowBackRounded';
import EditRoundedIcon from '@mui/icons-material/EditRounded';
import ElectricBoltRoundedIcon from '@mui/icons-material/ElectricBoltRounded';
import AttachMoneyRoundedIcon from '@mui/icons-material/AttachMoneyRounded';
import AddRoundedIcon from '@mui/icons-material/AddRounded';
import DeleteRoundedIcon from '@mui/icons-material/DeleteRounded';
import CheckCircleRoundedIcon from '@mui/icons-material/CheckCircleRounded';
import PictureAsPdfRoundedIcon from '@mui/icons-material/PictureAsPdfRounded';
import ArticleRoundedIcon from '@mui/icons-material/ArticleRounded';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { OrcamentoStatusChip } from '@/Components/UI/StatusChip';
import { ConfirmDialog } from '@/Components/UI/ConfirmDialog';
import { PageProps, OrcamentoStatus } from '@/types';
import { ROTULO_EVENTO } from '@/Components/Funil/eventoHistorico';

interface ProdutoBusca {
    id: number; nome: string; modelo?: string; sku?: string;
    preco_custo: number; unidade: string;
    categoria?: { nome: string; slug: string };
    marca?: { nome: string };
}

interface OrcamentoItem {
    id: number; tipo: string; descricao?: string; quantidade: number;
    preco_venda_unitario: number; preco_venda_total: number; geracao_estimada?: number; ordem: number;
}
interface Historico {
    id: number; tipo: string; status?: string | null; mensagem?: string; created_at: string;
    usuario?: { id: number; name: string };
}
interface OrcamentoFull {
    id: number; status: OrcamentoStatus; preco_total: number; geracao_estimada: number;
    perdido_em?: string | null;
    anotacoes?: string; token: string; created_at: string;
    cliente?: { id: number; nome?: string; razao_social?: string; tipo_pessoa: string; email?: string; celular?: string; telefone?: string };
    cidade?: { id: number; cidade: string; estado: string };
    info?: { tipo_dimensionamento?: string; consumo?: number; tensao?: number; orientacao?: string; anotacoes_tecnicas?: string; estrutura?: { id: number; nome: string } };
    itens: OrcamentoItem[];
    historicos: Historico[];
    contrato?: { id: number; status: string } | null;
}

interface Props extends PageProps { orcamento: OrcamentoFull }

const orientacaoLabel: Record<string, string> = {
    norte: 'Norte', nordeste_noroeste: 'Nordeste / Noroeste',
    leste_oeste: 'Leste / Oeste', sudeste_sudoeste: 'Sudeste / Sudoeste', sul: 'Sul',
};

function InfoRow({ label, value }: { label: string; value?: React.ReactNode }) {
    return (
        <Box sx={{ py: 1.25, borderBottom: '1px solid', borderColor: 'divider', display: 'flex', gap: 2, '&:last-child': { border: 'none' } }}>
            <Typography variant="body2" color="text.secondary" sx={{ minWidth: 160, fontWeight: 500 }}>{label}</Typography>
            <Typography variant="body2">{value ?? '—'}</Typography>
        </Box>
    );
}

// Dialog de adição de item
function AdicionarItemDialog({
    open, onClose, orcamentoId,
}: { open: boolean; onClose: () => void; orcamentoId: number }) {
    const [buscando, setBuscando] = useState(false);
    const [opcoes, setOpcoes] = useState<ProdutoBusca[]>([]);
    const [produtoSelecionado, setProdutoSelecionado] = useState<ProdutoBusca | null>(null);

    const { data, setData, post, processing, reset, errors } = useForm({
        tipo: 'produto',
        produto_id: '' as string | number,
        descricao: '',
        quantidade: 1,
        preco_venda_unitario: 0,
    });

    const buscarProdutos = async (q: string) => {
        if (!q || q.length < 2) return;
        setBuscando(true);
        try {
            const res = await fetch(route('consultor.orcamentos.produtos.buscar') + `?q=${encodeURIComponent(q)}`);
            const json = await res.json();
            setOpcoes(json);
        } catch {
            setOpcoes([]);
        } finally {
            setBuscando(false);
        }
    };

    const handleProdutoChange = (_: unknown, prod: ProdutoBusca | null) => {
        setProdutoSelecionado(prod);
        if (prod) {
            setData('produto_id', prod.id);
            setData('descricao', prod.nome);
            setData('preco_venda_unitario', prod.preco_custo * 1.3); // sugestão 30% margem
        } else {
            setData('produto_id', '');
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('consultor.orcamentos.itens.store', orcamentoId), {
            onSuccess: () => { reset(); setProdutoSelecionado(null); onClose(); },
        });
    };

    return (
        <Dialog open={open} onClose={onClose} maxWidth="sm" fullWidth>
            <DialogTitle>Adicionar Item ao Orçamento</DialogTitle>
            <Divider />
            <Box component="form" onSubmit={handleSubmit} noValidate>
                <DialogContent sx={{ display: 'flex', flexDirection: 'column', gap: 2, pt: 2.5 }}>
                    <Autocomplete
                        options={opcoes}
                        getOptionLabel={(p) => `${p.nome}${p.modelo ? ` — ${p.modelo}` : ''}${p.sku ? ` (${p.sku})` : ''}`}
                        filterOptions={(x) => x}
                        onInputChange={(_, v) => buscarProdutos(v)}
                        onChange={handleProdutoChange}
                        loading={buscando}
                        renderOption={(props, p) => (
                            <Box component="li" {...props} key={p.id}>
                                <Box>
                                    <Typography variant="body2" fontWeight={500}>{p.nome}</Typography>
                                    <Typography variant="caption" color="text.secondary">
                                        {p.categoria?.nome} {p.modelo ? `· ${p.modelo}` : ''} {p.sku ? `· SKU: ${p.sku}` : ''}
                                    </Typography>
                                </Box>
                            </Box>
                        )}
                        renderInput={(params) => (
                            <TextField
                                {...params}
                                label="Buscar produto no catálogo"
                                size="small"
                                InputProps={{
                                    ...params.InputProps,
                                    endAdornment: buscando ? <CircularProgress size={16} /> : params.InputProps.endAdornment,
                                }}
                                helperText="Digite para buscar por nome, modelo ou SKU"
                            />
                        )}
                    />

                    <TextField
                        label="Descrição do item *"
                        size="small"
                        value={data.descricao}
                        onChange={(e) => setData('descricao', e.target.value)}
                        error={Boolean(errors.descricao)}
                        helperText={errors.descricao || (produtoSelecionado ? undefined : 'Para item personalizado, descreva manualmente')}
                    />

                    <Grid container spacing={3}>
                        <Grid size={{ xs: 6 }}>
                            <TextField
                                label="Quantidade *"
                                type="number"
                                size="small"
                                fullWidth
                                value={data.quantidade}
                                onChange={(e) => setData('quantidade', Number(e.target.value))}
                                inputProps={{ min: 1 }}
                                error={Boolean(errors.quantidade)}
                                helperText={errors.quantidade}
                            />
                        </Grid>
                        <Grid size={{ xs: 6 }}>
                            <TextField
                                label="Preço de Venda Unitário *"
                                type="number"
                                size="small"
                                fullWidth
                                value={data.preco_venda_unitario}
                                onChange={(e) => setData('preco_venda_unitario', Number(e.target.value))}
                                InputProps={{ startAdornment: <InputAdornment position="start">R$</InputAdornment> }}
                                inputProps={{ step: 0.01, min: 0 }}
                                error={Boolean(errors.preco_venda_unitario)}
                                helperText={errors.preco_venda_unitario}
                            />
                        </Grid>
                    </Grid>

                    {produtoSelecionado && (
                        <Box sx={{ p: 1.5, bgcolor: 'grey.50', borderRadius: 1.5, border: '1px solid', borderColor: 'divider' }}>
                            <Typography variant="caption" color="text.secondary">
                                Custo: R$ {produtoSelecionado.preco_custo.toLocaleString('pt-BR', { minimumFractionDigits: 2 })} ·
                                Categoria: {produtoSelecionado.categoria?.nome} ·
                                Unidade: {produtoSelecionado.unidade}
                            </Typography>
                        </Box>
                    )}
                </DialogContent>
                <Divider />
                <DialogActions sx={{ px: 3, py: 2 }}>
                    <Button onClick={onClose} variant="outlined">Cancelar</Button>
                    <Button type="submit" variant="contained" disabled={processing || !data.descricao}>
                        {processing ? <CircularProgress size={18} sx={{ color: 'white' }} /> : 'Adicionar'}
                    </Button>
                </DialogActions>
            </Box>
        </Dialog>
    );
}

export default function OrcamentosShow({ orcamento, flash }: Props) {
    const nome = orcamento.cliente?.tipo_pessoa === 'pj' ? orcamento.cliente?.razao_social : orcamento.cliente?.nome;

    const [confirmDelete, setConfirmDelete] = useState(false);
    const [deleteItemId, setDeleteItemId] = useState<number | null>(null);
    const [addItemOpen, setAddItemOpen] = useState(false);

    const { put, delete: destroy, processing } = useForm({});

    const canEdit = orcamento.status === 'novo';
    const fmtMoney = (v: number) => v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

    return (
        <AppLayout>
            <Head title={`Orçamento #${orcamento.id}`} />

            <PageHeader
                title={`Orçamento #${orcamento.id}`}
                subtitle={nome ?? 'Cliente não informado'}
                breadcrumbs={[
                    { label: 'Orçamentos', href: route('consultor.orcamentos.index') },
                    { label: `#${orcamento.id}` },
                ]}
                action={
                    <Box sx={{ display: 'flex', gap: 1 }}>
                        <Button component={Link} href={route('consultor.orcamentos.index')} startIcon={<ArrowBackRoundedIcon />} variant="outlined">
                            Voltar
                        </Button>
                        {canEdit && (
                            <Button
                                component={Link}
                                href={route('consultor.orcamentos.edit', orcamento.id)}
                                startIcon={<EditRoundedIcon />}
                                variant="outlined"
                                color="primary"
                            >
                                Editar
                            </Button>
                        )}
                        <Button
                            component="a"
                            href={route('consultor.orcamentos.pdf', orcamento.id)}
                            target="_blank"
                            rel="noopener"
                            startIcon={<PictureAsPdfRoundedIcon />}
                            variant="outlined"
                            color="error"
                        >
                            PDF
                        </Button>
                    </Box>
                }
            />

            {flash?.success && <Alert severity="success" sx={{ mb: 3 }}>{flash.success}</Alert>}
            {flash?.error && <Alert severity="error" sx={{ mb: 3 }}>{flash.error}</Alert>}

            {/* KPIs */}
            <Grid container spacing={2} sx={{ mb: 3 }}>
                {[
                    { label: 'Valor Total', value: fmtMoney(orcamento.preco_total), icon: <AttachMoneyRoundedIcon />, color: '#22c55e' },
                    { label: 'Geração Estimada', value: `${orcamento.geracao_estimada ?? 0} kWh/mês`, icon: <ElectricBoltRoundedIcon />, color: '#f59e0b' },
                    { label: 'Itens', value: `${orcamento.itens.length} ${orcamento.itens.length === 1 ? 'item' : 'itens'}`, icon: <AddRoundedIcon />, color: '#6366f1' },
                ].map((d) => (
                    <Grid key={d.label} size={{ xs: 12, sm: 4 }}>
                        <Card variant="outlined">
                            <CardContent sx={{ p: '20px !important' }}>
                                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                                    <Box sx={{ width: 44, height: 44, borderRadius: 2, bgcolor: alpha(d.color, 0.12), display: 'flex', alignItems: 'center', justifyContent: 'center', color: d.color }}>
                                        {d.icon}
                                    </Box>
                                    <Box>
                                        <Typography variant="h6" fontWeight={700}>{d.value}</Typography>
                                        <Typography variant="caption" color="text.secondary">{d.label}</Typography>
                                    </Box>
                                </Box>
                            </CardContent>
                        </Card>
                    </Grid>
                ))}
            </Grid>

            <Grid container spacing={3}>
                {/* Main */}
                <Grid size={{ xs: 12, md: 8 }}>
                    {/* Dados do cliente */}
                    <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                        <CardHeader
                            title="Dados do Orçamento"
                            titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }}
                            action={<OrcamentoStatusChip status={orcamento.status} />}
                        />
                        <Divider />
                        <CardContent>
                            <Grid container spacing={0}>
                                <Grid size={{ xs: 12, sm: 6 }}>
                                    <InfoRow label="Cliente" value={nome} />
                                    <InfoRow label="E-mail" value={orcamento.cliente?.email} />
                                    <InfoRow label="Telefone" value={orcamento.cliente?.celular ?? orcamento.cliente?.telefone} />
                                    <InfoRow label="Cidade" value={orcamento.cidade ? `${orcamento.cidade.cidade} — ${orcamento.cidade.estado}` : undefined} />
                                </Grid>
                                <Grid size={{ xs: 12, sm: 6 }}>
                                    <InfoRow label="Tipo de Sistema" value={orcamento.info?.tipo_dimensionamento === 'convencional' ? 'Convencional' : 'Por Demanda'} />
                                    <InfoRow label="Estrutura" value={orcamento.info?.estrutura?.nome} />
                                    <InfoRow label="Orientação" value={orcamento.info?.orientacao ? orientacaoLabel[orcamento.info.orientacao] : undefined} />
                                    <InfoRow label="Tensão" value={orcamento.info?.tensao ? `${orcamento.info.tensao}V` : undefined} />
                                </Grid>
                            </Grid>
                        </CardContent>
                    </Card>

                    {/* Itens */}
                    <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                        <CardHeader
                            title="Itens do Orçamento"
                            titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }}
                            action={
                                canEdit && (
                                    <Button
                                        size="small"
                                        startIcon={<AddRoundedIcon />}
                                        onClick={() => setAddItemOpen(true)}
                                    >
                                        Adicionar Item
                                    </Button>
                                )
                            }
                        />
                        <Divider />
                        <Table size="small">
                            <TableHead sx={{ bgcolor: '#F8FAFC' }}>
                                <TableRow>
                                    <TableCell sx={{ fontWeight: 600 }}>Descrição</TableCell>
                                    <TableCell align="center" sx={{ fontWeight: 600 }}>Qtd</TableCell>
                                    <TableCell align="right" sx={{ fontWeight: 600 }}>Unit.</TableCell>
                                    <TableCell align="right" sx={{ fontWeight: 600 }}>Total</TableCell>
                                    {canEdit && <TableCell sx={{ width: 40 }} />}
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {orcamento.itens.map((item) => (
                                    <TableRow key={item.id} hover>
                                        <TableCell>
                                            <Box>
                                                <Typography variant="body2" fontWeight={item.tipo === 'kit' ? 600 : 400}>
                                                    {item.descricao ?? item.tipo}
                                                </Typography>
                                                <Box sx={{ display: 'flex', gap: 0.5, mt: 0.3 }}>
                                                    <Chip label={item.tipo} size="small" sx={{ fontSize: '0.68rem', height: 16 }} />
                                                    {item.geracao_estimada && (
                                                        <Chip
                                                            icon={<ElectricBoltRoundedIcon sx={{ fontSize: '0.75rem !important' }} />}
                                                            label={`${item.geracao_estimada} kWh/mês`}
                                                            size="small"
                                                            color="warning"
                                                            variant="outlined"
                                                            sx={{ fontSize: '0.68rem', height: 16 }}
                                                        />
                                                    )}
                                                </Box>
                                            </Box>
                                        </TableCell>
                                        <TableCell align="center">{item.quantidade}</TableCell>
                                        <TableCell align="right">{fmtMoney(item.preco_venda_unitario)}</TableCell>
                                        <TableCell align="right">
                                            <Typography variant="body2" fontWeight={600}>{fmtMoney(item.preco_venda_total)}</Typography>
                                        </TableCell>
                                        {canEdit && (
                                            <TableCell align="right">
                                                {item.tipo !== 'kit' && (
                                                    <Tooltip title="Remover item">
                                                        <IconButton size="small" color="error" onClick={() => setDeleteItemId(item.id)}>
                                                            <DeleteRoundedIcon fontSize="small" />
                                                        </IconButton>
                                                    </Tooltip>
                                                )}
                                            </TableCell>
                                        )}
                                    </TableRow>
                                ))}
                                <TableRow sx={{ bgcolor: '#F8FAFC' }}>
                                    <TableCell colSpan={canEdit ? 3 : 3} align="right">
                                        <Typography variant="body2" fontWeight={700} color="text.secondary">TOTAL DO ORÇAMENTO</Typography>
                                    </TableCell>
                                    <TableCell align="right">
                                        <Typography variant="subtitle1" fontWeight={800} color="success.main">
                                            {fmtMoney(orcamento.preco_total)}
                                        </Typography>
                                    </TableCell>
                                    {canEdit && <TableCell />}
                                </TableRow>
                            </TableBody>
                        </Table>
                    </Card>

                    {/* Anotações da proposta */}
                    {orcamento.anotacoes && (
                        <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                            <CardHeader
                                title="Anotações da Proposta"
                                titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }}
                            />
                            <Divider />
                            <CardContent>
                                <Typography variant="body2" sx={{ whiteSpace: 'pre-wrap' }}>
                                    {orcamento.anotacoes}
                                </Typography>
                            </CardContent>
                        </Card>
                    )}

                    {/* Histórico */}
                    {orcamento.historicos.length > 0 && (
                        <Card variant="outlined" sx={{ borderRadius: 2 }}>
                            <CardHeader title="Histórico" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                            <Divider />
                            <CardContent>
                                {orcamento.historicos.map((h) => (
                                    <Box key={h.id} sx={{ display: 'flex', gap: 2, mb: 1.5, pb: 1.5, borderBottom: '1px solid', borderColor: 'divider', '&:last-child': { border: 'none', mb: 0, pb: 0 } }}>
                                        <Box sx={{ width: 8, height: 8, borderRadius: '50%', bgcolor: 'primary.main', mt: 1.2, flexShrink: 0 }} />
                                        <Box sx={{ flexGrow: 1 }}>
                                            <Typography variant="body2">{h.mensagem ?? `Status: ${h.status}`}</Typography>
                                            <Typography variant="caption" color="text.secondary">
                                                {h.usuario?.name ?? 'Sistema'} · {new Date(h.created_at).toLocaleString('pt-BR')}
                                            </Typography>
                                        </Box>
                                        <Chip label={h.status ?? ROTULO_EVENTO[h.tipo] ?? h.tipo} size="small" variant="outlined" />
                                    </Box>
                                ))}
                            </CardContent>
                        </Card>
                    )}
                </Grid>

                {/* Sidebar */}
                <Grid size={{ xs: 12, md: 4 }}>
                    {canEdit && (
                        <Card variant="outlined" sx={{ mb: 2, borderRadius: 2 }}>
                            <CardHeader title="Ações" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                            <Divider />
                            <CardContent sx={{ display: 'flex', flexDirection: 'column', gap: 1.5 }}>
                                {orcamento.perdido_em ? (
                                    <Alert severity="warning" action={<Button size="small" color="inherit" href={route('consultor.funil.index')}>Funil</Button>}>
                                        Venda marcada como perdida. Reative no funil para retomar a negociação.
                                    </Alert>
                                ) : (
                                    <Button
                                        variant="contained"
                                        color="success"
                                        fullWidth
                                        startIcon={<CheckCircleRoundedIcon />}
                                        onClick={() => put(route('consultor.orcamentos.update', orcamento.id), { data: { status: 'aprovando' } } as any)}
                                        disabled={processing}
                                    >
                                        Solicitar Aprovação
                                    </Button>
                                )}
                                <Button
                                    variant="outlined"
                                    color="error"
                                    fullWidth
                                    startIcon={<DeleteRoundedIcon />}
                                    onClick={() => setConfirmDelete(true)}
                                    disabled={processing}
                                >
                                    Excluir Orçamento
                                </Button>
                            </CardContent>
                        </Card>
                    )}

                    {orcamento.status === 'aprovado' && (
                        <Card variant="outlined" sx={{ mb: 2, borderRadius: 2 }}>
                            <CardHeader title="Contrato" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                            <Divider />
                            <CardContent>
                                {orcamento.contrato ? (
                                    <Button
                                        component={Link}
                                        href={route('consultor.contratos.show', orcamento.contrato.id)}
                                        startIcon={<ArticleRoundedIcon />}
                                        variant="outlined"
                                        fullWidth
                                    >
                                        Ver Contrato #{orcamento.contrato.id}
                                    </Button>
                                ) : (
                                    <Button
                                        component={Link}
                                        href={route('consultor.contratos.create', orcamento.id)}
                                        startIcon={<ArticleRoundedIcon />}
                                        variant="contained"
                                        fullWidth
                                    >
                                        Gerar Contrato
                                    </Button>
                                )}
                            </CardContent>
                        </Card>
                    )}

                    {orcamento.info && (
                        <Card variant="outlined" sx={{ borderRadius: 2 }}>
                            <CardHeader title="Dados Técnicos" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                            <Divider />
                            <CardContent>
                                <InfoRow label="Consumo" value={orcamento.info.consumo ? `${orcamento.info.consumo} kWh/mês` : undefined} />
                                <InfoRow label="Tensão" value={orcamento.info.tensao ? `${orcamento.info.tensao}V` : undefined} />
                                <InfoRow label="Estrutura" value={orcamento.info.estrutura?.nome} />
                                <InfoRow label="Orientação" value={orcamento.info.orientacao ? orientacaoLabel[orcamento.info.orientacao] : undefined} />
                                {orcamento.info.anotacoes_tecnicas && (
                                    <Box sx={{ mt: 1.5, pt: 1.5, borderTop: '1px solid', borderColor: 'divider' }}>
                                        <Typography variant="caption" color="text.secondary" fontWeight={500} sx={{ display: 'block', mb: 0.5 }}>
                                            Notas Técnicas
                                        </Typography>
                                        <Typography variant="body2" sx={{ whiteSpace: 'pre-wrap' }}>
                                            {orcamento.info.anotacoes_tecnicas}
                                        </Typography>
                                    </Box>
                                )}
                            </CardContent>
                        </Card>
                    )}
                </Grid>
            </Grid>

            {/* Dialogs */}
            <AdicionarItemDialog
                open={addItemOpen}
                onClose={() => setAddItemOpen(false)}
                orcamentoId={orcamento.id}
            />

            <ConfirmDialog
                open={confirmDelete}
                title="Excluir Orçamento"
                message="Tem certeza que deseja excluir este orçamento? Esta ação não pode ser desfeita."
                confirmLabel="Excluir"
                onConfirm={() => destroy(route('consultor.orcamentos.destroy', orcamento.id))}
                onCancel={() => setConfirmDelete(false)}
            />

            <ConfirmDialog
                open={Boolean(deleteItemId)}
                title="Remover Item"
                message="Deseja remover este item do orçamento?"
                confirmLabel="Remover"
                onConfirm={() => {
                    if (deleteItemId) {
                        router.delete(route('consultor.orcamentos.itens.destroy', { orcamento: orcamento.id, item: deleteItemId }));
                        setDeleteItemId(null);
                    }
                }}
                onCancel={() => setDeleteItemId(null)}
            />
        </AppLayout>
    );
}
