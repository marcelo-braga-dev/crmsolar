import React, { useState } from 'react';
import {
    Alert, Autocomplete, Box, Button, Card, CardContent, CardHeader, Chip, Divider,
    FormControl, FormControlLabel, FormHelperText, Grid, InputAdornment, InputLabel,
    LinearProgress, MenuItem, Radio, RadioGroup, Select, TextField, ToggleButton,
    ToggleButtonGroup, Tooltip, Typography, alpha, Checkbox, FormGroup, Slider,
} from '@mui/material';
import CalculateRoundedIcon from '@mui/icons-material/CalculateRounded';
import SaveRoundedIcon from '@mui/icons-material/SaveRounded';
import StorefrontRoundedIcon from '@mui/icons-material/StorefrontRounded';
import InfoOutlinedIcon from '@mui/icons-material/InfoOutlined';
import WarningAmberRoundedIcon from '@mui/icons-material/WarningAmberRounded';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { AnaliseEconomica, EconomiaKit } from '@/Components/UI/AnaliseEconomica';
import { PageProps } from '@/types';

const COR_GRUPO = '#D97706';
const MESES: Record<string, string> = { jan:'Jan',fev:'Fev',mar:'Mar',abr:'Abr',mai:'Mai',jun:'Jun',jul:'Jul',ago:'Ago',set:'Set',out:'Out',nov:'Nov',dez:'Dez' };

interface Estrutura { id: number; nome: string }
interface ClienteOpt { id: number; tipo_pessoa: string; nome?: string; razao_social?: string; cidade_id?: number; cidade?: { id: number; cidade: string; estado: string; sigla?: string } }
interface ConcessionariaOpt { id: number; nome: string; estado: string; tarifa_convencional?: string }
interface AnaliseMes { hsp: number; geracao: number; consumo: number; cobertura: number }
interface CalcResult { potencia_calculada: number; hsp: number; pr: any; kits: EconomiaKit[]; analise_mensal: { meses: Record<string, AnaliseMes>; pior_mes: string; pior_cobertura: number } | null }
interface Props extends PageProps { estruturas: Estrutura[]; clientes: ClienteOpt[]; concessionarias: ConcessionariaOpt[] }

