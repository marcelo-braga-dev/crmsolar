import React from 'react';
import {
    Box, Button, Card, CardContent, CardHeader, Divider,
    FormControl, Grid, InputLabel, MenuItem, Select, TextField,
} from '@mui/material';
import SaveRoundedIcon from '@mui/icons-material/SaveRounded';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { PageProps } from '@/types';

interface Cliente { id: number; nome?: string; razao_social?: string; tipo_pessoa: string; }

interface Props extends PageProps {
    visita?: {
        id?: number; cliente_id: string; data: string; hora: string; tipo: string;
        status: string; endereco?: string; anotacoes?: string;
    };
    clientes: Cliente[];
    orcamentoId?: number;
}

export default function VisitasForm({ visita, clientes, orcamentoId }: Props) {
    const editing = !!visita?.id;

    const { data, setData, post, put, processing, errors } = useForm({
        cliente_id: String(visita?.cliente_id ?? ''),
        orcamento_id: String(orcamentoId ?? ''),
        data: visita?.data ?? '',
        hora: visita?.hora ?? '09:00',
        tipo: visita?.tipo ?? 'tecnica',
        status: visita?.status ?? 'agendada',
        endereco: visita?.endereco ?? '',
        anotacoes: visita?.anotacoes ?? '',
    });

    const nomeCliente = (c: Cliente) => c.tipo_pessoa === 'pj' ? c.razao_social : c.nome;

    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (editing) {
            put(route('consultor.visitas.update', visita!.id));
        } else {
            post(route('consultor.visitas.store'));
        }
    }

    return (
        <AppLayout>
            <Head title={editing ? 'Editar Visita' : 'Agendar Visita'} />
            <PageHeader
                title={editing ? 'Editar Visita' : 'Agendar Visita'}
                breadcrumbs={[
                    { label: 'Visitas', href: route('consultor.visitas.index') },
                    { label: editing ? 'Editar' : 'Agendar' },
                ]}
            />

            <Box component="form" onSubmit={submit}>
                <Card variant="outlined" sx={{ borderRadius: 2 }}>
                    <CardHeader title="Dados da Visita" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                    <Divider />
                    <CardContent>
                        <Grid container spacing={3}>
                            <Grid size={{ xs: 12, md: 6 }}>
                                <FormControl fullWidth size="small" error={!!errors.cliente_id}>
                                    <InputLabel>Cliente *</InputLabel>
                                    <Select value={data.cliente_id} label="Cliente *" onChange={(e) => setData('cliente_id', e.target.value)}>
                                        <MenuItem value="">Selecione...</MenuItem>
                                        {clientes.map((c) => (
                                            <MenuItem key={c.id} value={c.id}>{nomeCliente(c)}</MenuItem>
                                        ))}
                                    </Select>
                                </FormControl>
                            </Grid>
                            <Grid size={{ xs: 12, md: 3 }}>
                                <FormControl fullWidth size="small">
                                    <InputLabel>Tipo</InputLabel>
                                    <Select value={data.tipo} label="Tipo" onChange={(e) => setData('tipo', e.target.value)}>
                                        <MenuItem value="tecnica">Vistoria Técnica</MenuItem>
                                        <MenuItem value="comercial">Visita Comercial</MenuItem>
                                        <MenuItem value="instalacao">Acompanhamento Instalação</MenuItem>
                                    </Select>
                                </FormControl>
                            </Grid>
                            <Grid size={{ xs: 12, md: 3 }}>
                                <FormControl fullWidth size="small">
                                    <InputLabel>Status</InputLabel>
                                    <Select value={data.status} label="Status" onChange={(e) => setData('status', e.target.value)}>
                                        <MenuItem value="agendada">Agendada</MenuItem>
                                        <MenuItem value="realizada">Realizada</MenuItem>
                                        <MenuItem value="cancelada">Cancelada</MenuItem>
                                    </Select>
                                </FormControl>
                            </Grid>
                            <Grid size={{ xs: 12, md: 4 }}>
                                <TextField
                                    fullWidth size="small" label="Data *" type="date"
                                    value={data.data} onChange={(e) => setData('data', e.target.value)}
                                    InputLabelProps={{ shrink: true }}
                                    error={!!errors.data} helperText={errors.data}
                                />
                            </Grid>
                            <Grid size={{ xs: 12, md: 3 }}>
                                <TextField
                                    fullWidth size="small" label="Hora *" type="time"
                                    value={data.hora} onChange={(e) => setData('hora', e.target.value)}
                                    InputLabelProps={{ shrink: true }}
                                />
                            </Grid>
                            <Grid size={{ xs: 12 }}>
                                <TextField
                                    fullWidth size="small" label="Endereço da visita"
                                    value={data.endereco} onChange={(e) => setData('endereco', e.target.value)}
                                    helperText="Se diferente do endereço do cliente"
                                />
                            </Grid>
                            <Grid size={{ xs: 12 }}>
                                <TextField
                                    fullWidth multiline rows={4} size="small" label="Anotações"
                                    value={data.anotacoes} onChange={(e) => setData('anotacoes', e.target.value)}
                                />
                            </Grid>
                        </Grid>
                    </CardContent>
                </Card>

                <Box sx={{ display: 'flex', gap: 2, justifyContent: 'flex-end', mt: 3 }}>
                    <Button component={Link} href={route('consultor.visitas.index')} variant="outlined">Cancelar</Button>
                    <Button type="submit" variant="contained" startIcon={<SaveRoundedIcon />} disabled={processing}>
                        {editing ? 'Salvar' : 'Agendar'}
                    </Button>
                </Box>
            </Box>
        </AppLayout>
    );
}
