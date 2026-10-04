import React from 'react';
import {
    Box,
    Button,
    Card,
    CardContent,
    CardHeader,
    Chip,
    Divider,
    Grid,
    MenuItem,
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableRow,
    TextField,
    Typography,
    alpha,
} from '@mui/material';
import ArrowBackRoundedIcon from '@mui/icons-material/ArrowBackRounded';
import SaveRoundedIcon from '@mui/icons-material/SaveRounded';
import ElectricBoltRoundedIcon from '@mui/icons-material/ElectricBoltRounded';
import AttachMoneyRoundedIcon from '@mui/icons-material/AttachMoneyRounded';
import BoltRoundedIcon from '@mui/icons-material/BoltRounded';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { OrcamentoStatusChip } from '@/Components/UI/StatusChip';
import { PageProps, OrcamentoStatus } from '@/types';
import { ROTULO_EVENTO } from '@/Components/Funil/eventoHistorico';

interface OrcamentoInfo {
    tipo_sistema?: string;
    tipo_ligacao?: string;
    consumo_mensal?: number;
    tarifa?: number;
    estrutura?: string;
    fornecedor?: string;
}

interface OrcamentoItem {
    id: number;
    descricao: string;
    quantidade: number;
    valor_unitario: number;
    valor_total: number;
    ordem: number;
}

interface Historico {
    id: number;
    tipo: string;
    status: OrcamentoStatus | null;
    mensagem?: string;
    created_at: string;
    usuario?: { id: number; name: string };
}

interface OrcamentoFull {
    id: number;
    status: OrcamentoStatus;
    preco_total: number;
    geracao_estimada: number;
    anotacoes?: string;
    token: string;
    created_at: string;
    updated_at: string;
    consultor?: { id: number; name: string; email?: string };
    cliente?: {
        id: number;
        nome?: string;
        razao_social?: string;
        tipo_pessoa: string;
        email?: string;
        celular?: string;
    };
    cidade?: { id: number; cidade: string; estado: string };
    info?: OrcamentoInfo;
    itens: OrcamentoItem[];
    historicos: Historico[];
}

interface Props extends PageProps { orcamento: OrcamentoFull; transicoes: OrcamentoStatus[] }

const STATUS_LABEL: Record<OrcamentoStatus, string> = {
    novo: 'Novo',
    aprovando: 'Para Aprovação',
    aprovado: 'Aprovado',
    aprovacao_reprovada: 'Reprovado',
    instalando: 'Em Instalação',
    finalizado: 'Finalizado',
};

function InfoRow({ label, value }: { label: string; value?: React.ReactNode }) {
    return (
        <Box sx={{ py: 1.25, borderBottom: '1px solid', borderColor: 'divider', display: 'flex', gap: 2, '&:last-child': { border: 'none' } }}>
            <Typography variant="body2" color="text.secondary" sx={{ minWidth: 160, fontWeight: 500 }}>{label}</Typography>
            <Typography variant="body2">{value ?? '—'}</Typography>
        </Box>
    );
}

