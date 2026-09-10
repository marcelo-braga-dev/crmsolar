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
import LocalShippingRoundedIcon from '@mui/icons-material/LocalShippingRounded';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { PageProps } from '@/types';

interface FornecedorRow {
    id: number;
    nome: string;
    ativo: boolean;
    margem: number;
}

interface Props extends PageProps {
    fornecedores: FornecedorRow[];
}

export default function FornecedoresIndex({ fornecedores }: Props) {
    const [editTarget, setEditTarget] = useState<FornecedorRow | null>(null);

    const { data, setData, post, processing, errors } = useForm({
        fornecedor_id: 0,
        margem: '0',
    });

    function openEdit(f: FornecedorRow) {
        setEditTarget(f);
        setData({ fornecedor_id: f.id, margem: String(f.margem) });
    }

    function handleSubmit(ev: React.FormEvent) {
        ev.preventDefault();
        post(route('admin.precificacao.fornecedores.store'), {
            onSuccess: () => setEditTarget(null),
        });
    }

    return (
        <AppLayout>
            <Head title="Precificação por Fornecedor" />

            <PageHeader
                title="Por Fornecedor"
                breadcrumbs={[{ label: 'Precificação' }, { label: 'Por Fornecedor' }]}
            />

            <Card>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell>Fornecedor</TableCell>
                            <TableCell>Status</TableCell>
                            <TableCell align="right">Margem Adicional</TableCell>
                            <TableCell align="right">Ações</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {fornecedores.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={4} align="center" sx={{ py: 6 }}>
                                    <LocalShippingRoundedIcon sx={{ fontSize: 48, color: 'text.disabled', mb: 1 }} />
                                    <Typography color="text.secondary">Nenhum fornecedor cadastrado</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                        {fornecedores.map((f) => (
                            <TableRow key={f.id} hover>
                                <TableCell>
                                    <Typography variant="body2" fontWeight={600}>{f.nome}</Typography>
                                </TableCell>
                                <TableCell>
                                    <Chip
                                        label={f.ativo ? 'Ativo' : 'Inativo'}
                                        size="small"
                                        color={f.ativo ? 'success' : 'default'}
                                        variant={f.ativo ? 'filled' : 'outlined'}
                                    />
                                </TableCell>
                                <TableCell align="right">
                                    <Chip
                                        label={`${f.margem.toFixed(3)}%`}
                                        size="small"
                                        color={f.margem > 0 ? 'primary' : 'default'}
                                        variant={f.margem > 0 ? 'filled' : 'outlined'}
                                    />
                                </TableCell>
                                <TableCell align="right">
                                    <Tooltip title="Editar margem">
                                        <IconButton size="small" onClick={() => openEdit(f)}>
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
