import React from 'react';
import {
    Box,
    Button,
    Card,
    CardContent,
    CardHeader,
    Divider,
    FormControl,
    InputLabel,
    MenuItem,
    Select,
    TextField,
    Typography,
} from '@mui/material';
import SaveRoundedIcon from '@mui/icons-material/SaveRounded';
import PersonAddRoundedIcon from '@mui/icons-material/PersonAddRounded';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { LeadStatusChip } from '@/Components/UI/StatusChip';
import { PageProps } from '@/types';

interface Lead {
    id: number;
    nome?: string;
    email?: string;
    telefone?: string;
    cidade?: string;
    estado?: string;
    consumo_mensal?: number;
    origem?: string;
    status: string;
    anotacoes?: string;
    created_at: string;
}

interface Props extends PageProps {
    lead: Lead;
}

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

export default function LeadsShow({ lead }: Props) {
    const { data, setData, put, processing } = useForm({
        status: lead.status,
        anotacoes: lead.anotacoes ?? '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        put(route('consultor.leads.update', lead.id));
    }

    return (
        <AppLayout>
            <Head title={lead.nome ?? 'Lead'} />

            <PageHeader
                title={lead.nome ?? 'Lead'}
                breadcrumbs={[
                    { label: 'Leads', href: route('consultor.leads.index') },
                    { label: lead.nome ?? 'Detalhe' },
                ]}
                action={
                    <Button
                        component={Link}
                        href={route('consultor.clientes.create') + `?nome=${lead.nome ?? ''}&email=${lead.email ?? ''}&telefone=${lead.telefone ?? ''}`}
                        variant="contained"
                        startIcon={<PersonAddRoundedIcon />}
                        size="small"
                    >
                        Converter em Cliente
                    </Button>
                }
            />

            <Box sx={{ display: 'flex', gap: 3, flexWrap: 'wrap', alignItems: 'flex-start' }}>
                <Card sx={{ flex: 2, minWidth: 300 }}>
                    <CardHeader title="Dados do lead" />
                    <Divider />
                    <CardContent>
                        <InfoRow label="Nome" value={lead.nome} />
                        <InfoRow label="E-mail" value={lead.email} />
                        <InfoRow label="Telefone" value={lead.telefone} />
                        <InfoRow label="Cidade" value={[lead.cidade, lead.estado].filter(Boolean).join(' - ')} />
                        <InfoRow label="Consumo mensal" value={lead.consumo_mensal ? `${lead.consumo_mensal} kWh/mês` : undefined} />
                        <InfoRow label="Origem" value={lead.origem} />
                        <InfoRow label="Status" value={<LeadStatusChip status={lead.status} />} />
                        <InfoRow label="Criado em" value={new Date(lead.created_at).toLocaleDateString('pt-BR')} />
                    </CardContent>
                </Card>

                <Card sx={{ flex: 1, minWidth: 260 }} component="form" onSubmit={submit}>
                    <CardHeader title="Atualizar" />
                    <Divider />
                    <CardContent sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
                        <FormControl fullWidth size="small">
                            <InputLabel>Status</InputLabel>
                            <Select
                                value={data.status}
                                label="Status"
                                onChange={(e) => setData('status', e.target.value)}
                            >
                                <MenuItem value="novo">Novo</MenuItem>
                                <MenuItem value="contatado">Contatado</MenuItem>
                                <MenuItem value="encaminhado">Encaminhado</MenuItem>
                                <MenuItem value="convertido">Convertido</MenuItem>
                                <MenuItem value="perdido">Perdido</MenuItem>
                            </Select>
                        </FormControl>
                        <TextField
                            fullWidth size="small" multiline rows={3}
                            label="Anotações"
                            value={data.anotacoes}
                            onChange={(e) => setData('anotacoes', e.target.value)}
                        />
                        <Button type="submit" variant="contained" startIcon={<SaveRoundedIcon />} disabled={processing}>
                            Salvar
                        </Button>
                    </CardContent>
                </Card>
            </Box>
        </AppLayout>
    );
}
