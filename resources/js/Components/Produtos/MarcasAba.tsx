import React, { useMemo, useState } from 'react';
import {
    Avatar,
    Box,
    Button,
    Card,
    Chip,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    IconButton,
    InputAdornment,
    MenuItem,
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
import SearchRoundedIcon from '@mui/icons-material/SearchRounded';
import LabelRoundedIcon from '@mui/icons-material/LabelRounded';
import { router, useForm } from '@inertiajs/react';
import { ConfirmDialog } from '@/Components/UI/ConfirmDialog';

export interface Marca {
    id: number;
    nome: string;
    url_logo?: string | null;
    ativo: boolean;
    produtos_count: number;
    updated_at: string;
}

interface Props {
    marcas: Marca[];
}

type FormState = { nome: string; url_logo: string; ativo: boolean };

/** Aba "Marcas" do Catálogo de produtos. Lista curta: busca e filtro feitos na própria tela. */
export function MarcasAba({ marcas }: Props) {
    const [search, setSearch] = useState('');
    const [ativoFilter, setAtivoFilter] = useState('');

    const [modalOpen, setModalOpen] = useState(false);
    const [editTarget, setEditTarget] = useState<Marca | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<Marca | null>(null);

    const { data, setData, post, put, processing, reset, errors } = useForm<FormState>({
        nome: '',
        url_logo: '',
        ativo: true,
    });

    const visiveis = useMemo(() => {
        const termo = search.trim().toLowerCase();
        return marcas.filter((m) =>
            (!termo || m.nome.toLowerCase().includes(termo))
            && (ativoFilter === '' || m.ativo === (ativoFilter === '1')));
    }, [marcas, search, ativoFilter]);

    function openCreate() {
        setEditTarget(null);
        reset();
        setData({ nome: '', url_logo: '', ativo: true });
        setModalOpen(true);
    }

    function openEdit(m: Marca) {
        setEditTarget(m);
        setData({ nome: m.nome, url_logo: m.url_logo ?? '', ativo: m.ativo });
        setModalOpen(true);
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        const opcoes = { preserveScroll: true, onSuccess: () => setModalOpen(false) };
        if (editTarget) {
            put(route('admin.produtos.marcas.update', editTarget.id), opcoes);
        } else {
            post(route('admin.produtos.marcas.store'), opcoes);
        }
    }

    function handleDelete() {
        if (!deleteTarget) return;
        router.delete(route('admin.produtos.marcas.destroy', deleteTarget.id), {
            preserveScroll: true,
            onSuccess: () => setDeleteTarget(null),
        });
    }

    const initials = (nome: string) => nome.slice(0, 2).toUpperCase();

    return (
        <>
            <Box sx={{ display: 'flex', gap: 2, mb: 2, flexWrap: 'wrap', alignItems: 'center' }}>
                <TextField
                    size="small"
                    placeholder="Buscar marca..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    InputProps={{
                        startAdornment: (
                            <InputAdornment position="start">
                                <SearchRoundedIcon fontSize="small" />
                            </InputAdornment>
                        ),
                    }}
                    sx={{ minWidth: 240 }}
                />
                <TextField
                    select size="small" label="Status" value={ativoFilter}
                    onChange={(e) => setAtivoFilter(e.target.value)}
                    sx={{ minWidth: 140 }}
                >
                    <MenuItem value="">Todas</MenuItem>
                    <MenuItem value="1">Ativas</MenuItem>
                    <MenuItem value="0">Inativas</MenuItem>
                </TextField>
                <Box sx={{ flex: 1 }} />
                <Button variant="contained" startIcon={<AddRoundedIcon />} onClick={openCreate}>
                    Nova Marca
                </Button>
            </Box>

            <Card>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell>Marca</TableCell>
                            <TableCell>Produtos</TableCell>
                            <TableCell>Status</TableCell>
                            <TableCell>Atualizado</TableCell>
                            <TableCell align="right">Ações</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {visiveis.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={5} align="center" sx={{ py: 6 }}>
                                    <LabelRoundedIcon sx={{ fontSize: 48, color: 'text.disabled', mb: 1 }} />
                                    <Typography color="text.secondary">Nenhuma marca encontrada</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                        {visiveis.map((m) => (
                            <TableRow key={m.id} hover>
                                <TableCell>
                                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                                        {m.url_logo ? (
                                            <Avatar src={m.url_logo} sx={{ width: 36, height: 36 }} variant="rounded" />
                                        ) : (
                                            <Avatar sx={{ width: 36, height: 36, fontSize: '0.8rem', bgcolor: 'primary.light' }} variant="rounded">
                                                {initials(m.nome)}
                                            </Avatar>
                                        )}
                                        <Typography variant="body2" fontWeight={600}>{m.nome}</Typography>
                                    </Box>
                                </TableCell>
                                <TableCell>
                                    <Chip label={m.produtos_count} size="small" variant="outlined" />
                                </TableCell>
                                <TableCell>
                                    <Chip
                                        label={m.ativo ? 'Ativa' : 'Inativa'}
                                        size="small"
                                        color={m.ativo ? 'success' : 'default'}
                                        variant={m.ativo ? 'filled' : 'outlined'}
                                    />
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2" color="text.secondary">
                                        {new Date(m.updated_at).toLocaleDateString('pt-BR')}
                                    </Typography>
                                </TableCell>
                                <TableCell align="right">
                                    <Tooltip title="Editar">
                                        <IconButton size="small" onClick={() => openEdit(m)}>
                                            <EditRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                    <Tooltip title="Excluir">
                                        <IconButton size="small" color="error" onClick={() => setDeleteTarget(m)}>
                                            <DeleteRoundedIcon fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
                <Box sx={{ px: 2, py: 1.5 }}>
                    <Typography variant="caption" color="text.secondary">
                        {visiveis.length} de {marcas.length} marcas
                    </Typography>
                </Box>
            </Card>

            <Dialog open={modalOpen} onClose={() => setModalOpen(false)} maxWidth="sm" fullWidth>
                <form onSubmit={handleSubmit}>
                    <DialogTitle>{editTarget ? 'Editar Marca' : 'Nova Marca'}</DialogTitle>
                    <DialogContent sx={{ pt: '16px !important', display: 'flex', flexDirection: 'column', gap: 2 }}>
                        <TextField
                            label="Nome da Marca *"
                            value={data.nome}
                            onChange={(e) => setData('nome', e.target.value)}
                            error={!!errors.nome}
                            helperText={errors.nome}
                            fullWidth
                            autoFocus
                        />
                        <TextField
                            label="URL do Logo"
                            value={data.url_logo}
                            onChange={(e) => setData('url_logo', e.target.value)}
                            error={!!errors.url_logo}
                            helperText={errors.url_logo ?? 'Link direto para imagem (png, svg, jpg)'}
                            fullWidth
                        />
                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                            <Switch checked={data.ativo} onChange={(e) => setData('ativo', e.target.checked)} />
                            <Typography variant="body2">Marca ativa</Typography>
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
                title="Excluir Marca"
                message={`Deseja excluir a marca "${deleteTarget?.nome}"? Esta ação não pode ser desfeita.`}
                confirmLabel="Excluir"
                onConfirm={handleDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </>
    );
}
