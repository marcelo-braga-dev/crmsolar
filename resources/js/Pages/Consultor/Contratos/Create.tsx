import React from 'react';
import {
    Alert, Box, Button, Card, CardContent, CardHeader, Divider, Grid,
    InputAdornment, TextField, Typography,
} from '@mui/material';
import ArrowBackRoundedIcon from '@mui/icons-material/ArrowBackRounded';
import SaveRoundedIcon from '@mui/icons-material/SaveRounded';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { PageProps } from '@/types';

interface Props extends PageProps {
    orcamento: { id: number; preco_total: number };
    sugestao: {
        nome_cliente?: string;
        documento_cliente?: string;
        endereco_instalacao?: string;
        potencia_kwp?: number;
        consumo_mensal?: number;
        geracao_estimada?: number;
        valor_total?: number;
    };
}

const DO_ORCAMENTO = 'Vem do orçamento aprovado';

export default function ContratosCreate({ orcamento, sugestao }: Props) {
    // Valores que o orçamento aprovado já tem são gravados pelo servidor a partir dele.
    const doOrcamento = (campo: 'potencia_kwp' | 'consumo_mensal' | 'geracao_estimada') => sugestao[campo] != null;

    const { data, setData, post, processing, errors } = useForm({
        orcamento_id: orcamento.id,
        nome_cliente: sugestao.nome_cliente ?? '',
        documento_cliente: sugestao.documento_cliente ?? '',
        endereco_instalacao: sugestao.endereco_instalacao ?? '',
        potencia_kwp: sugestao.potencia_kwp ?? '',
        qtd_paineis: '',
        qtd_inversores: '',
        modelo_inversor: '',
        consumo_mensal: sugestao.consumo_mensal ?? '',
        geracao_estimada: sugestao.geracao_estimada ?? '',
        garantia_paineis: '',
        garantia_inversores: '',
        valor_total: sugestao.valor_total ?? '',
        formas_pagamento: '',
        clausulas_adicionais: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(route('consultor.contratos.store'));
    }

    return (
        <AppLayout>
            <Head title={`Gerar Contrato — Orçamento #${orcamento.id}`} />

            <PageHeader
                title="Gerar Contrato"
                subtitle={`A partir do orçamento #${orcamento.id}`}
                breadcrumbs={[
                    { label: 'Orçamentos', href: route('consultor.orcamentos.index') },
                    { label: `#${orcamento.id}`, href: route('consultor.orcamentos.show', orcamento.id) },
                    { label: 'Gerar Contrato' },
                ]}
                action={
                    <Button
                        component={Link}
                        href={route('consultor.orcamentos.show', orcamento.id)}
                        startIcon={<ArrowBackRoundedIcon />}
                        variant="outlined"
                    >
                        Voltar ao Orçamento
                    </Button>
                }
            />

            <Alert severity="info" sx={{ mb: 3 }}>
                Os campos abaixo vieram do orçamento como sugestão — revise antes de gerar o contrato,
                especialmente quantidade de painéis/inversores e as garantias, que não fazem parte do orçamento.
            </Alert>

            <Box component="form" onSubmit={submit}>
                <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                    <CardHeader title="Cliente e Instalação" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                    <Divider />
                    <CardContent>
                        <Grid container spacing={3}>
                            <Grid size={{ xs: 12, sm: 6 }}>
                                <TextField
                                    label="Nome do contratante *" size="small" fullWidth
                                    value={data.nome_cliente} onChange={(e) => setData('nome_cliente', e.target.value)}
                                    error={Boolean(errors.nome_cliente)} helperText={errors.nome_cliente}
                                />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 6 }}>
                                <TextField
                                    label="CPF/CNPJ *" size="small" fullWidth
                                    value={data.documento_cliente} onChange={(e) => setData('documento_cliente', e.target.value)}
                                    error={Boolean(errors.documento_cliente)} helperText={errors.documento_cliente}
                                />
                            </Grid>
                            <Grid size={12}>
                                <TextField
                                    label="Endereço de instalação *" size="small" fullWidth
                                    value={data.endereco_instalacao} onChange={(e) => setData('endereco_instalacao', e.target.value)}
                                    error={Boolean(errors.endereco_instalacao)} helperText={errors.endereco_instalacao}
                                />
                            </Grid>
                        </Grid>
                    </CardContent>
                </Card>

                <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                    <CardHeader title="Sistema Fotovoltaico" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                    <Divider />
                    <CardContent>
                        <Grid container spacing={3}>
                            <Grid size={{ xs: 12, sm: 4 }}>
                                <TextField
                                    label="Potência (kWp) *" type="number" size="small" fullWidth
                                    value={data.potencia_kwp} onChange={(e) => setData('potencia_kwp', e.target.value)}
                                    inputProps={{ step: 0.001, min: 0 }}
                                    disabled={doOrcamento('potencia_kwp')}
                                    error={Boolean(errors.potencia_kwp)} helperText={errors.potencia_kwp ?? (doOrcamento('potencia_kwp') ? DO_ORCAMENTO : undefined)}
                                />
                            </Grid>
                            <Grid size={{ xs: 6, sm: 4 }}>
                                <TextField
                                    label="Qtd. painéis *" type="number" size="small" fullWidth
                                    value={data.qtd_paineis} onChange={(e) => setData('qtd_paineis', e.target.value)}
                                    inputProps={{ min: 0 }}
                                    error={Boolean(errors.qtd_paineis)} helperText={errors.qtd_paineis}
                                />
                            </Grid>
                            <Grid size={{ xs: 6, sm: 4 }}>
                                <TextField
                                    label="Qtd. inversores *" type="number" size="small" fullWidth
                                    value={data.qtd_inversores} onChange={(e) => setData('qtd_inversores', e.target.value)}
                                    inputProps={{ min: 0 }}
                                    error={Boolean(errors.qtd_inversores)} helperText={errors.qtd_inversores}
                                />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 6 }}>
                                <TextField
                                    label="Modelo do inversor *" size="small" fullWidth
                                    value={data.modelo_inversor} onChange={(e) => setData('modelo_inversor', e.target.value)}
                                    error={Boolean(errors.modelo_inversor)} helperText={errors.modelo_inversor}
                                />
                            </Grid>
                            <Grid size={{ xs: 6, sm: 3 }}>
                                <TextField
                                    label="Consumo médio *" type="number" size="small" fullWidth
                                    value={data.consumo_mensal} onChange={(e) => setData('consumo_mensal', e.target.value)}
                                    InputProps={{ endAdornment: <InputAdornment position="end">kWh</InputAdornment> }}
                                    inputProps={{ min: 0 }}
                                    disabled={doOrcamento('consumo_mensal')}
                                    error={Boolean(errors.consumo_mensal)} helperText={errors.consumo_mensal ?? (doOrcamento('consumo_mensal') ? DO_ORCAMENTO : undefined)}
                                />
                            </Grid>
                            <Grid size={{ xs: 6, sm: 3 }}>
                                <TextField
                                    label="Geração estimada *" type="number" size="small" fullWidth
                                    value={data.geracao_estimada} onChange={(e) => setData('geracao_estimada', e.target.value)}
                                    InputProps={{ endAdornment: <InputAdornment position="end">kWh/mês</InputAdornment> }}
                                    inputProps={{ min: 0 }}
                                    disabled={doOrcamento('geracao_estimada')}
                                    error={Boolean(errors.geracao_estimada)} helperText={errors.geracao_estimada ?? (doOrcamento('geracao_estimada') ? DO_ORCAMENTO : undefined)}
                                />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 6 }}>
                                <TextField
                                    label="Garantia dos painéis *" size="small" fullWidth
                                    placeholder="Ex.: 25 anos de geração, 12 anos de produto"
                                    value={data.garantia_paineis} onChange={(e) => setData('garantia_paineis', e.target.value)}
                                    error={Boolean(errors.garantia_paineis)} helperText={errors.garantia_paineis}
                                />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 6 }}>
                                <TextField
                                    label="Garantia dos inversores *" size="small" fullWidth
                                    placeholder="Ex.: 10 anos"
                                    value={data.garantia_inversores} onChange={(e) => setData('garantia_inversores', e.target.value)}
                                    error={Boolean(errors.garantia_inversores)} helperText={errors.garantia_inversores}
                                />
                            </Grid>
                        </Grid>
                    </CardContent>
                </Card>

                <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                    <CardHeader title="Valor e Pagamento" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                    <Divider />
                    <CardContent>
                        <Grid container spacing={3}>
                            <Grid size={{ xs: 12, sm: 4 }}>
                                <TextField
                                    label="Valor total" type="number" size="small" fullWidth disabled
                                    value={data.valor_total}
                                    InputProps={{ startAdornment: <InputAdornment position="start">R$</InputAdornment> }}
                                    helperText="Valor do orçamento aprovado — para alterar, ajuste os itens do orçamento"
                                />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 8 }}>
                                <TextField
                                    label="Formas de pagamento *" size="small" fullWidth multiline minRows={2}
                                    placeholder="Ex.: Entrada de 30% + saldo em 12x via financiamento Banco X"
                                    value={data.formas_pagamento} onChange={(e) => setData('formas_pagamento', e.target.value)}
                                    error={Boolean(errors.formas_pagamento)} helperText={errors.formas_pagamento}
                                />
                            </Grid>
                            <Grid size={12}>
                                <TextField
                                    label="Cláusulas adicionais" size="small" fullWidth multiline minRows={3}
                                    placeholder="Opcional — condições específicas deste contrato"
                                    value={data.clausulas_adicionais} onChange={(e) => setData('clausulas_adicionais', e.target.value)}
                                    error={Boolean(errors.clausulas_adicionais)} helperText={errors.clausulas_adicionais}
                                />
                            </Grid>
                        </Grid>
                    </CardContent>
                </Card>

                <Box sx={{ display: 'flex', justifyContent: 'flex-end', gap: 1.5 }}>
                    <Typography variant="caption" color="text.secondary" sx={{ alignSelf: 'center', mr: 'auto' }}>
                        Campos com * são obrigatórios.
                    </Typography>
                    <Button component={Link} href={route('consultor.orcamentos.show', orcamento.id)} variant="outlined">
                        Cancelar
                    </Button>
                    <Button type="submit" variant="contained" startIcon={<SaveRoundedIcon />} disabled={processing}>
                        Gerar Contrato
                    </Button>
                </Box>
            </Box>
        </AppLayout>
    );
}
