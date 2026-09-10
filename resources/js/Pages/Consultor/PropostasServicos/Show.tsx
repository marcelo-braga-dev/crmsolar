import React from 'react';
import {
    Alert, Box, Button, Card, CardContent, CardHeader, Chip,
    Divider, Grid, Typography, alpha,
} from '@mui/material';
import EditRoundedIcon from '@mui/icons-material/EditRounded';
import DeleteRoundedIcon from '@mui/icons-material/DeleteRounded';
import PersonRoundedIcon from '@mui/icons-material/PersonRounded';
import CalendarTodayRoundedIcon from '@mui/icons-material/CalendarTodayRounded';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { PageProps } from '@/types';

type Status = 'rascunho' | 'enviada' | 'aceita' | 'recusada' | 'expirada';

interface Proposta {
    id: number; titulo: string; valor: number; validade: string;
    conteudo: string; observacoes?: string; status: Status;
    created_at: string; updated_at: string;
    cliente?: { id: number; tipo_pessoa: string; nome?: string; razao_social?: string; email?: string; telefone?: string; cpf?: string; cnpj?: string };
}
interface Props extends PageProps { proposta: Proposta }

const STATUS_MAP: Record<Status, { label: string; color: 'default' | 'info' | 'success' | 'error' | 'warning'; desc: string }> = {
    rascunho: { label: 'Rascunho',  color: 'default',  desc: 'Em elaboração — não enviada ao cliente.' },
    enviada:  { label: 'Enviada',   color: 'info',     desc: 'Enviada ao cliente. Aguardando resposta.' },
    aceita:   { label: 'Aceita',    color: 'success',  desc: 'Proposta aceita pelo cliente.' },
    recusada: { label: 'Recusada',  color: 'error',    desc: 'Proposta recusada pelo cliente.' },
    expirada: { label: 'Expirada',  color: 'warning',  desc: 'Prazo de validade encerrado.' },
};

const COR_STATUS: Record<Status, string> = {
    rascunho: '#6366f1', enviada: '#0891B2', aceita: '#10B981', recusada: '#EF4444', expirada: '#F59E0B',
};

