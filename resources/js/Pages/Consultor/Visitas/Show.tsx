import React from 'react';
import { Box, Button, Card, CardContent, CardHeader, Chip, Divider, Typography } from '@mui/material';
import EditRoundedIcon from '@mui/icons-material/EditRounded';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { PageProps } from '@/types';

interface Visita {
    id: number;
    data_agendada: string;
    status: 'agendada' | 'realizada' | 'cancelada';
    anotacoes?: string;
    cliente?: { id: number; tipo_pessoa: string; nome?: string; razao_social?: string; celular?: string; email?: string };
    orcamento?: { id: number; preco_total?: number } | null;
}

interface Props extends PageProps {
    visita: Visita;
}

const STATUS_COLORS: Record<string, 'default' | 'warning' | 'success' | 'error'> = {
    agendada: 'warning',
    realizada: 'success',
    cancelada: 'error',
};

function InfoRow({ label, value }: { label: string; value?: React.ReactNode }) {
    return (
        <Box sx={{ py: 1, borderBottom: '1px solid', borderColor: 'divider', display: 'flex', gap: 2 }}>
            <Typography variant="body2" color="text.secondary" sx={{ minWidth: 150 }}>
                {label}
            </Typography>
            <Typography variant="body2" fontWeight={500}>{value ?? '—'}</Typography>
        </Box>
    );
}

export default function VisitasShow({ visita }: Props) {
    const nomeCliente = visita.cliente?.tipo_pessoa === 'pj' ? visita.cliente?.razao_social : visita.cliente?.nome;
    const titulo = `Visita #${visita.id}`;

    return (
        <AppLayout>
            <Head title={titulo} />

            <PageHeader
                title={titulo}
                breadcrumbs={[
                    { label: 'Visitas', href: route('consultor.visitas.index') },
                    { label: titulo },
                ]}
                action={
                    <Button
                        component={Link}
                        href={route('consultor.visitas.edit', visita.id)}
                        variant="contained"
                        startIcon={<EditRoundedIcon />}
                        size="small"
                    >
                        Editar
                    </Button>
                }
            />

            <Card sx={{ maxWidth: 720 }}>
                <CardHeader title="Dados da visita" />
                <Divider />
                <CardContent>
                    <InfoRow
                        label="Cliente"
                        value={visita.cliente
                            ? <Link href={route('consultor.clientes.show', visita.cliente.id)}>{nomeCliente}</Link>
                            : undefined}
                    />
                    <InfoRow label="Contato" value={visita.cliente?.celular ?? visita.cliente?.email} />
                    <InfoRow
                        label="Orçamento"
                        value={visita.orcamento
                            ? <Link href={route('consultor.orcamentos.show', visita.orcamento.id)}>#{visita.orcamento.id}</Link>
                            : undefined}
                    />
                    <InfoRow label="Data agendada" value={new Date(visita.data_agendada).toLocaleString('pt-BR')} />
                    <InfoRow
                        label="Status"
                        value={<Chip label={visita.status} size="small" color={STATUS_COLORS[visita.status] ?? 'default'} />}
                    />
                    <InfoRow label="Anotações" value={visita.anotacoes} />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
