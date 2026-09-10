import React from 'react';
import {
    Box, Button, Card, Chip, IconButton, MenuItem, Table, TableBody,
    TableCell, TableHead, TableRow, TextField, Tooltip, Typography,
} from '@mui/material';
import AddRoundedIcon from '@mui/icons-material/AddRounded';
import VisibilityRoundedIcon from '@mui/icons-material/VisibilityRounded';
import MapRoundedIcon from '@mui/icons-material/MapRounded';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { PageProps, PaginatedData } from '@/types';

interface Visita {
    id: number;
    data_agendada: string;
    status: string;
    anotacoes?: string;
    cliente: { nome?: string; razao_social?: string; tipo_pessoa: string };
    orcamento?: { id: number };
}

interface Props extends PageProps {
    visitas: PaginatedData<Visita>;
    filters: { status?: string };
}

const STATUS_COLORS: Record<string, 'default' | 'warning' | 'success' | 'error'> = {
    agendada: 'warning',
    realizada: 'success',
    cancelada: 'error',
};

export default function VisitasIndex({ visitas, filters }: Props) {
    return (
        <AppLayout>
            <Head title="Visitas Técnicas" />
            <PageHeader
                title="Visitas Técnicas"
                breadcrumbs={[{ label: 'Visitas Técnicas' }]}
                action={
                    <Button component={Link} href={route('consultor.visitas.create')} variant="contained" startIcon={<AddRoundedIcon />}>
                        Agendar Visita
                    </Button>
                }
            />

            <Box sx={{ mb: 2 }}>
                <TextField
                    select size="small" label="Status" value={filters.status ?? ''}
                    onChange={(e) => router.get(route('consultor.visitas.index'), { status: e.target.value }, { preserveState: true })}
                    sx={{ minWidth: 160 }}
                >
                    <MenuItem value="">Todos</MenuItem>
                    <MenuItem value="agendada">Agendada</MenuItem>
                    <MenuItem value="realizada">Realizada</MenuItem>
                    <MenuItem value="cancelada">Cancelada</MenuItem>
                </TextField>
            </Box>

            <Card>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell>Cliente</TableCell>
                            <TableCell>Data Agendada</TableCell>
                            <TableCell>Status</TableCell>
                            <TableCell>Orçamento</TableCell>
                            <TableCell align="right">Ações</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {visitas.data.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={5} align="center" sx={{ py: 6 }}>
                                    <MapRoundedIcon sx={{ fontSize: 48, color: 'text.disabled', mb: 1 }} />
                                    <Typography color="text.secondary">Nenhuma visita agendada</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                        {visitas.data.map((v) => (
                            <TableRow key={v.id} hover>
                                <TableCell>
                                    <Typography variant="body2" fontWeight={600}>
                                        {v.cliente.tipo_pessoa === 'pj' ? v.cliente.razao_social : v.cliente.nome}
                                    </Typography>
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2">
                                        {new Date(v.data_agendada).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' })}
                                    </Typography>
                                </TableCell>
                                <TableCell>
                                    <Chip label={v.status} size="small" color={STATUS_COLORS[v.status] ?? 'default'} />
                                </TableCell>
                                <TableCell>
                                    {v.orcamento ? <Chip label={`#${v.orcamento.id}`} size="small" variant="outlined" /> : '—'}
                                </TableCell>
                                <TableCell align="right">
                                    <Tooltip title="Ver detalhes">
                                        <IconButton size="small" component={Link} href={route('consultor.visitas.show', v.id)}>
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
