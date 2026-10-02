import React, { useState } from 'react';
import {
    Box,
    Button,
    Card,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    Chip,
    IconButton,
    Switch,
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
import CategoryRoundedIcon from '@mui/icons-material/CategoryRounded';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { ConfirmDialog } from '@/Components/UI/ConfirmDialog';
import { BoolChip } from '@/Components/UI/StatusChip';
import { PageProps } from '@/types';

interface Categoria {
    id: number;
    nome: string;
    slug: string;
    descricao?: string | null;
    icone?: string | null;
    eh_componente_kit: boolean;
    exige_potencia: boolean;
    ativo: boolean;
    ordem: number;
    produtos_count: number;
}

interface Props extends PageProps {
    categorias: Categoria[];
}

type FormState = {
    nome: string;
    slug: string;
    descricao: string;
    icone: string;
    eh_componente_kit: boolean;
    exige_potencia: boolean;
    ativo: boolean;
    ordem: number;
};

const emptyForm: FormState = {
    nome: '',
    slug: '',
    descricao: '',
    icone: '',
    eh_componente_kit: false,
    exige_potencia: false,
    ativo: true,
    ordem: 0,
};

export default function CategoriasIndex({ categorias }: Props) {
    const [modalOpen, setModalOpen] = useState(false);
    const [editTarget, setEditTarget] = useState<Categoria | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<Categoria | null>(null);

    const { data, setData, post, put, processing, reset, errors } = useForm<FormState>(emptyForm);

    function openCreate() {
        setEditTarget(null);
        reset();
        setData(emptyForm);
        setModalOpen(true);
    }

    function openEdit(c: Categoria) {
        setEditTarget(c);
        setData({
            nome: c.nome,
            slug: c.slug,
            descricao: c.descricao ?? '',
            icone: c.icone ?? '',
            eh_componente_kit: c.eh_componente_kit,
            exige_potencia: c.exige_potencia,
            ativo: c.ativo,
            ordem: c.ordem,
        });
        setModalOpen(true);
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (editTarget) {
            put(route('admin.produtos.categorias.update', editTarget.id), {
                onSuccess: () => setModalOpen(false),
            });
        } else {
            post(route('admin.produtos.categorias.store'), {
                onSuccess: () => setModalOpen(false),
            });
        }
    }

    function handleDelete() {
        if (!deleteTarget) return;
        router.delete(route('admin.produtos.categorias.destroy', deleteTarget.id), {
            onSuccess: () => setDeleteTarget(null),
        });
    }

    return (
        <AppLayout>
            <Head title="Categorias de Produtos" />

            <PageHeader
                title="Categorias de Produtos"
                breadcrumbs={[{ label: 'Produtos' }, { label: 'Categorias' }]}
                action={
                    <Button variant="contained" startIcon={<AddRoundedIcon />} onClick={openCreate}>
                        Nova Categoria
                    </Button>
                }
            />

            <Card>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell>Ordem</TableCell>
                            <TableCell>Categoria</TableCell>
                            <TableCell>Slug</TableCell>
                            <TableCell>Componente de Kit</TableCell>
                            <TableCell>Exige Potência</TableCell>
                            <TableCell>Produtos</TableCell>
                            <TableCell>Status</TableCell>
                            <TableCell align="right">Ações</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {categorias.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={8} align="center" sx={{ py: 6 }}>
                                    <CategoryRoundedIcon sx={{ fontSize: 48, color: 'text.disabled', mb: 1 }} />
                                    <Typography color="text.secondary">Nenhuma categoria cadastrada</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                        {categorias.map((c) => (
                            <TableRow key={c.id} hover>
                                <TableCell>{c.ordem}</TableCell>
                                <TableCell>
                                    <Typography variant="body2" fontWeight={600}>{c.nome}</Typography>
                                    {c.descricao && (
                                        <Typography variant="caption" color="text.secondary">{c.descricao}</Typography>
                                    )}
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2" color="text.secondary">{c.slug}</Typography>
                                </TableCell>
                                <TableCell>
                                    <BoolChip value={c.eh_componente_kit} />
                                </TableCell>
                                <TableCell>
                                    <BoolChip value={c.exige_potencia} />
                                </TableCell>
                                <TableCell>
                                    <Chip label={c.produtos_count} size="small" variant="outlined" />
                                </TableCell>
                                <TableCell>
                                    <Chip
                                        label={c.ativo ? 'Ativa' : 'Inativa'}
                                        size="small"
                                        color={c.ativo ? 'success' : 'default'}
                                        variant={c.ativo ? 'filled' : 'outlined'}
                                    />
                                </TableCell>
                                <TableCell align="right">
                                    <Tooltip title="Editar">
                                        <IconButton size="small" onClick={() => openEdit(c)}>
                                            <EditRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                    <Tooltip title="Excluir">
                                        <IconButton size="small" color="error" onClick={() => setDeleteTarget(c)}>
                                            <DeleteRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </Card>

            {/* Modal criar/editar */}
            <Dialog open={modalOpen} onClose={() => setModalOpen(false)} maxWidth="sm" fullWidth>
                <form onSubmit={handleSubmit}>
                    <DialogTitle>{editTarget ? 'Editar Categoria' : 'Nova Categoria'}</DialogTitle>
                    <DialogContent sx={{ pt: '16px !important', display: 'flex', flexDirection: 'column', gap: 2 }}>
                        <TextField
                            label="Nome *"
                            value={data.nome}
                            onChange={(e) => setData('nome', e.target.value)}
                            error={!!errors.nome}
                            helperText={errors.nome}
                            fullWidth
                            autoFocus
                        />
                        <TextField
                            label="Slug *"
                            value={data.slug}
                            onChange={(e) => setData('slug', e.target.value)}
                            error={!!errors.slug}
                            helperText={errors.slug ?? 'Identificador único, ex: paineis-solares'}
                            fullWidth
                            disabled={!!editTarget}
                        />
                        <TextField
                            label="Descrição"
                            value={data.descricao}
                            onChange={(e) => setData('descricao', e.target.value)}
                            error={!!errors.descricao}
                            helperText={errors.descricao}
                            fullWidth
                        />
                        <TextField
                            label="Ícone"
                            value={data.icone}
                            onChange={(e) => setData('icone', e.target.value)}
                            error={!!errors.icone}
                            helperText={errors.icone ?? 'Nome do ícone MUI, ex: SolarPowerRounded'}
                            fullWidth
                        />
                        <TextField
                            label="Ordem"
                            type="number"
                            value={data.ordem}
                            onChange={(e) => setData('ordem', Number(e.target.value))}
                            error={!!errors.ordem}
                            helperText={errors.ordem}
                            fullWidth
                        />
                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                            <Switch
                                checked={data.eh_componente_kit}
                                onChange={(e) => setData('eh_componente_kit', e.target.checked)}
                            />
                            <Typography variant="body2">Pode ser usada como componente de kit</Typography>
                        </Box>
                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                            <Switch
                                checked={data.exige_potencia}
                                onChange={(e) => setData('exige_potencia', e.target.checked)}
                            />
                            <Typography variant="body2">Produtos exigem campo de potência</Typography>
                        </Box>
                        {editTarget && (
                            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                                <Switch checked={data.ativo} onChange={(e) => setData('ativo', e.target.checked)} />
                                <Typography variant="body2">Categoria ativa</Typography>
                            </Box>
                        )}
                    </DialogContent>
                    <DialogActions sx={{ px: 3, pb: 2 }}>
                        <Button onClick={() => setModalOpen(false)}>Cancelar</Button>
                        <Button type="submit" variant="contained" disabled={processing}>
                            {editTarget ? 'Salvar' : 'Criar'}
                        </Button>
                    </DialogActions>
                </form>
            </Dialog>

            {/* Confirm delete */}
            <ConfirmDialog
                open={!!deleteTarget}
                title="Excluir Categoria"
                message={`Deseja excluir a categoria "${deleteTarget?.nome}"? Esta ação não pode ser desfeita.`}
                confirmLabel="Excluir"
                onConfirm={handleDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </AppLayout>
    );
}
