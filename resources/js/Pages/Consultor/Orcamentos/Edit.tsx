import React, { useState } from 'react';
import {
    Alert, Box, Button, Card, CardContent, CardHeader, Chip,
    Divider, Grid, TextField, Typography,
} from '@mui/material';
import ArrowBackRoundedIcon from '@mui/icons-material/ArrowBackRounded';
import SaveRoundedIcon from '@mui/icons-material/SaveRounded';
import CheckCircleRoundedIcon from '@mui/icons-material/CheckCircleRounded';
import NotesRoundedIcon from '@mui/icons-material/NotesRounded';
import EngineeringRoundedIcon from '@mui/icons-material/EngineeringRounded';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { OrcamentoStatusChip } from '@/Components/UI/StatusChip';
import { PageProps, OrcamentoStatus } from '@/types';

interface OrcamentoEdit {
    id: number;
    status: OrcamentoStatus;
    preco_total: number;
    geracao_estimada: number;
    anotacoes?: string;
    created_at: string;
    cliente?: { id: number; tipo_pessoa: string; nome?: string; razao_social?: string; email?: string };
    cidade?: { cidade: string; estado: string };
    info?: {
        tipo_dimensionamento?: string;
        anotacoes_tecnicas?: string;
        estrutura?: { id: number; nome: string };
        consumo?: number;
    };
    itens: Array<{ id: number; descricao?: string; tipo: string; quantidade: number; preco_venda_total: number }>;
}

interface Props extends PageProps { orcamento: OrcamentoEdit }

