import React, { useState } from 'react';
import {
    Box,
    Button,
    Card,
    Chip,
    MenuItem,
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableRow,
    TextField,
    Typography,
} from '@mui/material';
import HistoryRoundedIcon from '@mui/icons-material/HistoryRounded';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { PageProps, PaginatedData } from '@/types';
import { rotuloPaginacao } from '@/Components/UI/TablePagination';

interface Historico {
    id: number;
    tipo: 'distribuidora' | 'excel' | 'manual';
    status: 'iniciado' | 'concluido' | 'erro';
    itens_importados: number;
    itens_atualizados: number;
    itens_desativados: number;
    iniciado_em: string;
    finalizado_em?: string;
    alertas?: string;
    fornecedor?: { id: number; nome: string };
}

interface Props extends PageProps {
    historicos: PaginatedData<Historico>;
    filters: { tipo?: string; status?: string };
    /** Nome exibido da distribuidora integrada ("Distribuidora" na demonstração). */
    distribuidora: string;
}

const statusConfig = {
    iniciado: { label: 'Em andamento', color: 'warning' as const },
    concluido: { label: 'Concluído', color: 'success' as const },
    erro: { label: 'Erro', color: 'error' as const },
};

const tipoConfig = (distribuidora: string) => ({
    distribuidora: { label: distribuidora, color: 'secondary' as const },
    excel: { label: 'Excel', color: 'default' as const },
    manual: { label: 'Manual', color: 'default' as const },
});

function duracao(iniciado: string, finalizado?: string): string {
    if (!finalizado) return 'Em andamento';
    const diff = new Date(finalizado).getTime() - new Date(iniciado).getTime();
    const s = Math.round(diff / 1000);
    if (s < 60) return `${s}s`;
    return `${Math.floor(s / 60)}m ${s % 60}s`;
}

export default function HistoricoIndex({ historicos, filters, distribuidora }: Props) {
    const tipos = tipoConfig(distribuidora);
    const [tipo, setTipo] = useState(filters.tipo ?? '');
    const [status, setStatus] = useState(filters.status ?? '');

    function applyFilters(overrides: object = {}) {
        router.get(route('admin.integracoes.historico'), { tipo, status, ...overrides }, { preserveState: true, replace: true });
    }

    return (
        <AppLayout>
            <Head title="Histórico de Integrações" />

            <PageHeader
                title="Histórico de Integrações"
                breadcrumbs={[{ label: 'Integrações' }, { label: 'Histórico' }]}
            />

            <Card sx={{ mb: 3, p: 2 }}>
                <Box sx={{ display: 'flex', gap: 2, alignItems: 'center' }}>
                    <TextField select size="small" label="Tipo" value={tipo}
                        onChange={(e) => { setTipo(e.target.value); applyFilters({ tipo: e.target.value }); }}
                        sx={{ minWidth: 150 }}>
                        <MenuItem value="">Todos</MenuItem>
                        <MenuItem value="distribuidora">{distribuidora}</MenuItem>
                        <MenuItem value="excel">Excel</MenuItem>
                        <MenuItem value="manual">Manual</MenuItem>
                    </TextField>
                    <TextField select size="small" label="Status" value={status}
                        onChange={(e) => { setStatus(e.target.value); applyFilters({ status: e.target.value }); }}
                        sx={{ minWidth: 150 }}>
                        <MenuItem value="">Todos</MenuItem>
                        <MenuItem value="concluido">Concluído</MenuItem>
                        <MenuItem value="erro">Erro</MenuItem>
                        <MenuItem value="iniciado">Em andamento</MenuItem>
                    </TextField>
                    {(tipo || status) && (
                        <Button size="small" onClick={() => { setTipo(''); setStatus(''); applyFilters({ tipo: '', status: '' }); }}>
                            Limpar
                        </Button>
                    )}
                </Box>
            </Card>

            <Card>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell>Tipo</TableCell>
                            <TableCell>Fornecedor</TableCell>
                            <TableCell>Status</TableCell>
                            <TableCell align="right">Importados</TableCell>
                            <TableCell align="right">Atualizados</TableCell>
                            <TableCell align="right">Desativados</TableCell>
                            <TableCell>Duração</TableCell>
                            <TableCell>Iniciado em</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {historicos.data.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={8} align="center" sx={{ py: 6 }}>
                                    <HistoryRoundedIcon sx={{ fontSize: 48, color: 'text.disabled', mb: 1 }} />
                                    <Typography color="text.secondary">Nenhum histórico encontrado</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                        {historicos.data.map((h) => {
                            const tipoC = tipos[h.tipo] ?? { label: h.tipo, color: 'default' as const };
                            const statusC = statusConfig[h.status] ?? { label: h.status, color: 'default' as const };
                            return (
                                <TableRow key={h.id} hover>
                                    <TableCell>
                                        <Chip label={tipoC.label} color={tipoC.color} size="small" />
                                    </TableCell>
                                    <TableCell>
                                        <Typography variant="body2">{h.fornecedor?.nome ?? '—'}</Typography>
                                    </TableCell>
                                    <TableCell>
                                        <Chip label={statusC.label} color={statusC.color} size="small" />
                                    </TableCell>
                                    <TableCell align="right">
                                        <Typography variant="body2">{h.itens_importados}</Typography>
                                    </TableCell>
                                    <TableCell align="right">
                                        <Typography variant="body2">{h.itens_atualizados}</Typography>
                                    </TableCell>
                                    <TableCell align="right">
                                        <Typography variant="body2">{h.itens_desativados}</Typography>
                                    </TableCell>
                                    <TableCell>
                                        <Typography variant="body2" fontFamily="monospace">
                                            {duracao(h.iniciado_em, h.finalizado_em)}
                                        </Typography>
                                    </TableCell>
                                    <TableCell>
                                        <Typography variant="body2">
                                            {new Date(h.iniciado_em).toLocaleString('pt-BR')}
                                        </Typography>
                                    </TableCell>
                                </TableRow>
                            );
                        })}
                    </TableBody>
                </Table>

                {historicos.last_page > 1 && (
                    <Box sx={{ display: 'flex', justifyContent: 'center', gap: 1, p: 2 }}>
                        {historicos.links.map((link, i) => (
                            <Button key={i} size="small" variant={link.active ? 'contained' : 'outlined'}
                                disabled={!link.url} onClick={() => link.url && router.visit(link.url)} sx={{ minWidth: 36 }}
                            >{rotuloPaginacao(link.label)}</Button>
                        ))}
                    </Box>
                )}
                <Box sx={{ px: 2, pb: 1.5 }}>
                    <Typography variant="caption" color="text.secondary">
                        {historicos.from}–{historicos.to} de {historicos.total} registros
                    </Typography>
                </Box>
            </Card>
        </AppLayout>
    );
}
