import React from 'react';
import {
    Alert, Box, Button, Card, CardContent, CardHeader, Chip, Divider, Grid, Typography,
} from '@mui/material';
import ArrowBackRoundedIcon from '@mui/icons-material/ArrowBackRounded';
import PictureAsPdfRoundedIcon from '@mui/icons-material/PictureAsPdfRounded';
import ArticleRoundedIcon from '@mui/icons-material/ArticleRounded';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { PageProps } from '@/types';

interface ContratoFull {
    id: number;
    orcamento_id: number;
    nome_cliente: string;
    documento_cliente: string;
    endereco_instalacao: string;
    potencia_kwp: string;
    qtd_paineis: number;
    qtd_inversores: number;
    modelo_inversor: string;
    consumo_mensal: number;
    geracao_estimada: number;
    garantia_paineis: string;
    garantia_inversores: string;
    valor_total: string;
    formas_pagamento: string;
    clausulas_adicionais?: string;
    status: 'gerado' | 'assinado' | 'cancelado';
    created_at: string;
    orcamento?: { id: number; cliente?: { id: number } };
}

interface Props extends PageProps { contrato: ContratoFull }

const STATUS_COLORS: Record<string, 'default' | 'warning' | 'success' | 'error'> = {
    gerado: 'warning',
    assinado: 'success',
    cancelado: 'error',
};

const STATUS_LABELS: Record<string, string> = {
    gerado: 'Gerado',
    assinado: 'Assinado',
    cancelado: 'Cancelado',
};

function InfoRow({ label, value }: { label: string; value?: React.ReactNode }) {
    return (
        <Box sx={{ py: 1.25, borderBottom: '1px solid', borderColor: 'divider', display: 'flex', gap: 2, '&:last-child': { border: 'none' } }}>
            <Typography variant="body2" color="text.secondary" sx={{ minWidth: 180, fontWeight: 500 }}>{label}</Typography>
            <Typography variant="body2">{value ?? '—'}</Typography>
        </Box>
    );
}

export default function ContratosShow({ contrato, flash }: Props) {
    const fmtMoney = (v: string | number) => Number(v).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

    return (
        <AppLayout>
            <Head title={`Contrato #${contrato.id}`} />

            <PageHeader
                title={`Contrato #${contrato.id}`}
                subtitle={contrato.nome_cliente}
                breadcrumbs={[
                    { label: 'Contratos', href: route('consultor.contratos.index') },
                    { label: `#${contrato.id}` },
                ]}
                action={
                    <Box sx={{ display: 'flex', gap: 1 }}>
                        <Button component={Link} href={route('consultor.contratos.index')} startIcon={<ArrowBackRoundedIcon />} variant="outlined">
                            Voltar
                        </Button>
                        <Button
                            component="a"
                            href={route('consultor.contratos.pdf', contrato.id)}
                            target="_blank"
                            rel="noopener"
                            startIcon={<PictureAsPdfRoundedIcon />}
                            variant="outlined"
                            color="error"
                        >
                            PDF
                        </Button>
                    </Box>
                }
            />

            {flash?.success && <Alert severity="success" sx={{ mb: 3 }}>{flash.success}</Alert>}
            {flash?.error && <Alert severity="error" sx={{ mb: 3 }}>{flash.error}</Alert>}

            <Grid container spacing={3}>
                <Grid size={{ xs: 12, md: 8 }}>
                    <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                        <CardHeader
                            title="Dados do Contrato"
                            titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }}
                            action={<Chip label={STATUS_LABELS[contrato.status] ?? contrato.status} color={STATUS_COLORS[contrato.status] ?? 'default'} size="small" />}
                        />
                        <Divider />
                        <CardContent>
                            <Grid container spacing={0}>
                                <Grid size={{ xs: 12, sm: 6 }}>
                                    <InfoRow label="Cliente" value={contrato.nome_cliente} />
                                    <InfoRow label="CPF/CNPJ" value={contrato.documento_cliente} />
                                    <InfoRow label="Endereço de instalação" value={contrato.endereco_instalacao} />
                                </Grid>
                                <Grid size={{ xs: 12, sm: 6 }}>
                                    <InfoRow label="Potência" value={`${Number(contrato.potencia_kwp).toLocaleString('pt-BR')} kWp`} />
                                    <InfoRow label="Painéis / Inversores" value={`${contrato.qtd_paineis} painéis · ${contrato.qtd_inversores} inversor(es)`} />
                                    <InfoRow label="Modelo do inversor" value={contrato.modelo_inversor} />
                                </Grid>
                            </Grid>
                        </CardContent>
                    </Card>

                    <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                        <CardHeader title="Dados Técnicos" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                        <Divider />
                        <CardContent>
                            <Grid container spacing={0}>
                                <Grid size={{ xs: 12, sm: 6 }}>
                                    <InfoRow label="Consumo médio mensal" value={`${contrato.consumo_mensal} kWh`} />
                                    <InfoRow label="Geração estimada" value={`${contrato.geracao_estimada} kWh/mês`} />
                                </Grid>
                                <Grid size={{ xs: 12, sm: 6 }}>
                                    <InfoRow label="Garantia dos painéis" value={contrato.garantia_paineis} />
                                    <InfoRow label="Garantia dos inversores" value={contrato.garantia_inversores} />
                                </Grid>
                            </Grid>
                        </CardContent>
                    </Card>

                    <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                        <CardHeader title="Pagamento" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                        <Divider />
                        <CardContent>
                            <Typography variant="body2" sx={{ whiteSpace: 'pre-wrap' }}>{contrato.formas_pagamento}</Typography>
                        </CardContent>
                    </Card>

                    {contrato.clausulas_adicionais && (
                        <Card variant="outlined" sx={{ borderRadius: 2 }}>
                            <CardHeader title="Cláusulas Adicionais" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                            <Divider />
                            <CardContent>
                                <Typography variant="body2" sx={{ whiteSpace: 'pre-wrap' }}>{contrato.clausulas_adicionais}</Typography>
                            </CardContent>
                        </Card>
                    )}
                </Grid>

                <Grid size={{ xs: 12, md: 4 }}>
                    <Card variant="outlined" sx={{ mb: 2, borderRadius: 2 }}>
                        <CardContent sx={{ textAlign: 'center', py: 3 }}>
                            <Typography variant="caption" color="text.secondary" sx={{ textTransform: 'uppercase' }}>Valor Total</Typography>
                            <Typography variant="h4" fontWeight={800} color="success.main" sx={{ mt: 0.5 }}>
                                {fmtMoney(contrato.valor_total)}
                            </Typography>
                        </CardContent>
                    </Card>

                    <Card variant="outlined" sx={{ borderRadius: 2 }}>
                        <CardHeader title="Referências" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                        <Divider />
                        <CardContent sx={{ display: 'flex', flexDirection: 'column', gap: 1.5 }}>
                            <Button
                                component={Link}
                                href={route('consultor.orcamentos.show', contrato.orcamento_id)}
                                startIcon={<ArticleRoundedIcon />}
                                variant="outlined"
                                fullWidth
                            >
                                Ver Orçamento #{contrato.orcamento_id}
                            </Button>
                            <Typography variant="caption" color="text.secondary">
                                Gerado em {new Date(contrato.created_at).toLocaleString('pt-BR')}
                            </Typography>
                        </CardContent>
                    </Card>
                </Grid>
            </Grid>
        </AppLayout>
    );
}