export default function GrupoB3({ estruturas, clientes, concessionarias }: Props) {
    const [calcResult, setCalcResult] = useState<CalcResult | null>(null);
    const [calculando, setCalculando] = useState(false);
    const [calcError, setCalcError] = useState<string | null>(null);
    const [kitSel, setKitSel] = useState<EconomiaKit | null>(null);
    const [categoriasFiltro, setCategoriasFiltro] = useState(new Set(['ongrid', 'hibrido', 'microinversor']));

    const { data, setData, post, processing, errors } = useForm({
        cliente_id: '', estrutura_id: '', tensao: '220', orientacao: 'norte', qtd_kits: '1',
        fases: 'bifasico', consumo: '', tarifa_kwh: '', valor_conta_mensal: '',
        objetivo_percentual: '100', kit_id: '',
        anotacoes: '', anotacoes_tecnicas: '',
        horario_funcionamento: '8',
        percentual_autoconsumo: '80',
    });

    const clienteSel = clientes.find((c) => String(c.id) === data.cliente_id) ?? null;
    const fmtMoney = (v: number) => v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    const disponibilidade: Record<string, number> = { bifasico: 50, trifasico: 100 };

    function selecionarConcessionaria(id: string) {
        const c = concessionarias.find((x) => String(x.id) === id);
        if (c?.tarifa_convencional) setData('tarifa_kwh', String(c.tarifa_convencional));
    }

    async function calcular() {
        if (!data.cliente_id || !data.estrutura_id || !data.consumo || !data.tarifa_kwh) {
            setCalcError('Preencha todos os campos obrigatórios.'); return;
        }
        setCalculando(true); setCalcError(null); setCalcResult(null); setKitSel(null);
        try {
            const res = await window.axios.post(route('consultor.grupo.b3.calcular'), {
                cliente_id: Number(data.cliente_id), estrutura_id: Number(data.estrutura_id),
                tensao: Number(data.tensao), qtd_kits: Number(data.qtd_kits),
                orientacao: data.orientacao, fases: data.fases,
                consumo: Number(data.consumo), tarifa_kwh: Number(data.tarifa_kwh),
                valor_conta_mensal: data.valor_conta_mensal ? Number(data.valor_conta_mensal) : undefined,
                objetivo_percentual: Number(data.objetivo_percentual),
                horario_funcionamento: Number(data.horario_funcionamento),
                percentual_autoconsumo: Number(data.percentual_autoconsumo),
                categorias: Array.from(categoriasFiltro),
            });
            setCalcResult(res.data);
        } catch (e: any) {
            setCalcError(e?.response?.data?.error ?? 'Erro ao calcular.');
        } finally { setCalculando(false); }
    }

    function selKit(kit: EconomiaKit) {
        setKitSel(kit);
        setData('kit_id', String(kit.id));
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(route('consultor.grupo.b3.store'));
    }

    return (
        <AppLayout>
            <Head title="Orçamento B3 — Comercial BT" />
            <PageHeader
                title="Orçamento B3 — Comercial BT"
                subtitle="Grupo B3 · Baixa Tensão · Tarifa Comercial"
                breadcrumbs={[
                    { label: 'Orçamentos', href: route('consultor.orcamentos.index') },
                    { label: 'Novo', href: route('consultor.orcamentos.selecionar_grupo') },
                    { label: 'B3 Comercial' },
                ]}
            />

            <Box component="form" onSubmit={submit}>
                <Grid container spacing={3}>
                    <Grid size={{ xs: 12, md: 8 }}>
                        {/* 1. Cliente */}
                        <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                            <CardHeader title="1. Cliente" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                            <Divider />
                            <CardContent>
                                <Autocomplete
                                    options={clientes}
                                    getOptionLabel={(c) => c.tipo_pessoa === 'pj' ? (c.razao_social ?? '') : (c.nome ?? '')}
                                    value={clienteSel} onChange={(_, v) => setData({ ...data, cliente_id: v ? String(v.id) : '' })}
                                    renderInput={(p) => <TextField {...p} label="Selecionar cliente *" size="small" error={!!errors.cliente_id} helperText={errors.cliente_id} />}
                                    renderOption={(p, c) => (
                                        <Box component="li" {...p} key={c.id}>
                                            <Box>
                                                <Typography variant="body2">{c.tipo_pessoa === 'pj' ? c.razao_social : c.nome}</Typography>
                                                {c.cidade && <Typography variant="caption" color="text.secondary">{c.cidade.cidade} — {c.cidade.estado}</Typography>}
                                            </Box>
                                        </Box>
                                    )} noOptionsText="Nenhum cliente"
                                />
                                {clienteSel && !clienteSel.cidade && <Alert severity="warning" sx={{ mt: 1.5 }}>Cliente sem cidade cadastrada. Edite antes de continuar.</Alert>}
                            </CardContent>
                        </Card>

                        {/* 2. Instalação */}
                        <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                            <CardHeader title="2. Dados da Instalação" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                            <Divider />
                            <CardContent>
                                <Grid container spacing={3}>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <FormControl fullWidth size="small" error={!!errors.estrutura_id}>
                                            <InputLabel>Estrutura do telhado *</InputLabel>
                                            <Select value={data.estrutura_id} label="Estrutura do telhado *" onChange={(e) => setData({ ...data, estrutura_id: e.target.value })}>
                                                {estruturas.map((e) => <MenuItem key={e.id} value={e.id}>{e.nome}</MenuItem>)}
                                            </Select>
                                        </FormControl>
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <FormControl fullWidth size="small">
                                            <InputLabel>Orientação dos painéis</InputLabel>
                                            <Select value={data.orientacao} label="Orientação dos painéis" onChange={(e) => setData({ ...data, orientacao: e.target.value })}>
                                                <MenuItem value="norte">Norte (0% perda)</MenuItem>
                                                <MenuItem value="nordeste_noroeste">NE / NO (−5%)</MenuItem>
                                                <MenuItem value="leste_oeste">Leste / Oeste (−12%)</MenuItem>
                                                <MenuItem value="sudeste_sudoeste">SE / SO (−18%)</MenuItem>
                                                <MenuItem value="sul">Sul (−25%)</MenuItem>
                                            </Select>
                                        </FormControl>
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 4 }}>
                                        <FormControl fullWidth size="small">
                                            <InputLabel>Tipo de ligação *</InputLabel>
                                            <Select value={data.fases} label="Tipo de ligação *" onChange={(e) => setData({ ...data, fases: e.target.value })}>
                                                <MenuItem value="bifasico">Bifásico (custo disp.: 50 kWh)</MenuItem>
                                                <MenuItem value="trifasico">Trifásico (custo disp.: 100 kWh)</MenuItem>
                                            </Select>
                                        </FormControl>
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 4 }}>
                                        <Box>
                                            <Typography variant="body2" color="text.secondary" mb={1}>Tensão da rede</Typography>
                                            <ToggleButtonGroup exclusive value={data.tensao} onChange={(_, v) => v && setData({ ...data, tensao: v })} size="small">
                                                <ToggleButton value="127">127V</ToggleButton>
                                                <ToggleButton value="220">220V</ToggleButton>
                                                <ToggleButton value="380">380V</ToggleButton>
                                            </ToggleButtonGroup>
                                        </Box>
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 4 }}>
                                        <TextField fullWidth size="small" label="Nº de kits" type="number" inputProps={{ min: 1, max: 10 }} value={data.qtd_kits} onChange={(e) => setData({ ...data, qtd_kits: e.target.value })} helperText="Para mais de 1 inversor" />
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <FormControl fullWidth size="small">
                                            <InputLabel>Horário de funcionamento</InputLabel>
                                            <Select value={data.horario_funcionamento} label="Horário de funcionamento" onChange={(e) => setData({ ...data, horario_funcionamento: e.target.value })}>
                                                {[6, 8, 10, 12, 14, 16, 18, 20, 24].map((h) => (
                                                    <MenuItem key={h} value={String(h)}>{h} horas/dia</MenuItem>
                                                ))}
                                            </Select>
                                            <FormHelperText>Horas de operação por dia</FormHelperText>
                                        </FormControl>
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <Typography variant="body2" color="text.secondary" mb={0.5}>
                                            Autoconsumo: <strong>{data.percentual_autoconsumo}%</strong>
                                        </Typography>
                                        <Slider
                                            value={Number(data.percentual_autoconsumo)}
                                            onChange={(_, v) => setData({ ...data, percentual_autoconsumo: String(v) })}
                                            min={10} max={100} step={5}
                                            marks={[{ value: 10, label: '10%' }, { value: 50, label: '50%' }, { value: 100, label: '100%' }]}
                                            sx={{ color: COR_GRUPO }}
                                        />
                                        <Typography variant="caption" color="text.secondary">
                                            % da geração consumida no próprio estabelecimento durante o horário de funcionamento. O restante é injetado na rede (crédito de energia).
                                        </Typography>
                                    </Grid>
                                </Grid>
                            </CardContent>
                        </Card>

                        {/* 3. Consumo e Tarifa */}
                        <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                            <CardHeader title="3. Consumo e Tarifa" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }}
                                subheader="Dados da conta de energia do cliente" />
                            <Divider />
                            <CardContent>
                                <Grid container spacing={3}>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <FormControl fullWidth size="small">
                                            <InputLabel>Concessionária (opcional)</InputLabel>
                                            <Select value="" label="Concessionária (opcional)" onChange={(e) => selecionarConcessionaria(e.target.value)}>
                                                {concessionarias.map((c) => <MenuItem key={c.id} value={c.id}>{c.estado} — {c.nome}</MenuItem>)}
                                            </Select>
                                        </FormControl>
                                        <Typography variant="caption" color="text.secondary">Selecione para pré-preencher a tarifa</Typography>
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 3 }}>
                                        <TextField fullWidth size="small" label="Tarifa de energia *" type="number" inputProps={{ step: 0.001, min: 0.01 }}
                                            InputProps={{ startAdornment: <InputAdornment position="start">R$</InputAdornment>, endAdornment: <InputAdornment position="end">/kWh</InputAdornment> }}
                                            value={data.tarifa_kwh} onChange={(e) => setData({ ...data, tarifa_kwh: e.target.value })}
                                            error={!!errors.tarifa_kwh} helperText={errors.tarifa_kwh ?? 'TUSD + TE + encargos'} />
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 3 }}>
                                        <TextField fullWidth size="small" label="Valor atual da conta" type="number" inputProps={{ min: 0 }}
                                            InputProps={{ startAdornment: <InputAdornment position="start">R$</InputAdornment> }}
                                            value={data.valor_conta_mensal} onChange={(e) => setData({ ...data, valor_conta_mensal: e.target.value })}
                                            helperText="Opcional — para análise" />
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 4 }}>
                                        <TextField fullWidth size="small" label="Consumo médio mensal *" type="number" inputProps={{ min: 1, step: 10 }}
                                            InputProps={{ endAdornment: <InputAdornment position="end">kWh/mês</InputAdornment> }}
                                            value={data.consumo} onChange={(e) => setData({ ...data, consumo: e.target.value })}
                                            helperText="Média dos últimos 12 meses" />
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 4 }}>
                                        <FormControl fullWidth size="small">
                                            <InputLabel>Objetivo do sistema *</InputLabel>
                                            <Select value={data.objetivo_percentual} label="Objetivo do sistema *" onChange={(e) => setData({ ...data, objetivo_percentual: e.target.value })}>
                                                <MenuItem value="50">50% — Reduzir metade da conta</MenuItem>
                                                <MenuItem value="75">75% — Reduzir 3/4 da conta</MenuItem>
                                                <MenuItem value="100">100% — Zerar o consumo</MenuItem>
                                            </Select>
                                        </FormControl>
                                    </Grid>
                                    {data.fases && disponibilidade[data.fases] != null && (
                                        <Grid size={{ xs: 12, sm: 4 }}>
                                            <Box sx={{ p: 1.5, borderRadius: 1.5, bgcolor: alpha(COR_GRUPO, 0.06), border: '1px solid', borderColor: COR_GRUPO }}>
                                                <Typography variant="caption" color="text.secondary">Custo de disponibilidade</Typography>
                                                <Typography variant="body2" fontWeight={700} color={COR_GRUPO}>
                                                    {disponibilidade[data.fases]} kWh/mês
                                                </Typography>
                                                <Typography variant="caption" color="text.secondary">
                                                    ≈ {data.tarifa_kwh ? `R$ ${(disponibilidade[data.fases] * Number(data.tarifa_kwh)).toFixed(2)}` : '—'} — sempre cobrado
                                                </Typography>
                                            </Box>
                                        </Grid>
                                    )}
                                </Grid>
                            </CardContent>
                        </Card>

                        {/* 4. Dimensionamento e Kits */}
                        <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                            <CardHeader title="4. Dimensionamento e Kits" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                            <Divider />
                            <CardContent>
                                <Box sx={{ mb: 2 }}>
                                    <Typography variant="body2" color="text.secondary" mb={0.5}>Filtrar por tipo de sistema</Typography>
                                    <FormGroup row sx={{ gap: 0.5 }}>
                                        {[
                                            ['ongrid', 'On-Grid', COR_GRUPO],
                                            ['hibrido', 'Híbrido', '#7C3AED'],
                                            ['microinversor', 'Microinversor', '#0891B2'],
                                        ].map(([cat, label, cor]) => (
                                            <FormControlLabel key={cat} control={<Checkbox size="small" checked={categoriasFiltro.has(cat)} onChange={() => setCategoriasFiltro(prev => { const n = new Set(prev); n.size === 1 && n.has(cat) ? null : n.has(cat) ? n.delete(cat) : n.add(cat); return new Set(n); })} sx={{ color: cor, '&.Mui-checked': { color: cor } }} />}
                                            label={<Typography variant="caption" fontWeight={categoriasFiltro.has(cat) ? 700 : 400} sx={{ color: categoriasFiltro.has(cat) ? cor : 'text.secondary' }}>{label}</Typography>} sx={{ mx: 0 }} />
                                        ))}
                                    </FormGroup>
                                </Box>

                                <Button variant="contained" fullWidth onClick={calcular} disabled={calculando || !data.cliente_id || !data.estrutura_id || !data.consumo || !data.tarifa_kwh}
                                    startIcon={calculando ? undefined : <CalculateRoundedIcon />} sx={{ height: 44, bgcolor: COR_GRUPO }}>
                                    {calculando ? 'Calculando...' : 'Calcular Dimensionamento'}
                                </Button>

                                {calcError && <Alert severity="warning" sx={{ mt: 2 }}>{calcError}</Alert>}

                                {calcResult && (
                                    <Box sx={{ mt: 2.5 }}>
                                        <Box sx={{ display: 'flex', gap: 1.5, flexWrap: 'wrap', mb: 2 }}>
                                            <Chip label={`${calcResult.potencia_calculada} kWp`} size="small" sx={{ bgcolor: COR_GRUPO, color: '#fff' }} />
                                            <Chip label={`HSP: ${calcResult.hsp} kWh/m²/dia`} variant="outlined" size="small" />
                                            <Tooltip title={`PR sistema: ${calcResult.pr.pr_sistema}% · Orientação: ${calcResult.pr.fator_orientacao}% · Margem: ${calcResult.pr.margem_seguranca}%`}>
                                                <Chip icon={<InfoOutlinedIcon fontSize="small" />} label={`PR total: ${calcResult.pr.pr_total}%`} variant="outlined" size="small" sx={{ cursor: 'help' }} />
                                            </Tooltip>
                                        </Box>

                                        {calcResult.analise_mensal?.pior_cobertura != null && calcResult.analise_mensal.pior_cobertura < 100 && (
                                            <Alert severity="warning" icon={<WarningAmberRoundedIcon />} sx={{ mb: 1.5, py: 0.5 }}>
                                                Em <strong>{MESES[calcResult.analise_mensal.pior_mes]}</strong> o sistema cobrirá <strong>{calcResult.analise_mensal.pior_cobertura}%</strong> do consumo.
                                            </Alert>
                                        )}

                                        {calcResult.analise_mensal && (
                                            <Grid container spacing={0.5} sx={{ mb: 2 }}>
                                                {Object.entries(calcResult.analise_mensal.meses).map(([mes, m]) => (
                                                    <Grid key={mes} size={{ xs: 2, sm: 1 }}>
                                                        <Tooltip title={`${MESES[mes]}: ${m.geracao} kWh · ${m.cobertura}%`}>
                                                            <Box sx={{ textAlign: 'center', cursor: 'help' }}>
                                                                <Typography variant="caption" color="text.secondary" sx={{ fontSize: '0.62rem' }}>{MESES[mes]}</Typography>
                                                                <LinearProgress variant="determinate" value={Math.min(m.cobertura, 130)}
                                                                    sx={{ height: 24, borderRadius: 1, my: 0.3, bgcolor: alpha(m.cobertura >= 100 ? '#10B981' : '#F59E0B', 0.15), '& .MuiLinearProgress-bar': { bgcolor: m.cobertura >= 100 ? '#10B981' : '#F59E0B', borderRadius: 1 } }} />
                                                                <Typography variant="caption" fontWeight={600} sx={{ fontSize: '0.6rem', color: m.cobertura >= 100 ? 'success.main' : 'warning.main' }}>{m.cobertura}%</Typography>
                                                            </Box>
                                                        </Tooltip>
                                                    </Grid>
                                                ))}
                                            </Grid>
                                        )}

                                        <Typography variant="subtitle2" gutterBottom>Selecione um kit</Typography>
                                        <RadioGroup value={kitSel ? String(kitSel.id) : ''} onChange={(e) => { const k = calcResult.kits.find((x) => String(x.id) === e.target.value); if (k) selKit(k); }}>
                                            <Grid container spacing={1.5}>
                                                {calcResult.kits.map((kit) => {
                                                    const sel = kitSel?.id === kit.id;
                                                    return (
                                                        <Grid key={kit.id} size={{ xs: 12 }}>
                                                            <Box onClick={() => selKit(kit)} sx={{ border: '1.5px solid', borderRadius: 2, p: 2, cursor: 'pointer', borderColor: sel ? COR_GRUPO : 'divider', bgcolor: sel ? alpha(COR_GRUPO, 0.04) : 'transparent', '&:hover': { borderColor: COR_GRUPO } }}>
                                                                <FormControlLabel value={String(kit.id)} control={<Radio size="small" />} sx={{ m: 0, width: '100%', alignItems: 'flex-start' }}
                                                                    label={
                                                                        <Box sx={{ width: '100%' }}>
                                                                            <Grid container spacing={2} alignItems="flex-start">
                                                                                <Grid size={{ xs: 12, md: 5 }}>
                                                                                    <Typography variant="body2" fontWeight={700}>{kit.nome}</Typography>
                                                                                    <Box sx={{ display: 'flex', gap: 1, mt: 0.5, flexWrap: 'wrap' }}>
                                                                                        <Chip label={`${kit.potencia_kwp} kWp`} size="small" sx={{ height: 18, fontSize: '0.7rem' }} />
                                                                                        <Chip label={`${kit.geracao} kWh/mês`} size="small" variant="outlined" sx={{ height: 18, fontSize: '0.7rem' }} />
                                                                                        {kit.fornecedor && <Typography variant="caption" color="text.secondary">{kit.fornecedor}</Typography>}
                                                                                    </Box>
                                                                                    {/* Autoconsumo vs injeção */}
                                                                                    {(kit.economia?.geracao_autoconsumo != null || kit.economia?.geracao_injetada != null) && (
                                                                                        <Box sx={{ mt: 1, display: 'flex', gap: 1.5, flexWrap: 'wrap' }}>
                                                                                            {kit.economia?.geracao_autoconsumo != null && (
                                                                                                <Box>
                                                                                                    <Typography variant="caption" color="text.secondary" display="block">Autoconsumo</Typography>
                                                                                                    <Typography variant="caption" fontWeight={700} color={COR_GRUPO}>{kit.economia.geracao_autoconsumo} kWh/mês</Typography>
                                                                                                </Box>
                                                                                            )}
                                                                                            {kit.economia?.geracao_injetada != null && (
                                                                                                <Box>
                                                                                                    <Typography variant="caption" color="text.secondary" display="block">Injetado na rede</Typography>
                                                                                                    <Typography variant="caption" fontWeight={700} color="text.secondary">{kit.economia.geracao_injetada} kWh/mês</Typography>
                                                                                                </Box>
                                                                                            )}
                                                                                        </Box>
                                                                                    )}
                                                                                    <Typography variant="h6" fontWeight={700} color="success.main" sx={{ mt: 1 }}>{kit.preco_venda.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}</Typography>
                                                                                </Grid>
                                                                                <Grid size={{ xs: 12, md: 7 }}>
                                                                                    <AnaliseEconomica kit={kit} corGrupo={COR_GRUPO} />
                                                                                </Grid>
                                                                            </Grid>
                                                                        </Box>
                                                                    }
                                                                />
                                                            </Box>
                                                        </Grid>
                                                    );
                                                })}
                                            </Grid>
                                        </RadioGroup>
                                    </Box>
                                )}
                            </CardContent>
                        </Card>
                    </Grid>

                    {/* Sidebar */}
                    <Grid size={{ xs: 12, md: 4 }}>
                        <Card variant="outlined" sx={{ mb: 3, borderRadius: 2, borderColor: COR_GRUPO }}>
                            <CardHeader title={<Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                                <StorefrontRoundedIcon sx={{ color: COR_GRUPO }} />
                                <Typography variant="subtitle1" fontWeight={700} color={COR_GRUPO}>B3 Comercial BT</Typography>
                            </Box>} />
                            <Divider />
                            <CardContent>
                                <Typography variant="caption" color="text.secondary">
                                    Tarifa comercial em baixa tensão · Análise de autoconsumo vs. injeção na rede · Quanto mais horas de funcionamento, maior o autoconsumo da geração.
                                </Typography>
                                <Box sx={{ mt: 1.5, display: 'flex', gap: 0.5, flexWrap: 'wrap' }}>
                                    <Chip label="On-Grid" size="small" sx={{ bgcolor: alpha(COR_GRUPO, 0.12), color: COR_GRUPO, fontWeight: 600, fontSize: '0.7rem' }} />
                                    <Chip label="Híbrido" size="small" sx={{ bgcolor: alpha('#7C3AED', 0.12), color: '#7C3AED', fontWeight: 600, fontSize: '0.7rem' }} />
                                    <Chip label="Microinversor" size="small" sx={{ bgcolor: alpha('#0891B2', 0.12), color: '#0891B2', fontWeight: 600, fontSize: '0.7rem' }} />
                                </Box>
                            </CardContent>
                        </Card>

                        <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                            <CardHeader title="Anotações" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                            <Divider />
                            <CardContent sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
                                <TextField fullWidth multiline rows={3} size="small" label="Para o cliente" value={data.anotacoes} onChange={(e) => setData({ ...data, anotacoes: e.target.value })} />
                                <TextField fullWidth multiline rows={2} size="small" label="Técnicas" value={data.anotacoes_tecnicas} onChange={(e) => setData({ ...data, anotacoes_tecnicas: e.target.value })} />
                            </CardContent>
                        </Card>

                        {kitSel && (
                            <Card variant="outlined" sx={{ mb: 3, borderRadius: 2, borderColor: 'success.light' }}>
                                <CardHeader title="Resumo" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                                <Divider />
                                <CardContent>
                                    {[
                                        { label: 'Kit', value: kitSel.nome },
                                        { label: 'Potência', value: `${kitSel.potencia_kwp} kWp` },
                                        { label: 'Geração', value: `${kitSel.geracao} kWh/mês` },
                                        { label: 'Economia/mês', value: (kitSel.economia?.economia_mensal ?? 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) },
                                        { label: 'Payback', value: kitSel.payback_simples ? `${kitSel.payback_simples} anos` : '—' },
                                    ].map(({ label, value }) => (
                                        <Box key={label} sx={{ display: 'flex', justifyContent: 'space-between', py: 0.75 }}>
                                            <Typography variant="body2" color="text.secondary">{label}</Typography>
                                            <Typography variant="body2" fontWeight={500} sx={{ textAlign: 'right', maxWidth: '60%' }}>{value}</Typography>
                                        </Box>
                                    ))}
                                    <Divider sx={{ my: 1.5 }} />
                                    <Box sx={{ display: 'flex', justifyContent: 'space-between' }}>
                                        <Typography variant="body2" color="text.secondary">Valor total</Typography>
                                        <Typography variant="h6" fontWeight={700} color="success.main">{kitSel.preco_venda.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}</Typography>
                                    </Box>
                                </CardContent>
                            </Card>
                        )}

                        <Button type="submit" variant="contained" fullWidth size="large" disabled={processing || !kitSel}
                            startIcon={<SaveRoundedIcon />} sx={{ bgcolor: COR_GRUPO }}>
                            {processing ? 'Salvando...' : 'Salvar Orçamento B3'}
                        </Button>
                        {!kitSel && <Typography variant="caption" color="text.secondary" display="block" textAlign="center" mt={1}>Calcule e selecione um kit para continuar</Typography>}
                    </Grid>
                </Grid>
            </Box>
        </AppLayout>
    );
}
