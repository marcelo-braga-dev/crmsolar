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
    FormControlLabel,
    Switch,
} from '@mui/material';
import AddRoundedIcon from '@mui/icons-material/AddRounded';
import EditRoundedIcon from '@mui/icons-material/EditRounded';
import DeleteRoundedIcon from '@mui/icons-material/DeleteRounded';
import AccountBalanceRoundedIcon from '@mui/icons-material/AccountBalanceRounded';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { ConfirmDialog } from '@/Components/UI/ConfirmDialog';
import { BoolChip } from '@/Components/UI/StatusChip';
import { PageProps } from '@/types';

interface Banco {
    id: number;
    nome: string;
    juros_mensal: string;
    qtd_parcelas: number;
    carencia?: number;
    ativo: boolean;
}

interface FormState {
    nome: string;
    juros_mensal: string;
    qtd_parcelas: string;
    carencia: string;
    ativo: boolean;
}

interface Props extends PageProps {
    bancos: Banco[];
}

export default function BancosIndex({ bancos }: Props) {
    const [modalOpen, setModalOpen] = useState(false);
    const [editTarget, setEditTarget] = useState<Banco | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<Banco | null>(null);

    const { data, setData, post, put, processing, reset, errors } = useForm<FormState>({
        nome: '',
        juros_mensal: '',
        qtd_parcelas: '',
        carencia: '',
        ativo: true,
    });

    function openCreate() {
        setEditTarget(null);
        reset();
        setData({ nome: '', juros_mensal: '', qtd_parcelas: '', carencia: '', ativo: true });
        setModalOpen(true);
    }

    function openEdit(b: Banco) {
        setEditTarget(b);
        setData({
            nome: b.nome,
            juros_mensal: b.juros_mensal,
            qtd_parcelas: String(b.qtd_parcelas),
            carencia: b.carencia != null ? String(b.carencia) : '',
            ativo: b.ativo,
        });
        setModalOpen(true);
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (editTarget) {
            put(route('admin.configuracoes.bancos.update', editTarget.id), {
                onSuccess: () => setModalOpen(false),
            });
        } else {
            post(route('admin.configuracoes.bancos.store'), {
                onSuccess: () => setModalOpen(false),
            });
        }
    }

    function handleDelete() {
        if (!deleteTarget) return;
        router.delete(route('admin.configuracoes.bancos.destroy', deleteTarget.id), {
            onSuccess: () => setDeleteTarget(null),
        });
    }

    return (
        <AppLayout>
            <Head title="Bancos" />

            <PageHeader
                title="Bancos"
                breadcrumbs={[{ label: 'Configurações' }, { label: 'Bancos' }]}
                action={
                    <Button variant="contained" startIcon={<AddRoundedIcon />} onClick={openCreate}>
                        Novo Banco
                    </Button>
                }
            />

            <Card>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell>Banco</TableCell>
                            <TableCell align="right">Juros/mês</TableCell>
                            <TableCell align="right">Parcelas</TableCell>
                            <TableCell align="right">Carência (meses)</TableCell>
                            <TableCell>Status</TableCell>
                            <TableCell align="right">Ações</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {bancos.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={6} align="center" sx={{ py: 6 }}>
                                    <AccountBalanceRoundedIcon sx={{ fontSize: 48, color: 'text.disabled', mb: 1 }} />
                                    <Typography color="text.secondary">Nenhum banco cadastrado</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                        {bancos.map((b) => (
                            <TableRow key={b.id} hover>
                                <TableCell>
                                    <Typography variant="body2" fontWeight={600}>{b.nome}</Typography>
                                </TableCell>
                                <TableCell align="right">
                                    <Chip label={`${parseFloat(b.juros_mensal).toFixed(4)}%`} size="small" variant="outlined" />
                                </TableCell>
                                <TableCell align="right">
                                    <Typography variant="body2">{b.qtd_parcelas}x</Typography>
                                </TableCell>
                                <TableCell align="right">
                                    <Typography variant="body2">{b.carencia != null ? `${b.carencia} meses` : '—'}</Typography>
                                </TableCell>
                                <TableCell><BoolChip value={b.ativo} /></TableCell>
                                <TableCell align="right">
                                    <Tooltip title="Editar">
                                        <IconButton size="small" onClick={() => openEdit(b)}>
                                            <EditRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                    <Tooltip title="Excluir">
                                        <IconButton size="small" color="error" onClick={() => setDeleteTarget(b)}>
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
                    <DialogTitle>{editTarget ? 'Editar Banco' : 'Novo Banco'}</DialogTitle>
                    <DialogContent sx={{ pt: '16px !important', display: 'flex', flexDirection: 'column', gap: 2 }}>
                        <TextField
                            label="Nome do banco *"
                            value={data.nome}
                            onChange={(e) => setData('nome', e.target.value)}
                            error={!!errors.nome}
                            helperText={errors.nome}
                            fullWidth autoFocus
                        />
                        <Box sx={{ display: 'flex', gap: 2 }}>
                            <TextField
                                label="Juros mensal (%) *"
                                type="number"
                                value={data.juros_mensal}
                                onChange={(e) => setData('juros_mensal', e.target.value)}
                                error={!!errors.juros_mensal}
                                helperText={errors.juros_mensal}
                                inputProps={{ step: '0.0001', min: '0', max: '100' }}
                                fullWidth
                            />
                            <TextField
                                label="Qtd. parcelas *"
                                type="number"
                                value={data.qtd_parcelas}
                                onChange={(e) => setData('qtd_parcelas', e.target.value)}
                                error={!!errors.qtd_parcelas}
                                helperText={errors.qtd_parcelas}
                                inputProps={{ min: '1', max: '360' }}
                                fullWidth
                            />
                            <TextField
                                label="Carência (meses)"
                                type="number"
                                value={data.carencia}
                                onChange={(e) => setData('carencia', e.target.value)}
                                inputProps={{ min: '0', max: '12' }}
                                fullWidth
                            />
                        </Box>
                        <FormControlLabel
                            control={<Switch checked={data.ativo} onChange={(e) => setData('ativo', e.target.checked)} />}
                            label="Banco ativo"
                        />
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
                title="Excluir Banco"
                message={`Deseja excluir o banco "${deleteTarget?.nome}"?`}
                confirmLabel="Excluir"
                onConfirm={handleDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </AppLayout>
    );
}