export default function OrcamentosEdit({ orcamento, flash }: Props) {
    const nomeCliente = orcamento.cliente?.tipo_pessoa === 'pj'
        ? orcamento.cliente?.razao_social
        : orcamento.cliente?.nome;

    const canEdit = orcamento.status === 'novo';
    const fmtMoney = (v: number) => v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

    const [submitting, setSubmitting] = useState(false);
    const { data, setData, errors } = useForm({
        anotacoes:          orcamento.anotacoes ?? '',
        anotacoes_tecnicas: orcamento.info?.anotacoes_tecnicas ?? '',
    });

    function submit(extra: Record<string, unknown> = {}) {
        setSubmitting(true);
        router.put(
            route('consultor.orcamentos.update', orcamento.id),
            { ...data, ...extra },
            { onFinish: () => setSubmitting(false) },
        );
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        submit();
    }

    function handleSolicitarAprovacao() {
        submit({ status: 'aprovando' });
    }

    return (
        <AppLayout>
            <Head title={`Editar Orçamento #${orcamento.id}`} />

            <PageHeader
                title={`Editar Orçamento #${orcamento.id}`}
                subtitle={nomeCliente ?? 'Cliente não informado'}
                breadcrumbs={[
                    { label: 'Orçamentos', href: route('consultor.orcamentos.index') },
                    { label: `#${orcamento.id}`, href: route('consultor.orcamentos.show', orcamento.id) },
                    { label: 'Editar' },
                ]}
                action={
                    <Box sx={{ display: 'flex', gap: 1 }}>
                        <Button
                            component={Link}
                            href={route('consultor.orcamentos.show', orcamento.id)}
                            startIcon={<ArrowBackRoundedIcon />}
                            variant="outlined"
                        >
                            Voltar
                        </Button>
                        {canEdit && (
                            <Button
                                startIcon={<SaveRoundedIcon />}
                                variant="contained"
                                onClick={handleSubmit}
                                disabled={submitting}
                            >
                                Salvar
                            </Button>
                        )}
                    </Box>
                }
            />

            {flash?.success && <Alert severity="success" sx={{ mb: 3 }}>{flash.success}</Alert>}
            {flash?.error   && <Alert severity="error"   sx={{ mb: 3 }}>{flash.error}</Alert>}

            {!canEdit && (
                <Alert severity="info" sx={{ mb: 3 }}>
                    Este orçamento está com status <strong>{orcamento.status}</strong> e não pode ser editado.
                    Apenas orçamentos com status <strong>Novo</strong> podem ser alterados.
                </Alert>
            )}

            <Box component="form" onSubmit={handleSubmit} noValidate>
                <Grid container spacing={3}>
                    {/* ── Coluna principal ──────────────────────────────── */}
                    <Grid size={{ xs: 12, md: 8 }}>

                        {/* Anotações para o cliente */}
                        <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                            <CardHeader
                                avatar={<NotesRoundedIcon color="primary" />}
                                title="Anotações da Proposta"
                                subheader="Observações visíveis para o cliente na proposta final"
                                titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }}
                                subheaderTypographyProps={{ variant: 'caption' }}
                            />
                            <Divider />
                            <CardContent>
                                <TextField
                                    label="Observações / Notas"
                                    multiline
                                    rows={6}
                                    fullWidth
                                    value={data.anotacoes}
                                    onChange={(e) => setData('anotacoes', e.target.value)}
                                    disabled={!canEdit}
                                    error={Boolean(errors.anotacoes)}
                                    helperText={
                                        errors.anotacoes
                                        ?? 'Condições comerciais, validade da proposta, informações adicionais ao cliente, etc.'
                                    }
                                    inputProps={{ maxLength: 3000 }}
                                    placeholder="Ex: Proposta válida por 30 dias. Prazo de instalação estimado em 15 dias após aprovação..."
                                />
                                <Typography variant="caption" color="text.secondary" sx={{ mt: 0.5, display: 'block' }}>
                                    {data.anotacoes.length}/3000 caracteres
                                </Typography>
                            </CardContent>
                        </Card>

                        {/* Anotações técnicas (internas) */}
                        <Card variant="outlined" sx={{ borderRadius: 2 }}>
                            <CardHeader
                                avatar={<EngineeringRoundedIcon color="action" />}
                                title="Notas Técnicas"
                                subheader="Notas internas — não aparecem na proposta do cliente"
                                titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }}
                                subheaderTypographyProps={{ variant: 'caption' }}
                            />
                            <Divider />
                            <CardContent>
                                <TextField
                                    label="Notas Técnicas Internas"
                                    multiline
                                    rows={5}
                                    fullWidth
                                    value={data.anotacoes_tecnicas}
                                    onChange={(e) => setData('anotacoes_tecnicas', e.target.value)}
                                    disabled={!canEdit}
                                    error={Boolean(errors.anotacoes_tecnicas)}
                                    helperText={
                                        errors.anotacoes_tecnicas
                                        ?? 'Observações de engenharia, requisitos de instalação, detalhes do local, etc.'
                                    }
                                    inputProps={{ maxLength: 3000 }}
                                    placeholder="Ex: Telhado cerâmico com inclinação de 20°. Necessário reforço estrutural na calha leste..."
                                />
                                <Typography variant="caption" color="text.secondary" sx={{ mt: 0.5, display: 'block' }}>
                                    {data.anotacoes_tecnicas.length}/3000 caracteres
                                </Typography>
                            </CardContent>
                        </Card>
                    </Grid>

                    {/* ── Sidebar ───────────────────────────────────────── */}
                    <Grid size={{ xs: 12, md: 4 }}>

                        {/* Resumo do orçamento */}
                        <Card variant="outlined" sx={{ mb: 2, borderRadius: 2 }}>
                            <CardHeader
                                title="Resumo"
                                titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }}
                                action={<OrcamentoStatusChip status={orcamento.status} />}
                            />
                            <Divider />
                            <CardContent sx={{ '& > *:not(:last-child)': { mb: 1.5 } }}>
                                <Box>
                                    <Typography variant="caption" color="text.secondary">Cliente</Typography>
                                    <Typography variant="body2" fontWeight={600}>{nomeCliente ?? '—'}</Typography>
                                </Box>
                                {orcamento.cidade && (
                                    <Box>
                                        <Typography variant="caption" color="text.secondary">Cidade</Typography>
                                        <Typography variant="body2">{orcamento.cidade.cidade} — {orcamento.cidade.estado}</Typography>
                                    </Box>
                                )}
                                <Box>
                                    <Typography variant="caption" color="text.secondary">Valor Total</Typography>
                                    <Typography variant="h6" fontWeight={700} color="success.main">
                                        {fmtMoney(orcamento.preco_total)}
                                    </Typography>
                                </Box>
                                <Box>
                                    <Typography variant="caption" color="text.secondary">Geração Estimada</Typography>
                                    <Typography variant="body2">{orcamento.geracao_estimada} kWh/mês</Typography>
                                </Box>
                                <Box>
                                    <Typography variant="caption" color="text.secondary">Criado em</Typography>
                                    <Typography variant="body2">
                                        {new Date(orcamento.created_at).toLocaleDateString('pt-BR')}
                                    </Typography>
                                </Box>
                            </CardContent>
                        </Card>

                        {/* Itens resumidos */}
                        <Card variant="outlined" sx={{ mb: 2, borderRadius: 2 }}>
                            <CardHeader
                                title={`Itens (${orcamento.itens.length})`}
                                titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }}
                            />
                            <Divider />
                            <CardContent sx={{ p: '12px !important' }}>
                                {orcamento.itens.length === 0 ? (
                                    <Typography variant="body2" color="text.secondary" sx={{ textAlign: 'center', py: 1 }}>
                                        Nenhum item adicionado
                                    </Typography>
                                ) : (
                                    orcamento.itens.map((item) => (
                                        <Box
                                            key={item.id}
                                            sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', py: 1, borderBottom: '1px solid', borderColor: 'divider', '&:last-child': { border: 'none' } }}
                                        >
                                            <Box sx={{ flexGrow: 1, mr: 1 }}>
                                                <Typography variant="caption" fontWeight={600} sx={{ display: 'block' }}>
                                                    {item.descricao ?? item.tipo}
                                                </Typography>
                                                <Chip label={item.tipo} size="small" sx={{ fontSize: '0.65rem', height: 14, mt: 0.2 }} />
                                            </Box>
                                            <Typography variant="caption" fontWeight={700} color="text.secondary" sx={{ flexShrink: 0 }}>
                                                {fmtMoney(item.preco_venda_total)}
                                            </Typography>
                                        </Box>
                                    ))
                                )}
                            </CardContent>
                        </Card>

                        {/* Ações */}
                        {canEdit && (
                            <Card variant="outlined" sx={{ borderRadius: 2 }}>
                                <CardHeader title="Ações" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                                <Divider />
                                <CardContent sx={{ display: 'flex', flexDirection: 'column', gap: 1.5 }}>
                                    <Button
                                        variant="contained"
                                        fullWidth
                                        startIcon={<SaveRoundedIcon />}
                                        onClick={handleSubmit}
                                        disabled={submitting}
                                    >
                                        Salvar Alterações
                                    </Button>
                                    <Button
                                        variant="outlined"
                                        color="success"
                                        fullWidth
                                        startIcon={<CheckCircleRoundedIcon />}
                                        onClick={handleSolicitarAprovacao}
                                        disabled={submitting}
                                    >
                                        Salvar e Solicitar Aprovação
                                    </Button>
                                </CardContent>
                            </Card>
                        )}
                    </Grid>
                </Grid>
            </Box>
        </AppLayout>
    );
}
