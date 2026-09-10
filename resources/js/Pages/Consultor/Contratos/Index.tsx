import React from 'react';
import {
    Card, Chip, IconButton, MenuItem, Table, TableBody,
    TableCell, TableHead, TableRow, TextField, Tooltip, Typography,
} from '@mui/material';
import VisibilityRoundedIcon from '@mui/icons-material/VisibilityRounded';
import ArticleRoundedIcon from '@mui/icons-material/ArticleRounded';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { PageProps, PaginatedData } from '@/types';

interface Contrato {
    id: number;
    nome_cliente: string;
    valor_total: string;
    status: string;
    created_at: string;
    orcamento: { id: number };
}

interface Props extends PageProps {
    contratos: PaginatedData<Contrato>;
    filters: { status?: string };
}

const STATUS_COLORS: Record<string, 'default' | 'warning' | 'success' | 'error' | 'info'> = {
    pendente: 'warning',
    assinado: 'success',
    cancelado: 'error',
};

export default function ContratosIndex({ contratos, filters }: Props) {
    function fmt(val: string) {
        return parseFloat(val).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    return (
        <AppLayout>
            <Head title="Contratos" />
            <PageHeader title="Contratos" breadcrumbs={[{ label: 'Contratos' }]} />

            <TextField
                select size="small" label="Status" value={filters.status ?? ''}
                onChange={(e) => router.get(route('consultor.contratos.index'), { status: e.target.value }, { preserveState: true })}
                sx={{ mb: 2, minWidth: 160 }}
            >
                <MenuItem value="">Todos</MenuItem>
                <MenuItem value="pendente">Pendente</MenuItem>
                <MenuItem value="assinado">Assinado</MenuItem>
                <MenuItem value="cancelado">Cancelado</MenuItem>
            </TextField>

            <Card>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell>Contrato</TableCell>
                            <TableCell>Cliente</TableCell>
                            <TableCell>Orçamento</TableCell>
                            <TableCell align="right">Valor</TableCell>
                            <TableCell>Status</TableCell>
                            <TableCell>Data</TableCell>
                            <TableCell align="right">Ações</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {contratos.data.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={7} align="center" sx={{ py: 6 }}>
                                    <ArticleRoundedIcon sx={{ fontSize: 48, color: 'text.disabled', mb: 1 }} />
                                    <Typography color="text.secondary">Nenhum contrato encontrado</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                        {contratos.data.map((c) => (
                            <TableRow key={c.id} hover>
                                <TableCell><Chip label={`#${c.id}`} size="small" /></TableCell>
                                <TableCell>
                                    <Typography variant="body2" fontWeight={600}>{c.nome_cliente}</Typography>
                                </TableCell>
                                <TableCell><Chip label={`#${c.orcamento?.id}`} size="small" variant="outlined" /></TableCell>
                                <TableCell align="right">
                                    <Typography variant="body2" fontFamily="monospace" fontWeight={600}>{fmt(c.valor_total)}</Typography>
                                </TableCell>
                                <TableCell>
                                    <Chip label={c.status} size="small" color={STATUS_COLORS[c.status] ?? 'default'} />
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2">{new Date(c.created_at).toLocaleDateString('pt-BR')}</Typography>
                                </TableCell>
                                <TableCell align="right">
                                    <Tooltip title="Ver contrato">
                                        <IconButton size="small" component={Link} href={route('consultor.contratos.show', c.id)}>
                                            <VisibilityRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </Card>
        </AppLayout>
    );
}
