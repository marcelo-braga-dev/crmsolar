import React from 'react';
import {
    Box, Card, Chip, MenuItem, Table, TableBody, TableCell,
    TableHead, TableRow, TextField, Typography,
} from '@mui/material';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { PageProps, PaginatedData } from '@/types';

interface Vendedor { id: number; name: string; comissao_percentual: string | null }

interface ItemRow {
    id: number;
    preco_venda_total: string;
    comissao_percentual: string;
    orcamento: {
        id: number;
        cliente: { nome?: string; razao_social?: string };
        consultor: { name: string };
    };
}

interface Props extends PageProps {
    comissoes: PaginatedData<ItemRow>;
    vendedores: Vendedor[];
    filters: { consultor_id?: string };
}

export default function ComissoesIndex({ comissoes, vendedores, filters }: Props) {
    function fmt(val: string) {
        return parseFloat(val).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    return (
        <AppLayout>
            <Head title="Comissões" />
            <PageHeader title="Comissões" breadcrumbs={[{ label: 'Financeiro' }, { label: 'Comissões' }]} />

            <Card sx={{ mb: 2, p: 2 }}>
                <TextField
                    select size="small" label="Vendedor" defaultValue={filters.consultor_id ?? ''}
                    onChange={(e) => router.get(route('admin.financeiro.comissoes.index'), { consultor_id: e.target.value }, { preserveState: true })}
                    sx={{ minWidth: 220 }}
                >
                    <MenuItem value="">Todos os vendedores</MenuItem>
                    {vendedores.map((v) => <MenuItem key={v.id} value={v.id}>{v.name}</MenuItem>)}
                </TextField>
            </Card>

            <Card>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell>Orçamento</TableCell>
                            <TableCell>Cliente</TableCell>
                            <TableCell>Vendedor</TableCell>
                            <TableCell align="right">Valor Venda</TableCell>
                            <TableCell align="right">Comissão %</TableCell>
                            <TableCell align="right">Valor Comissão</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {comissoes.data.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={6} align="center" sx={{ py: 6 }}>
                                    <Typography color="text.secondary">Nenhuma comissão encontrada</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                        {comissoes.data.map((item) => {
                            const comissaoValor = parseFloat(item.preco_venda_total) * parseFloat(item.comissao_percentual) / 100;
                            return (
                                <TableRow key={item.id} hover>
                                    <TableCell><Chip label={`#${item.orcamento.id}`} size="small" /></TableCell>
                                    <TableCell>
                                        <Typography variant="body2">{item.orcamento.cliente?.nome ?? item.orcamento.cliente?.razao_social ?? '—'}</Typography>
                                    </TableCell>
                                    <TableCell>
                                        <Typography variant="body2">{item.orcamento.consultor?.name}</Typography>
                                    </TableCell>
                                    <TableCell align="right">
                                        <Typography variant="body2" fontFamily="monospace">{fmt(item.preco_venda_total)}</Typography>
                                    </TableCell>
                                    <TableCell align="right">
                                        <Chip label={`${parseFloat(item.comissao_percentual).toFixed(2)}%`} size="small" variant="outlined" />
                                    </TableCell>
                                    <TableCell align="right">
                                        <Typography variant="body2" fontFamily="monospace" fontWeight={600}>
                                            {comissaoValor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}
                                        </Typography>
                                    </TableCell>
                                </TableRow>
                            );
                        })}
                    </TableBody>
                </Table>
            </Card>
        </AppLayout>
    );
}
