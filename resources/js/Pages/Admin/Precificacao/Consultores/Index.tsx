import React, { useState } from 'react';
import {
    Box,
    Button,
    Card,
    Chip,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    IconButton,
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableRow,
    TextField,
    Tooltip,
    Typography,
} from '@mui/material';
import EditRoundedIcon from '@mui/icons-material/EditRounded';
import BadgeRoundedIcon from '@mui/icons-material/BadgeRounded';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { PageProps } from '@/types';

interface ConsultorRow {
    id: number;
    name: string;
    email: string;
    tipo: string;
    status: boolean;
    comissao_percentual: string;
}

interface Props extends PageProps {
    consultores: ConsultorRow[];
}

export default function ConsultoresIndex({ consultores }: Props) {
    const [editTarget, setEditTarget] = useState<ConsultorRow | null>(null);

    const { data, setData, put, processing, errors } = useForm({
        comissao_percentual: '0',
    });

    function openEdit(v: ConsultorRow) {
        setEditTarget(v);
        setData({ comissao_percentual: v.comissao_percentual ?? '0' });
    }

    function handleSubmit(ev: React.FormEvent) {
        ev.preventDefault();
        if (!editTarget) return;
        put(route('admin.precificacao.consultores.update', editTarget.id), {
            onSuccess: () => setEditTarget(null),
        });
    }

    function tipoLabel(tipo: string) {
        if (tipo === 'admin_consultor') return 'Admin + Consultor';
        return 'Consultor';
    }

    return (
        <AppLayout>
            <Head title="Precificação por Consultor" />

            <PageHeader
                title="Por Consultor"
                breadcrumbs={[{ label: 'Precificação' }, { label: 'Por Consultor' }]}
            />

            <Card>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell>Consultor</TableCell>
                            <TableCell>Tipo</TableCell>
                            <TableCell>Status</TableCell>
                            <TableCell align="right">Margem (Comissão)</TableCell>
                            <TableCell align="right">Ações</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {consultores.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={5} align="center" sx={{ py: 6 }}>
                                    <BadgeRoundedIcon sx={{ fontSize: 48, color: 'text.disabled', mb: 1 }} />
                                    <Typography color="text.secondary">Nenhum consultor cadastrado</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                        {consultores.map((v) => (
                            <TableRow key={v.id} hover>
                                <TableCell>
                                    <Box>
                                        <Typography variant="body2" fontWeight={600}>{v.name}</Typography>
                                        <Typography variant="caption" color="text.secondary">{v.email}</Typography>
                                    </Box>
                                </TableCell>
                                <TableCell>
                                    <Chip label={tipoLabel(v.tipo)} size="small" variant="outlined" />
                                </TableCell>
                                <TableCell>
                                    <Chip
                                        label={v.status ? 'Ativo' : 'Inativo'}
                                        size="small"
                                        color={v.status ? 'success' : 'default'}
                                        variant={v.status ? 'filled' : 'outlined'}
                                    />
                                </TableCell>
                                <TableCell align="right">
                                    <Chip
                                        label={`${parseFloat(v.comissao_percentual ?? '0').toFixed(2)}%`}
                                        size="small"
                                        color={parseFloat(v.comissao_percentual ?? '0') > 0 ? 'primary' : 'default'}
                                        variant={parseFloat(v.comissao_percentual ?? '0') > 0 ? 'filled' : 'outlined'}
                                    />
                                </TableCell>
                                <TableCell align="right">
                                    <Tooltip title="Editar margem">
                                        <IconButton size="small" onClick={() => openEdit(v)}>
                                            <EditRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </Card>

            <Dialog open={!!editTarget} onClose={() => setEditTarget(null)} maxWidth="xs" fullWidth>
                <form onSubmit={handleSubmit}>
                    <DialogTitle>Editar Margem — {editTarget?.name}</DialogTitle>
                    <DialogContent sx={{ pt: '16px !important' }}>
                        <TextField
                            label="Margem / Comissão (%)"
                            type="number"
                            value={data.comissao_percentual}
                            onChange={(e) => setData('comissao_percentual', e.target.value)}
                            error={!!errors.comissao_percentual}
                            helperText={errors.comissao_percentual ?? 'Percentual somado às margens no cálculo do orçamento'}
                            inputProps={{ step: '0.01', min: '0', max: '100' }}
                            fullWidth
                            autoFocus
                        />
                    </DialogContent>
                    <DialogActions sx={{ px: 3, pb: 2 }}>
                        <Button onClick={() => setEditTarget(null)}>Cancelar</Button>
                        <Button type="submit" variant="contained" disabled={processing}>Salvar</Button>
                    </DialogActions>
                </form>
            </Dialog>
        </AppLayout>
    );
}