export default function PropostasServicosShow({ proposta }: Props) {
    const st     = STATUS_MAP[proposta.status];
    const cor    = COR_STATUS[proposta.status];
    const fmtMoney = (v: number) => v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    const nomeCli  = proposta.cliente
        ? (proposta.cliente.tipo_pessoa === 'pj' ? proposta.cliente.razao_social : proposta.cliente.nome) ?? '—'
        : '—';
    const vencida  = proposta.status === 'enviada' && new Date(proposta.validade) < new Date();

    function confirmDelete() {
        if (!confirm('Excluir esta proposta? Esta ação não pode ser desfeita.')) return;
        router.delete(route('consultor.proposta-servicos.destroy', proposta.id));
    }

    return (
        <AppLayout>
            <Head title={proposta.titulo} />
            <PageHeader
                title={proposta.titulo}
                breadcrumbs={[
                    { label: 'Propostas de Serviços', href: route('consultor.proposta-servicos.index') },
                    { label: `#${proposta.id}` },
                ]}
                action={
                    <Box sx={{ display: 'flex', gap: 1.5 }}>
                        <Button component={Link} href={route('consultor.proposta-servicos.edit', proposta.id)}
                            variant="outlined" startIcon={<EditRoundedIcon />}>
                            Editar
                        </Button>
                        <Button variant="outlined" color="error" startIcon={<DeleteRoundedIcon />} onClick={confirmDelete}>
                            Excluir
                        </Button>
                    </Box>
                }
            />

            <Grid container spacing={3}>
                {/* Conteúdo principal */}
                <Grid size={{ xs: 12, md: 8 }}>
                    {/* Banner de status */}
                    <Alert
                        severity={proposta.status === 'aceita' ? 'success' : proposta.status === 'recusada' || vencida ? 'error' : proposta.status === 'expirada' ? 'warning' : 'info'}
                        sx={{ mb: 3, borderRadius: 2 }}
                    >
                        <strong>{st.label}</strong> — {vencida ? 'Esta proposta expirou em ' + new Date(proposta.validade).toLocaleDateString('pt-BR') + '.' : st.desc}
                    </Alert>

                    {/* Corpo da proposta */}
                    <Card variant="outlined" sx={{ borderRadius: 2 }}>
                        <CardHeader
                            title="Conteúdo da Proposta"
                            titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }}
                        />
                        <Divider />
                        <CardContent>
                            {/* Cabeçalho visual */}
                            <Box sx={{ mb: 3, p: 2.5, borderRadius: 2, bgcolor: alpha(cor, 0.05), border: '1px solid', borderColor: alpha(cor, 0.2) }}>
                                <Typography variant="h5" fontWeight={800} gutterBottom>{proposta.titulo}</Typography>
                                <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap', alignItems: 'center' }}>
                                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.5 }}>
                                        <PersonRoundedIcon fontSize="small" sx={{ color: 'text.secondary' }} />
                                        <Typography variant="body2" color="text.secondary">{nomeCli}</Typography>
                                    </Box>
                                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.5 }}>
                                        <CalendarTodayRoundedIcon fontSize="small" sx={{ color: 'text.secondary' }} />
                                        <Typography variant="body2" color="text.secondary">
                                            Válida até {new Date(proposta.validade + 'T12:00:00').toLocaleDateString('pt-BR', { day: '2-digit', month: 'long', year: 'numeric' })}
                                        </Typography>
                                    </Box>
                                    <Chip label={st.label} color={st.color} size="small" />
                                </Box>
                            </Box>

                            {/* Texto da proposta */}
                            <Box sx={{
                                whiteSpace: 'pre-wrap', fontFamily: 'inherit',
                                lineHeight: 1.8, color: 'text.primary',
                                fontSize: '0.9rem',
                            }}>
                                {proposta.conteudo}
                            </Box>

                            <Divider sx={{ my: 3 }} />

                            {/* Valor em destaque */}
                            <Box sx={{ display: 'flex', justifyContent: 'flex-end', alignItems: 'center', gap: 2 }}>
                                <Typography variant="body1" color="text.secondary">Valor total desta proposta:</Typography>
                                <Typography variant="h4" fontWeight={800} color="success.main">
                                    {fmtMoney(proposta.valor)}
                                </Typography>
                            </Box>
                        </CardContent>
                    </Card>

                    {/* Observações internas */}
                    {proposta.observacoes && (
                        <Card variant="outlined" sx={{ mt: 3, borderRadius: 2, borderColor: 'warning.light', bgcolor: alpha('#F59E0B', 0.03) }}>
                            <CardHeader title="Observações Internas" titleTypographyProps={{ variant: 'subtitle2', color: 'warning.dark' }}
                                subheader="Visível apenas para o consultor" />
                            <Divider />
                            <CardContent>
                                <Typography variant="body2" sx={{ whiteSpace: 'pre-wrap' }}>{proposta.observacoes}</Typography>
                            </CardContent>
                        </Card>
                    )}
                </Grid>

                {/* Sidebar */}
                <Grid size={{ xs: 12, md: 4 }}>
                    {/* Valor */}
                    <Card variant="outlined" sx={{ mb: 3, borderRadius: 2, borderColor: 'success.light' }}>
                        <CardContent>
                            <Typography variant="caption" color="text.secondary" display="block" mb={0.5}>Valor da Proposta</Typography>
                            <Typography variant="h3" fontWeight={800} color="success.main">{fmtMoney(proposta.valor)}</Typography>
                        </CardContent>
                    </Card>

                    {/* Cliente */}
                    <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                        <CardHeader title="Cliente" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                        <Divider />
                        <CardContent>
                            <Typography variant="body1" fontWeight={700} gutterBottom>{nomeCli}</Typography>
                            {proposta.cliente?.email && (
                                <Typography variant="body2" color="text.secondary">{proposta.cliente.email}</Typography>
                            )}
                            {proposta.cliente?.telefone && (
                                <Typography variant="body2" color="text.secondary">{proposta.cliente.telefone}</Typography>
                            )}
                            {proposta.cliente?.cnpj && (
                                <Typography variant="body2" color="text.secondary">CNPJ: {proposta.cliente.cnpj}</Typography>
                            )}
                            {proposta.cliente?.cpf && (
                                <Typography variant="body2" color="text.secondary">CPF: {proposta.cliente.cpf}</Typography>
                            )}
                        </CardContent>
                    </Card>

                    {/* Datas */}
                    <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                        <CardHeader title="Datas" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                        <Divider />
                        <CardContent>
                            {[
                                { label: 'Criada em', value: new Date(proposta.created_at).toLocaleDateString('pt-BR') },
                                { label: 'Atualizada em', value: new Date(proposta.updated_at).toLocaleDateString('pt-BR') },
                                { label: 'Válida até', value: new Date(proposta.validade + 'T12:00:00').toLocaleDateString('pt-BR'), destaque: vencida },
                            ].map(({ label, value, destaque }) => (
                                <Box key={label} sx={{ display: 'flex', justifyContent: 'space-between', py: 0.75 }}>
                                    <Typography variant="body2" color="text.secondary">{label}</Typography>
                                    <Typography variant="body2" fontWeight={600} color={destaque ? 'error.main' : 'text.primary'}>{value}</Typography>
                                </Box>
                            ))}
                        </CardContent>
                    </Card>

                    {/* Ações */}
                    <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1.5 }}>
                        <Button component={Link} href={route('consultor.proposta-servicos.edit', proposta.id)}
                            variant="contained" fullWidth startIcon={<EditRoundedIcon />} sx={{ borderRadius: 2 }}>
                            Editar Proposta
                        </Button>
                        <Button component={Link} href={route('consultor.proposta-servicos.index')}
                            variant="outlined" fullWidth color="inherit" sx={{ borderRadius: 2 }}>
                            Voltar à lista
                        </Button>
                    </Box>
                </Grid>
            </Grid>
        </AppLayout>
    );
}
