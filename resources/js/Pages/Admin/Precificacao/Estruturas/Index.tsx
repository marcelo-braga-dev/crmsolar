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
import HomeWorkRoundedIcon from '@mui/icons-material/HomeWorkRounded';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { PageProps } from '@/types';

interface EstruturaRow {
    id: number;
    nome: string;
    ativo: boolean;
    margem: number;
}

interface Props extends PageProps {
    estruturas: EstruturaRow[];
}

export default function EstruturasIndex({ estruturas }: Props) {
    const [editTarget, setEditTarget] = useState<EstruturaRow | null>(null);

    const { data, setData, post, processing, errors } = useForm({
        estrutura_id: 0,
        margem: '0',
    });

    function openEdit(e: EstruturaRow) {
        setEditTarget(e);
        setData({ estrutura_id: e.id, margem: String(e.margem) });
    }

    function handleSubmit(ev: React.FormEvent) {
        ev.preventDefault();
        post(route('admin.precificacao.estruturas.store'), {
            onSuccess: () => setEditTarget(null),
        });
    }

    return (
        <AppLayout>
            <Head title="Precificação por Estrutura" />

            <PageHeader
                title="Por Estrutura"
                breadcrumbs={[{ label: 'Precificação' }, { label: 'Por Estrutura' }]}
            />

            <Card>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell>Tipo de Estrutura</TableCell>
                            <TableCell>Status</TableCell>
                            <TableCell align="right">Margem Adicional</TableCell>
                            <TableCell align="right">Ações</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {estruturas.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={4} align="center" sx={{ py: 6 }}>
                                    <HomeWorkRoundedIcon sx={{ fontSize: 48, color: 'text.disabled', mb: 1 }} />
                                    <Typography color="text.secondary">Nenhuma estrutura cadastrada</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                        {estruturas.map((e) => (
                            <TableRow key={e.id} hover>
                                <TableCell>
                                    <Typography variant="body2" fontWeight={600}>{e.nome}</Typography>
                                </TableCell>
                                <TableCell>
                                    <Chip
                                        label={e.ativo ? 'Ativa' : 'Inativa'}
                                        size="small"
                                        color={e.ativo ? 'success' : 'default'}
                                        variant={e.ativo ? 'filled' : 'outlined'}
                                    />
                                </TableCell>
                                <TableCell align="right">
                                    <Chip
                                        label={`${e.margem.toFixed(3)}%`}
                                        size="small"
                                        color={e.margem > 0 ? 'primary' : 'default'}
                                        variant={e.margem > 0 ? 'filled' : 'outlined'}
                                    />
                                </TableCell>
                                <TableCell align="right">
                                    <Tooltip title="Editar margem">
                                        <IconButton size="small" onClick={() => openEdit(e)}>
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
                    <DialogTitle>Editar Margem — {editTarget?.nome}</DialogTitle>
                    <DialogContent sx={{ pt: '16px !important' }}>
                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
                            <TextField
                                label="Margem Adicional (%)"
                                type="number"
                                value={data.margem}
                                onChange={(e) => setData('margem', e.target.value)}
                                error={!!errors.margem}
                                helperText={errors.margem ?? 'Use 0 para sem acréscimo'}
                                inputProps={{ step: '0.001', min: '0', max: '100' }}
                                fullWidth
                                autoFocus
                            />
                        </Box>
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
