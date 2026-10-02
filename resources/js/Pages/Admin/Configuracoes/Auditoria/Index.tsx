import React from 'react';
import {
    Box, Button, Card, Chip, MenuItem, Table, TableBody, TableCell, TableHead, TableRow, TextField, Typography,
} from '@mui/material';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { TablePagination } from '@/Components/UI/TablePagination';
import { PageProps, PaginatedData } from '@/types';

type Valor = string | number | boolean | null | Record<string, unknown> | unknown[];

interface Atividade {
    id: number;
    data: string;
    evento: 'created' | 'updated' | 'deleted' | 'restored' | null;
    tipo: string;
    registro_id: number | null;
    usuario: string | null;
    alteracoes: Array<{ campo: string; antes: Valor; depois: Valor }>;
}

interface Props extends PageProps {
    atividades: PaginatedData<Atividade>;
    filters: { tipo?: string; usuario_id?: string; evento?: string };
    tipos: Array<{ value: string; label: string }>;
    usuarios: Array<{ id: number; name: string }>;
}

const EVENTOS: Record<string, { label: string; color: 'success' | 'info' | 'error' | 'default' }> = {
    created: { label: 'Criado', color: 'success' },
    updated: { label: 'Alterado', color: 'info' },
    deleted: { label: 'Excluído', color: 'error' },
    restored: { label: 'Restaurado', color: 'default' },
};

function formatar(v: Valor): string {
    if (v === null || v === undefined || v === '') return '—';
    if (typeof v === 'boolean') return v ? 'sim' : 'não';
    if (typeof v === 'object') return JSON.stringify(v);
    return String(v);
}

export default function AuditoriaIndex({ atividades, filters, tipos, usuarios }: Props) {
    function filtrar(mudanca: Partial<Props['filters']>) {
        router.get(route('admin.configuracoes.auditoria'), { ...filters, ...mudanca }, { preserveState: true, replace: true });
    }

    const temFiltro = !!(filters.tipo || filters.usuario_id || filters.evento);

    return (
        <AppLayout>
            <Head title="Auditoria" />

            <PageHeader
                title="Auditoria"
                subtitle="Quem alterou preços, margens, status e cadastros — e quando"
                breadcrumbs={[{ label: 'Configurações' }, { label: 'Auditoria' }]}
            />

            <Card sx={{ mb: 3, p: 2 }}>
                <Box sx={{ display: 'flex', gap: 2, alignItems: 'center', flexWrap: 'wrap' }}>
                    <TextField select size="small" label="Registro" value={filters.tipo ?? ''} sx={{ minWidth: 220 }}
                        onChange={(e) => filtrar({ tipo: e.target.value })}>
                        <MenuItem value="">Todos</MenuItem>
                        {tipos.map((t) => <MenuItem key={t.value} value={t.value}>{t.label}</MenuItem>)}
                    </TextField>
                    <TextField select size="small" label="Usuário" value={filters.usuario_id ?? ''} sx={{ minWidth: 200 }}
                        onChange={(e) => filtrar({ usuario_id: e.target.value })}>
                        <MenuItem value="">Todos</MenuItem>
                        {usuarios.map((u) => <MenuItem key={u.id} value={String(u.id)}>{u.name}</MenuItem>)}
                    </TextField>
                    <TextField select size="small" label="Evento" value={filters.evento ?? ''} sx={{ minWidth: 150 }}
                        onChange={(e) => filtrar({ evento: e.target.value })}>
                        <MenuItem value="">Todos</MenuItem>
                        {Object.entries(EVENTOS).map(([v, e]) => <MenuItem key={v} value={v}>{e.label}</MenuItem>)}
                    </TextField>
                    {temFiltro && (
                        <Button size="small" onClick={() => filtrar({ tipo: '', usuario_id: '', evento: '' })}>Limpar</Button>
                    )}
                </Box>
            </Card>

            <Card>
                <Table size="small">
                    <TableHead>
                        <TableRow>
                            <TableCell>Data</TableCell>
                            <TableCell>Usuário</TableCell>
                            <TableCell>Registro</TableCell>
                            <TableCell>Evento</TableCell>
                            <TableCell>Alterações</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {atividades.data.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={5} align="center" sx={{ py: 4 }}>
                                    <Typography color="text.secondary">Nenhum registro de auditoria encontrado</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                        {atividades.data.map((a) => (
                            <TableRow key={a.id} sx={{ verticalAlign: 'top' }}>
                                <TableCell sx={{ whiteSpace: 'nowrap' }}>{new Date(a.data).toLocaleString('pt-BR')}</TableCell>
                                <TableCell>{a.usuario ?? <Typography variant="body2" color="text.secondary">Sistema</Typography>}</TableCell>
                                <TableCell>{a.tipo}{a.registro_id ? ` #${a.registro_id}` : ''}</TableCell>
                                <TableCell>
                                    {a.evento && <Chip size="small" label={EVENTOS[a.evento]?.label ?? a.evento} color={EVENTOS[a.evento]?.color ?? 'default'} />}
                                </TableCell>
                                <TableCell>
                                    {a.alteracoes.map((c) => (
                                        <Typography key={c.campo} variant="body2" sx={{ wordBreak: 'break-word' }}>
                                            <strong>{c.campo}</strong>:{' '}
                                            {a.evento === 'updated'
                                                ? <>{formatar(c.antes)} → {formatar(c.depois)}</>
                                                : formatar(a.evento === 'deleted' ? c.antes : c.depois)}
                                        </Typography>
                                    ))}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
                <TablePagination {...atividades} label="registros" />
            </Card>
        </AppLayout>
    );
}
