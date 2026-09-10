import React, { useState } from 'react';
import {
    Box,
    Card,
    Chip,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    Button,
    IconButton,
    InputAdornment,
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
import SearchRoundedIcon from '@mui/icons-material/SearchRounded';
import MapRoundedIcon from '@mui/icons-material/MapRounded';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { PageProps } from '@/types';

interface EstadoRow {
    id: number | null;
    estado: string;
    nome_estado: string;
    margem: number;
}

interface Props extends PageProps {
    estados: EstadoRow[];
}

export default function EstadosIndex({ estados }: Props) {
    const [search, setSearch] = useState('');
    const [editTarget, setEditTarget] = useState<EstadoRow | null>(null);

    const { data, setData, post, processing, errors } = useForm({
        estado: '',
        margem: '0',
    });

    function openEdit(e: EstadoRow) {
        setEditTarget(e);
        setData({ estado: e.estado, margem: String(e.margem) });
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(route('admin.precificacao.estados.store'), {
            onSuccess: () => setEditTarget(null),
        });
    }

    const filtered = estados.filter(
        (e) =>
            e.estado.toLowerCase().includes(search.toLowerCase()) ||
            e.nome_estado.toLowerCase().includes(search.toLowerCase()),
    );

    return (
        <AppLayout>
            <Head title="Precificação por Estado" />

            <PageHeader
                title="Por Estado"
                breadcrumbs={[{ label: 'Precificação' }, { label: 'Por Estado' }]}
            />

            <Card sx={{ mb: 3, p: 2 }}>
                <TextField
                    size="small"
                    placeholder="Filtrar estado..."
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
            </Card>

            <Card>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell>UF</TableCell>
                            <TableCell>Estado</TableCell>
                            <TableCell align="right">Margem Adicional</TableCell>
                            <TableCell align="right">Ações</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {filtered.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={4} align="center" sx={{ py: 6 }}>
                                    <MapRoundedIcon sx={{ fontSize: 48, color: 'text.disabled', mb: 1 }} />
                                    <Typography color="text.secondary">Nenhum estado encontrado</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                        {filtered.map((e) => (
                            <TableRow key={e.estado} hover>
                                <TableCell>
                                    <Chip label={e.estado} size="small" variant="outlined" />
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2">{e.nome_estado}</Typography>
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
                    <DialogTitle>Editar Margem — {editTarget?.nome_estado}</DialogTitle>
                    <DialogContent sx={{ pt: '16px !important' }}>
                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
                            <Chip label={editTarget?.estado} variant="outlined" />
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
