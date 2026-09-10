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
import AddRoundedIcon from '@mui/icons-material/AddRounded';
import EditRoundedIcon from '@mui/icons-material/EditRounded';
import DeleteRoundedIcon from '@mui/icons-material/DeleteRounded';
import PriceChangeRoundedIcon from '@mui/icons-material/PriceChangeRounded';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { ConfirmDialog } from '@/Components/UI/ConfirmDialog';
import { PageProps } from '@/types';

interface Faixa {
    id: number;
    nome: string;
    potencia_min: string;
    potencia_max: string | null;
    margem: string;
    ordem: number;
}

interface Props extends PageProps {
    faixas: Faixa[];
}

type FormState = {
    nome: string;
    potencia_min: string;
    potencia_max: string;
    margem: string;
    ordem: string;
};

export default function MargemPrincipalIndex({ faixas }: Props) {
    const [modalOpen, setModalOpen] = useState(false);
    const [editTarget, setEditTarget] = useState<Faixa | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<Faixa | null>(null);

    const { data, setData, post, put, processing, reset, errors } = useForm<FormState>({
        nome: '',
        potencia_min: '0',
        potencia_max: '',
        margem: '0',
        ordem: '0',
    });

    function openCreate() {
        setEditTarget(null);
        reset();
        setData({ nome: '', potencia_min: '0', potencia_max: '', margem: '0', ordem: String(faixas.length) });
        setModalOpen(true);
    }

    function openEdit(f: Faixa) {
        setEditTarget(f);
        setData({
            nome: f.nome,
            potencia_min: f.potencia_min,
            potencia_max: f.potencia_max ?? '',
            margem: f.margem,
            ordem: String(f.ordem),
        });
        setModalOpen(true);
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (editTarget) {
            put(route('admin.precificacao.margem-principal.update', editTarget.id), {
                onSuccess: () => setModalOpen(false),
            });
        } else {
            post(route('admin.precificacao.margem-principal.store'), {
                onSuccess: () => setModalOpen(false),
            });
        }
    }

    function handleDelete() {
        if (!deleteTarget) return;
        router.delete(route('admin.precificacao.margem-principal.destroy', deleteTarget.id), {
            onSuccess: () => setDeleteTarget(null),
        });
    }

    function faixaLabel(f: Faixa) {
        const min = parseFloat(f.potencia_min);
        const max = f.potencia_max ? parseFloat(f.potencia_max) : null;
        if (min === 0 && max) return `Até ${max} kWp`;
        if (max) return `${min} – ${max} kWp`;
        return `Acima de ${min} kWp`;
    }

    return (
        <AppLayout>
            <Head title="Margem Principal" />

            <PageHeader
                title="Margem Principal"
                breadcrumbs={[{ label: 'Precificação' }, { label: 'Margem Principal' }]}
                action={
                    <Button variant="contained" startIcon={<AddRoundedIcon />} onClick={openCreate}>
                        Nova Faixa
                    </Button>
                }
            />

            <Card>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell>Ordem</TableCell>
                            <TableCell>Nome</TableCell>
                            <TableCell>Faixa de Potência</TableCell>
                            <TableCell align="right">Margem</TableCell>
                            <TableCell align="right">Ações</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {faixas.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={5} align="center" sx={{ py: 6 }}>
                                    <PriceChangeRoundedIcon sx={{ fontSize: 48, color: 'text.disabled', mb: 1 }} />
                                    <Typography color="text.secondary">Nenhuma faixa cadastrada</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                        {faixas.map((f) => (
                            <TableRow key={f.id} hover>
                                <TableCell>
                                    <Chip label={f.ordem} size="small" variant="outlined" />
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2" fontWeight={600}>{f.nome}</Typography>
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2" color="text.secondary">{faixaLabel(f)}</Typography>
                                </TableCell>
                                <TableCell align="right">
                                    <Chip
                                        label={`${parseFloat(f.margem).toFixed(2)}%`}
                                        size="small"
                                        color="primary"
                                        variant="outlined"
                                    />
                                </TableCell>
                                <TableCell align="right">
                                    <Tooltip title="Editar">
                                        <IconButton size="small" onClick={() => openEdit(f)}>
                                            <EditRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                    <Tooltip title="Excluir">
                                        <IconButton size="small" color="error" onClick={() => setDeleteTarget(f)}>
                                            <DeleteRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </Card>

            <Dialog open={modalOpen} onClose={() => setModalOpen(false)} maxWidth="sm" fullWidth>
                <form onSubmit={handleSubmit}>
                    <DialogTitle>{editTarget ? 'Editar Faixa' : 'Nova Faixa de Margem'}</DialogTitle>
                    <DialogContent sx={{ pt: '16px !important', display: 'flex', flexDirection: 'column', gap: 2 }}>
                        <TextField
                            label="Nome *"
                            placeholder="Ex: Residencial Pequeno"
                            value={data.nome}
                            onChange={(e) => setData('nome', e.target.value)}
                            error={!!errors.nome}
                            helperText={errors.nome}
                            fullWidth
                            autoFocus
                        />
                        <Box sx={{ display: 'flex', gap: 2 }}>
                            <TextField
                                label="Potência Mínima (kWp) *"
                                type="number"
                                value={data.potencia_min}
                                onChange={(e) => setData('potencia_min', e.target.value)}
                                error={!!errors.potencia_min}
                                helperText={errors.potencia_min}
                                inputProps={{ step: '0.001', min: '0' }}
                                fullWidth
                            />
                            <TextField
                                label="Potência Máxima (kWp)"
                                type="number"
                                value={data.potencia_max}
                                onChange={(e) => setData('potencia_max', e.target.value)}
                                error={!!errors.potencia_max}
                                helperText={errors.potencia_max ?? 'Deixe em branco para sem limite'}
                                inputProps={{ step: '0.001', min: '0' }}
                                fullWidth
                            />
                        </Box>
                        <Box sx={{ display: 'flex', gap: 2 }}>
                            <TextField
                                label="Margem (%) *"
                                type="number"
                                value={data.margem}
                                onChange={(e) => setData('margem', e.target.value)}
                                error={!!errors.margem}
                                helperText={errors.margem}
                                inputProps={{ step: '0.001', min: '0', max: '100' }}
                                fullWidth
                            />
                            <TextField
                                label="Ordem *"
                                type="number"
                                value={data.ordem}
                                onChange={(e) => setData('ordem', e.target.value)}
                                error={!!errors.ordem}
                                helperText={errors.ordem}
                                inputProps={{ min: '0' }}
                                fullWidth
                            />
                        </Box>
                    </DialogContent>
                    <DialogActions sx={{ px: 3, pb: 2 }}>
                        <Button onClick={() => setModalOpen(false)}>Cancelar</Button>
                        <Button type="submit" variant="contained" disabled={processing}>
                            {editTarget ? 'Salvar' : 'Criar'}
                        </Button>
                    </DialogActions>
                </form>
            </Dialog>

            <ConfirmDialog
                open={!!deleteTarget}
                title="Excluir Faixa"
                message={`Deseja excluir a faixa "${deleteTarget?.nome}"? Esta ação não pode ser desfeita.`}
                confirmLabel="Excluir"
                onConfirm={handleDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </AppLayout>
    );
}
