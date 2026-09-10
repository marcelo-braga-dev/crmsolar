import React from 'react';
import {
    Box, Card, CardContent, CardHeader, Chip, Divider,
    Grid, Table, TableBody, TableCell, TableHead, TableRow, Typography,
} from '@mui/material';
import AccountBalanceWalletRoundedIcon from '@mui/icons-material/AccountBalanceWalletRounded';
import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { OrcamentoStatusChip } from '@/Components/UI/StatusChip';
import { PageProps } from '@/types';

interface OrcamentoRow {
    id: number;
    preco_total: string;
    status: string;
    created_at: string;
    cliente: { nome?: string; razao_social?: string; tipo_pessoa: string };
}

interface Props extends PageProps {
    orcamentos: OrcamentoRow[];
    total_comissoes: number;
}

export default function FinanceiroIndex({ orcamentos, total_comissoes }: Props) {
    function fmt(val: string | number) {
        return Number(val).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    const totalVendas = orcamentos.reduce((s, o) => s + parseFloat(o.preco_total), 0);

    return (
        <AppLayout>
            <Head title="Financeiro" />
            <PageHeader title="Financeiro" breadcrumbs={[{ label: 'Financeiro' }]} />

            <Grid container spacing={2} mb={3}>
                <Grid size={{ xs: 12, sm: 6 }}>
                    <Card>
                        <CardContent sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
                            <AccountBalanceWalletRoundedIcon color="primary" sx={{ fontSize: 40 }} />
                            <Box>
                                <Typography variant="caption" color="text.secondary">Total de Vendas</Typography>
                                <Typography variant="h5" fontWeight={700}>{fmt(totalVendas)}</Typography>
                            </Box>
                        </CardContent>
                    </Card>
                </Grid>
                <Grid size={{ xs: 12, sm: 6 }}>
                    <Card>
                        <CardContent sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
                            <AccountBalanceWalletRoundedIcon color="success" sx={{ fontSize: 40 }} />
                            <Box>
                                <Typography variant="caption" color="text.secondary">Comissões Estimadas</Typography>
                                <Typography variant="h5" fontWeight={700} color="success.main">{fmt(total_comissoes)}</Typography>
                            </Box>
                        </CardContent>
                    </Card>
                </Grid>
            </Grid>

            <Card>
                <CardHeader title="Orçamentos aprovados" />
                <Divider />
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell>Orçamento</TableCell>
                            <TableCell>Cliente</TableCell>
                            <TableCell>Status</TableCell>
                            <TableCell align="right">Valor</TableCell>
                            <TableCell>Data</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {orcamentos.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={5} align="center" sx={{ py: 4 }}>
                                    <Typography color="text.secondary">Nenhum orçamento aprovado ainda</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                        {orcamentos.map((o) => (
                            <TableRow key={o.id} hover>
                                <TableCell><Chip label={`#${o.id}`} size="small" /></TableCell>
                                <TableCell>
                                    <Typography variant="body2">{o.cliente.tipo_pessoa === 'pj' ? o.cliente.razao_social : o.cliente.nome}</Typography>
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