export default function OrcamentosShow({ orcamento, transicoes }: Props) {
    const nome = orcamento.cliente?.tipo_pessoa === 'pj'
        ? orcamento.cliente?.razao_social
        : orcamento.cliente?.nome;

    const { data, setData, put, processing, errors } = useForm({
        status: orcamento.status,
        anotacoes: orcamento.anotacoes ?? '',
    });

    function handleUpdate(e: React.FormEvent) {
        e.preventDefault();
        put(route('admin.orcamentos.update', orcamento.id));
    }

    const fmtMoney = (v: number) => v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

    const destaque = [
        { label: 'Valor Total', value: fmtMoney(orcamento.preco_total), icon: <AttachMoneyRoundedIcon />, color: '#22c55e' },
        { label: 'Geração Estimada', value: `${orcamento.geracao_estimada ?? 0} kWh/mês`, icon: <ElectricBoltRoundedIcon />, color: '#f59e0b' },
        { label: 'Economia Estimada', value: orcamento.info?.tarifa && orcamento.geracao_estimada
            ? fmtMoney(orcamento.geracao_estimada * orcamento.info.tarifa)
            : '—', icon: <BoltRoundedIcon />, color: '#6366f1' },
    ];

    return (
        <AppLayout>
            <Head title={`Orçamento #${orcamento.id}`} />

            <PageHeader
                title={`Orçamento #${orcamento.id}`}
                subtitle={nome ?? 'Cliente não informado'}
                breadcrumbs={[
                    { label: 'Orçamentos', href: route('admin.orcamentos.index') },
                    { label: `#${orcamento.id}` },
                ]}
                action={
                    <Button component={Link} href={route('admin.orcamentos.index')} startIcon={<ArrowBackRoundedIcon />}>
                        Voltar
                    </Button>
                }
            />

            {/* Destaques */}
            <Grid container spacing={2} sx={{ mb: 3 }}>
                {destaque.map((d) => (
                    <Grid key={d.label} size={{ xs: 12, sm: 4 }}>
                        <Card>
                            <CardContent sx={{ p: '20px !important' }}>
                                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                                    <Box sx={{
                                        width: 48, height: 48, borderRadius: 2.5,
                                        bgcolor: alpha(d.color, 0.12),
                                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                                        color: d.color, '& svg': { fontSize: 24 },
                                    }}>
                                        {d.icon}
                                    </Box>
                                    <Box>
                                        <Typography variant="h5" fontWeight={700}>{d.value}</Typography>
                                        <Typography variant="caption" color="text.secondary">{d.label}</Typography>
                                    </Box>
                                </Box>
                            </CardContent>
                        </Card>
                    </Grid>
                ))}
            </Grid>

            <Grid container spacing={3}>
                <Grid size={{ xs: 12, md: 8 }}>
                    {/* Dados do orçamento */}
                    <Card sx={{ mb: 3 }}>
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
                                    <InfoRow label="Telefone" value={orcamento.cliente?.celular} />
                                    <InfoRow label="Cidade" value={orcamento.cidade ? `${orcamento.cidade.cidade} - ${orcamento.cidade.estado}` : undefined} />
                                </Grid>
                                <Grid size={{ xs: 12, sm: 6 }}>
                                    <InfoRow label="Vendedor" value={orcamento.consultor?.name} />
                                    <InfoRow label="Tipo de Sistema" value={orcamento.info?.tipo_sistema} />
                                    <InfoRow label="Tipo de Ligação" value={orcamento.info?.tipo_ligacao} />
                                    <InfoRow label="Estrutura" value={orcamento.info?.estrutura} />
                                </Grid>
                            </Grid>
                        </CardContent>
                    </Card>

                    {/* Itens */}
                    {orcamento.itens.length > 0 && (
                        <Card sx={{ mb: 3 }}>
                            <CardHeader
                                title="Itens do Orçamento"
                                titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }}
                            />
                            <Divider />
                            <Table size="small">
                                <TableHead>
                                    <TableRow>
                                        <TableCell>Descrição</TableCell>
                                        <TableCell align="right">Qtd.</TableCell>
                                        <TableCell align="right">Valor Unit.</TableCell>
                                        <TableCell align="right">Total</TableCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {orcamento.itens.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell>{item.descricao}</TableCell>
                                            <TableCell align="right">{item.quantidade}</TableCell>
                                            <TableCell align="right">{fmtMoney(item.valor_unitario)}</TableCell>
                                            <TableCell align="right">
                                                <Typography variant="body2" fontWeight={600}>
                                                    {fmtMoney(item.valor_total)}
                                                </Typography>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    <TableRow>
                                        <TableCell colSpan={3} align="right">
                                            <Typography variant="body2" fontWeight={600} color="text.secondary">
                                                TOTAL
                                            </Typography>
                                        </TableCell>
                                        <TableCell align="right">
                                            <Typography variant="body1" fontWeight={700} color="success.main">
                                                {fmtMoney(orcamento.preco_total)}
                                            </Typography>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </Card>
                    )}

                    {/* Histórico */}
                    {orcamento.historicos.length > 0 && (
                        <Card>
                            <CardHeader
                                title="Histórico"
                                titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }}
                            />
                            <Divider />
                            <CardContent>
                                {orcamento.historicos.map((h) => (
                                    <Box key={h.id} sx={{ display: 'flex', gap: 2, mb: 1.5, pb: 1.5, borderBottom: '1px solid', borderColor: 'divider', '&:last-child': { border: 'none', mb: 0, pb: 0 } }}>
                                        <Box sx={{ flexShrink: 0, pt: 0.5 }}>
                                            <Box sx={{ width: 8, height: 8, borderRadius: '50%', bgcolor: 'primary.main', mt: 0.5 }} />
                                        </Box>
                                        <Box sx={{ flexGrow: 1 }}>
                                            <Typography variant="body2">
                                                {h.mensagem ?? (h.status ? `Status alterado para ${STATUS_LABEL[h.status]}` : '')}
                                            </Typography>
                                            <Typography variant="caption" color="text.secondary">
                                                {h.usuario?.name ?? 'Sistema'} · {new Date(h.created_at).toLocaleString('pt-BR')}
                                            </Typography>
                                        </Box>
                                        <Box sx={{ flexShrink: 0 }}>
                                            <Chip label={h.status ? STATUS_LABEL[h.status] : (ROTULO_EVENTO[h.tipo] ?? h.tipo)} size="small" variant="outlined" />
                                        </Box>
                                    </Box>
                                ))}
                            </CardContent>
                        </Card>
                    )}
                </Grid>

                {/* Sidebar */}
                <Grid size={{ xs: 12, md: 4 }}>
                    {/* Atualizar status */}
                    <Card sx={{ mb: 3 }} component="form" onSubmit={handleUpdate}>
                        <CardHeader title="Atualizar Status" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                        <Divider />
                        <CardContent sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
                            <TextField
                                select label="Status" fullWidth
                                value={data.status}
                                onChange={(e) => setData('status', e.target.value as OrcamentoStatus)}
                                error={!!errors.status}
                                helperText={errors.status ?? (transicoes.length === 0 ? 'Status final — não pode ser alterado' : undefined)}
                            >
                                {[orcamento.status, ...transicoes].map((s) => (
                                    <MenuItem key={s} value={s}>{STATUS_LABEL[s]}</MenuItem>
                                ))}
                            </TextField>
                            <TextField
                                label="Anotações" fullWidth multiline rows={4}
                                value={data.anotacoes}
                                onChange={(e) => setData('anotacoes', e.target.value)}
                            />
                            <Button type="submit" variant="contained" startIcon={<SaveRoundedIcon />} disabled={processing}>
                                Salvar
                            </Button>
                        </CardContent>
                    </Card>

                    {/* Info técnica */}
                    {orcamento.info && (
                        <Card>
                            <CardHeader title="Dados Técnicos" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                            <Divider />
                            <CardContent>
                                <InfoRow label="Consumo Mensal" value={orcamento.info.consumo_mensal ? `${orcamento.info.consumo_mensal} kWh/mês` : undefined} />
                                <InfoRow label="Tarifa" value={orcamento.info.tarifa ? `R$ ${orcamento.info.tarifa}/kWh` : undefined} />
                                <InfoRow label="Fornecedor" value={orcamento.info.fornecedor} />
                            </CardContent>
                        </Card>
                    )}
                </Grid>
            </Grid>
        </AppLayout>
    );
}
