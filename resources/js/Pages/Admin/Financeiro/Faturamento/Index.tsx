import React from 'react';
import {
    Box, Card, Chip, MenuItem, Stack, Table, TableBody, TableCell,
    TableHead, TableRow, TextField, Typography,
} from '@mui/material';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { OrcamentoStatusChip } from '@/Components/UI/StatusChip';
import { PageProps, PaginatedData } from '@/types';

interface OrcamentoRow {
    id: number;
    preco_total: string;
    status: string;
    created_at: string;
    cliente: { nome?: string; razao_social?: string };
    consultor: { name: string };
}

interface Props extends PageProps {
    orcamentos: PaginatedData<OrcamentoRow>;
    total: number;
    filters: { mes?: string; ano?: string };
}

const MESES = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
const ANOS  = Array.from({ length: 5 }, (_, i) => String(new Date().getFullYear() - i));

export default function FaturamentoIndex({ orcamentos, total, filters }: Props) {
    function fmt(val: string | number) {
        return Number(val).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    function applyFilter(params: object) {
        router.get(route('admin.financeiro.faturamento.index'), { ...filters, ...params }, { preserveState: true });
    }

    return (
        <AppLayout>
            <Head title="Faturamento" />
            <PageHeader title="Faturamento" breadcrumbs={[{ label: 'Financeiro' }, { label: 'Faturamento' }]} />

            <Stack direction="row" spacing={2} mb={2} alignItems="center" flexWrap="wrap">
                <TextField select size="small" label="Mês" value={filters.mes ?? ''} onChange={(e) => applyFilter({ mes: e.target.value })} sx={{ minWidth: 140 }}>
                    <MenuItem value="">Todos</MenuItem>
                    {MESES.map((m, i) => <MenuItem key={i + 1} value={i + 1}>{m}</MenuItem>)}
                </TextField>
                <TextField select size="small" label="Ano" value={filters.ano ?? ''} onChange={(e) => applyFilter({ ano: e.target.value })} sx={{ minWidth: 110 }}>
                    <MenuItem value="">Todos</MenuItem>
                    {ANOS.map((a) => <MenuItem key={a} value={a}>{a}</MenuItem>)}
                </TextField>
                <Box sx={{ ml: 'auto' }}>
                    <Typography variant="subtitle1" fontWeight={700}>
                        Total (filtrado): {fmt(orcamentos.data.reduce((s, o) => s + parseFloat(o.preco_total), 0))}
                    </Typography>
                </Box>
            </Stack>

            <Card>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell>Orçamento</TableCell>
                            <TableCell>Cliente</TableCell>
                            <TableCell>Vendedor</TableCell>
                            <TableCell>Status</TableCell>
                            <TableCell align="right">Valor</TableCell>
                            <TableCell>Data</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {orcamentos.data.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={6} align="center" sx={{ py: 6 }}>
                                    <Typography color="text.secondary">Nenhum registro encontrado</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                        {orcamentos.data.map((o) => (
                            <TableRow key={o.id} hover>
                                <TableCell><Chip label={`#${o.id}`} size="small" /></TableCell>
                                <TableCell>
                                    <Typography variant="body2">{o.cliente?.nome ?? o.cliente?.razao_social ?? '—'}</Typography>
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2">{o.consultor?.name}</Typography>
                                </TableCell>
                                <TableCell><OrcamentoStatusChip status={o.status as any} /></TableCell>
                                <TableCell align="right">
                                    <Typography variant="body2" fontFamily="monospace" fontWeight={600}>{fmt(o.preco_total)}</Typography>
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2">{new Date(o.created_at).toLocaleDateString('pt-BR')}</Typography>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </Card>
        </AppLayout>
    );
}
