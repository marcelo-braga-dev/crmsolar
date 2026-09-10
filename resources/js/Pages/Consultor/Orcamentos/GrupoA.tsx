import React, { useState } from 'react';
import {
    Alert, Autocomplete, Box, Button, Card, CardContent, CardHeader, Chip, Divider,
    FormControl, FormControlLabel, FormHelperText, Grid, InputAdornment, InputLabel,
    LinearProgress, MenuItem, Radio, RadioGroup, Select, TextField, ToggleButton,
    ToggleButtonGroup, Tooltip, Typography, alpha, Checkbox, FormGroup,
} from '@mui/material';
import CalculateRoundedIcon from '@mui/icons-material/CalculateRounded';
import SaveRoundedIcon from '@mui/icons-material/SaveRounded';
import BoltRoundedIcon from '@mui/icons-material/BoltRounded';
import InfoOutlinedIcon from '@mui/icons-material/InfoOutlined';
import WarningAmberRoundedIcon from '@mui/icons-material/WarningAmberRounded';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { AnaliseEconomica, EconomiaKit } from '@/Components/UI/AnaliseEconomica';
import { PageProps } from '@/types';

const COR_GRUPO = '#7C3AED';
const MESES: Record<string, string> = { jan:'Jan',fev:'Fev',mar:'Mar',abr:'Abr',mai:'Mai',jun:'Jun',jul:'Jul',ago:'Ago',set:'Set',out:'Out',nov:'Nov',dez:'Dez' };

interface Estrutura { id: number; nome: string }
interface ClienteOpt { id: number; tipo_pessoa: string; nome?: string; razao_social?: string; cidade_id?: number; cidade?: { id: number; cidade: string; estado: string; sigla?: string } }
interface ConcessionariaOpt { id: number; nome: string; estado: string; tarifa_ponta?: string; tarifa_fora_ponta?: string }
interface AnaliseMes { hsp: number; geracao: number; consumo: number; cobertura: number }
interface CalcResult { potencia_calculada: number; hsp: number; pr: any; kits: EconomiaKit[]; analise_mensal: { meses: Record<string, AnaliseMes>; pior_mes: string; pior_cobertura: number } | null }

interface GrupoInfo { label: string; tensao: string; modalidades: string[] }
interface Props extends PageProps {
    estruturas: Estrutura[];
    clientes: ClienteOpt[];
    concessionarias: ConcessionariaOpt[];
    grupo: string;
    grupo_info: GrupoInfo;
}

export default function GrupoA({ estruturas, clientes, concessionarias, grupo, grupo_info }: Props) {
    const [calcResult, setCalcResult] = useState<CalcResult | null>(null);
    const [calculando, setCalculando] = useState(false);
    const [calcError, setCalcError] = useState<string | null>(null);
    const [kitSel, setKitSel] = useState<EconomiaKit | null>(null);
    const [categoriasFiltro, setCategoriasFiltro] = useState(new Set(['ongrid', 'hibrido', 'microinversor']));

    const { data, setData, post, processing, errors } = useForm({
        cliente_id: '', estrutura_id: '', tensao: '220', orientacao: 'norte', qtd_kits: '1',
        kit_id: '', geracao_estimada: '0', preco_venda: '0',
        anotacoes: '', anotacoes_tecnicas: '',
        grupo_tarifario: grupo,
        modalidade_tarifaria: 'THS_VERDE',
        consumo_ponta: '',
        consumo_fora_ponta: '',
        tarifa_kwh_ponta: '',
        tarifa_kwh_fp: '',
        demanda_ponta_kw: '',
        demanda_fora_ponta_kw: '',
        tarifa_demanda_ponta: '',
        tarifa_demanda_fp: '',
        percentual_autoconsumo: '80',
        subgrupo_tensao: '',
        valor_conta_mensal: '',
    });

    const clienteSel = clientes.find((c) => String(c.id) === data.cliente_id) ?? null;
    const fmtMoney = (v: number) => v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    const isAzul = data.modalidade_tarifaria === 'THS_AZUL';

    function selecionarConcessionaria(id: string) {
        const c = concessionarias.find((x) => String(x.id) === id);
        if (!c) return;
        setData((prev: typeof data) => ({
            ...prev,
            tarifa_kwh_ponta: c.tarifa_ponta ?? prev.tarifa_kwh_ponta,
            tarifa_kwh_fp: c.tarifa_fora_ponta ?? prev.tarifa_kwh_fp,
        }));
    }

    async function calcular() {
        if (!data.cliente_id || !data.estrutura_id || !data.consumo_ponta || !data.consumo_fora_ponta || !data.tarifa_kwh_ponta || !data.tarifa_kwh_fp) {
            setCalcError('Preencha todos os campos obrigatórios.'); return;
        }
        setCalculando(true); setCalcError(null); setCalcResult(null); setKitSel(null);
        try {
            const payload: Record<string, any> = {
                cliente_id: Number(data.cliente_id),
                estrutura_id: Number(data.estrutura_id),
                tensao: Number(data.tensao),
                qtd_kits: Number(data.qtd_kits),
                orientacao: data.orientacao,
                grupo_tarifario: data.grupo_tarifario,
                modalidade_tarifaria: data.modalidade_tarifaria,
                consumo_ponta: Number(data.consumo_ponta),
                consumo_fora_ponta: Number(data.consumo_fora_ponta),
                tarifa_kwh_ponta: Number(data.tarifa_kwh_ponta),
                tarifa_kwh_fp: Number(data.tarifa_kwh_fp),
                demanda_ponta_kw: data.demanda_ponta_kw ? Number(data.demanda_ponta_kw) : undefined,
                tarifa_demanda_ponta: data.tarifa_demanda_ponta ? Number(data.tarifa_demanda_ponta) : undefined,
                percentual_autoconsumo: Number(data.percentual_autoconsumo),
                subgrupo_tensao: data.subgrupo_tensao || undefined,
                valor_conta_mensal: data.valor_conta_mensal ? Number(data.valor_conta_mensal) : undefined,
                categorias: Array.from(categoriasFiltro),
            };
            if (isAzul) {
                payload.demanda_fora_ponta_kw = data.demanda_fora_ponta_kw ? Number(data.demanda_fora_ponta_kw) : undefined;
                payload.tarifa_demanda_fp = data.tarifa_demanda_fp ? Number(data.tarifa_demanda_fp) : undefined;
            }
            const res = await window.axios.post(route('consultor.grupo.a.calcular', { grupo: data.grupo_tarifario }), payload);
            setCalcResult(res.data);
        } catch (e: any) {
            setCalcError(e?.response?.data?.error ?? 'Erro ao calcular.');
        } finally { setCalculando(false); }
    }

    function selKit(kit: EconomiaKit) {
        setKitSel(kit);
        setData({ ...data, kit_id: String(kit.id), geracao_estimada: String(kit.geracao), preco_venda: String(kit.preco_venda) });
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(route('consultor.grupo.a.store', { grupo: data.grupo_tarifario }));
    }

    return (
        <AppLayout>
            <Head title={`Orçamento ${grupo} — ${grupo_info.label}`} />
            <PageHeader
                title={`Orçamento ${grupo} — ${grupo_info.label}`}
                subtitle={`Grupo A · ${grupo_info.tensao} · Tarifa Horo-Sazonal`}
                breadcrumbs={[
                    { label: 'Orçamentos', href: route('consultor.orcamentos.index') },
                    { label: 'Novo', href: route('consultor.orcamentos.selecionar_grupo') },
                    { label: grupo },
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

                        {/* 2. Instalação e Grupo Tarifário */}
                        <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                            <CardHeader title="2. Instalação e Grupo Tarifário" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                            <Divider />
                            <CardContent>
                                <Grid container spacing={3}>
                                    {/* Grupo tarifário (display-only) */}
                                    <Grid size={{ xs: 12 }}>
                                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, p: 1.5, borderRadius: 1.5, bgcolor: alpha(COR_GRUPO, 0.06), border: '1px solid', borderColor: COR_GRUPO }}>
                                            <BoltRoundedIcon sx={{ color: COR_GRUPO }} />
                                            <Box>
                                                <Typography variant="caption" color="text.secondary">Grupo tarifário</Typography>
                                                <Typography variant="body1" fontWeight={700} color={COR_GRUPO}>{grupo} — {grupo_info.label}</Typography>
                                                <Typography variant="caption" color="text.secondary">{grupo_info.tensao}</Typography>
                                            </Box>
                                        </Box>
                                        {/* Hidden field value carried in form state via data.grupo_tarifario */}
                                    </Grid>
                                    <Grid size={{ xs: 12 }}>
                                        <Typography variant="body2" color="text.secondary" mb={1}>Modalidade tarifária</Typography>
                                        <ToggleButtonGroup
                                            exclusive
                                            value={data.modalidade_tarifaria}
                                            onChange={(_, v) => v && setData({ ...data, modalidade_tarifaria: v })}
                                            size="small"
                                        >
                                            <ToggleButton value="THS_VERDE" sx={{ '&.Mui-selected': { bgcolor: alpha('#10B981', 0.12), color: '#10B981', borderColor: '#10B981' } }}>
                                                THS Verde — Demanda única
                                            </ToggleButton>
                                            <ToggleButton value="THS_AZUL" sx={{ '&.Mui-selected': { bgcolor: alpha('#2563EB', 0.12), color: '#2563EB', borderColor: '#2563EB' } }}>
                                                THS Azul — Demanda diferenciada
                                            </ToggleButton>
                                        </ToggleButtonGroup>
                                        <Typography variant="caption" color="text.secondary" display="block" mt={0.5}>
                                            {isAzul
                                                ? 'Demandas diferenciadas ponta e fora-ponta · Tarifas de consumo diferenciadas por período.'
                                                : 'Demanda única contratada · Tarifas de consumo diferenciadas por período ponta/fora-ponta.'}
                                        </Typography>
                                    </Grid>
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
                                        <Box>
                                            <Typography variant="body2" color="text.secondary" mb={1}>Tensão da rede</Typography>
                                            <ToggleButtonGroup exclusive value={data.tensao} onChange={(_, v) => v && setData({ ...data, tensao: v })} size="small">
                                                <ToggleButton value="220">220V</ToggleButton>
                                                <ToggleButton value="380">380V</ToggleButton>
                                            </ToggleButtonGroup>
                                        </Box>
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 4 }}>
                                        <TextField fullWidth size="small" label="Subgrupo / nível de tensão" value={data.subgrupo_tensao} onChange={(e) => setData({ ...data, subgrupo_tensao: e.target.value })}
                                            helperText='Opcional, ex: "13,8 kV"' />
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 4 }}>
                                        <TextField fullWidth size="small" label="Nº de kits" type="number" inputProps={{ min: 1, max: 30 }} value={data.qtd_kits} onChange={(e) => setData({ ...data, qtd_kits: e.target.value })} helperText="Até 30 inversores" />
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <TextField fullWidth size="small" label="Autoconsumo (%)" type="number" inputProps={{ min: 10, max: 100, step: 5 }}
                                            InputProps={{ endAdornment: <InputAdornment position="end">%</InputAdornment> }}
                                            value={data.percentual_autoconsumo} onChange={(e) => setData({ ...data, percentual_autoconsumo: e.target.value })}
                                            helperText="% da geração consumida no local (padrão: 80%)" />
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <TextField fullWidth size="small" label="Valor atual da conta" type="number" inputProps={{ min: 0 }}
                                            InputProps={{ startAdornment: <InputAdornment position="start">R$</InputAdornment> }}
                                            value={data.valor_conta_mensal} onChange={(e) => setData({ ...data, valor_conta_mensal: e.target.value })}
                                            helperText="Opcional — para análise" />
                                    </Grid>
                                </Grid>
                            </CardContent>
                        </Card>

                        {/* 3. Consumo e Tarifas */}
                        <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                            <CardHeader title="3. Consumo e Tarifas Horo-Sazonais" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }}
                                subheader="Dados da conta de energia — períodos ponta e fora-ponta" />
                            <Divider />
                            <CardContent>
                                <Grid container spacing={3}>
                                    {/* Concessionária */}
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <FormControl fullWidth size="small">
                                            <InputLabel>Concessionária (opcional)</InputLabel>
                                            <Select value="" label="Concessionária (opcional)" onChange={(e) => selecionarConcessionaria(e.target.value)}>
                                                {concessionarias.map((c) => <MenuItem key={c.id} value={c.id}>{c.estado} — {c.nome}</MenuItem>)}
                                            </Select>
                                        </FormControl>
                                        <Typography variant="caption" color="text.secondary">Pré-preenche tarifas de ponta e fora-ponta</Typography>
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 6 }} />

                                    {/* Consumo */}
                                    <Grid size={{ xs: 12 }}>
                                        <Typography variant="body2" fontWeight={600} color="text.secondary" mb={0.5}>Consumo mensal</Typography>
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <TextField fullWidth size="small" label="Consumo na ponta *" type="number" inputProps={{ min: 0, step: 10 }}
                                            InputProps={{ endAdornment: <InputAdornment position="end">kWh/mês</InputAdornment> }}
                                            value={data.consumo_ponta} onChange={(e) => setData({ ...data, consumo_ponta: e.target.value })}
                                            error={!!errors.consumo_ponta} helperText={errors.consumo_ponta ?? 'Período de pico (18h–21h)'} />
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <TextField fullWidth size="small" label="Consumo fora-ponta *" type="number" inputProps={{ min: 0, step: 10 }}
                                            InputProps={{ endAdornment: <InputAdornment position="end">kWh/mês</InputAdornment> }}
                                            value={data.consumo_fora_ponta} onChange={(e) => setData({ ...data, consumo_fora_ponta: e.target.value })}
                                            error={!!errors.consumo_fora_ponta} helperText={errors.consumo_fora_ponta ?? 'Demais horários'} />
                                    </Grid>

                                    {/* Tarifas de consumo */}
                                    <Grid size={{ xs: 12 }}>
                                        <Typography variant="body2" fontWeight={600} color="text.secondary" mb={0.5}>Tarifas de consumo</Typography>
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <TextField fullWidth size="small" label="Tarifa ponta *" type="number" inputProps={{ step: 0.001, min: 0 }}
                                            InputProps={{ startAdornment: <InputAdornment position="start">R$</InputAdornment>, endAdornment: <InputAdornment position="end">/kWh</InputAdornment> }}
                                            value={data.tarifa_kwh_ponta} onChange={(e) => setData({ ...data, tarifa_kwh_ponta: e.target.value })}
                                            error={!!errors.tarifa_kwh_ponta} helperText={errors.tarifa_kwh_ponta ?? 'TUSD + TE período ponta'} />
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <TextField fullWidth size="small" label="Tarifa fora-ponta *" type="number" inputProps={{ step: 0.001, min: 0 }}
                                            InputProps={{ startAdornment: <InputAdornment position="start">R$</InputAdornment>, endAdornment: <InputAdornment position="end">/kWh</InputAdornment> }}
                                            value={data.tarifa_kwh_fp} onChange={(e) => setData({ ...data, tarifa_kwh_fp: e.target.value })}
                                            error={!!errors.tarifa_kwh_fp} helperText={errors.tarifa_kwh_fp ?? 'TUSD + TE fora-ponta'} />
                                    </Grid>

                                    {/* Demandas */}
                                    <Grid size={{ xs: 12 }}>
                                        <Typography variant="body2" fontWeight={600} color="text.secondary" mb={0.5}>Demanda contratada</Typography>
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: isAzul ? 6 : 12 }}>
                                        <TextField fullWidth size="small" label={isAzul ? 'Demanda de ponta (kW)' : 'Demanda contratada (kW)'} type="number" inputProps={{ min: 0, step: 1 }}
                                            InputProps={{ endAdornment: <InputAdornment position="end">kW</InputAdornment> }}
                                            value={data.demanda_ponta_kw} onChange={(e) => setData({ ...data, demanda_ponta_kw: e.target.value })}
                                            helperText={isAzul ? 'Demanda de ponta contratada' : 'Demanda única contratada'} />
                                    </Grid>
                                    {isAzul && (
                                        <Grid size={{ xs: 12, sm: 6 }}>
                                            <TextField fullWidth size="small" label="Demanda fora-ponta (kW)" type="number" inputProps={{ min: 0, step: 1 }}
                                                InputProps={{ endAdornment: <InputAdornment position="end">kW</InputAdornment> }}
                                                value={data.demanda_fora_ponta_kw} onChange={(e) => setData({ ...data, demanda_fora_ponta_kw: e.target.value })}
                                                helperText="Demanda fora-ponta contratada (THS Azul)" />
                                        </Grid>
                                    )}

                                    {/* Tarifas de demanda */}
                                    <Grid size={{ xs: 12 }}>
                                        <Typography variant="body2" fontWeight={600} color="text.secondary" mb={0.5}>Tarifas de demanda</Typography>
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: isAzul ? 6 : 12 }}>
                                        <TextField fullWidth size="small" label={isAzul ? 'Tarifa demanda ponta' : 'Tarifa de demanda'} type="number" inputProps={{ step: 0.01, min: 0 }}
                                            InputProps={{ startAdornment: <InputAdornment position="start">R$</InputAdornment>, endAdornment: <InputAdornment position="end">/kW</InputAdornment> }}
                                            value={data.tarifa_demanda_ponta} onChange={(e) => setData({ ...data, tarifa_demanda_ponta: e.target.value })}
                                            helperText={isAzul ? 'Tarifa de demanda ponta' : 'Tarifa de demanda contratada'} />
                                    </Grid>
                                    {isAzul && (
                                        <Grid size={{ xs: 12, sm: 6 }}>
                                            <TextField fullWidth size="small" label="Tarifa demanda fora-ponta" type="number" inputProps={{ step: 0.01, min: 0 }}
                                                InputProps={{ startAdornment: <InputAdornment position="start">R$</InputAdornment>, endAdornment: <InputAdornment position="end">/kW</InputAdornment> }}
                                                value={data.tarifa_demanda_fp} onChange={(e) => setData({ ...data, tarifa_demanda_fp: e.target.value })}
                                                helperText="Tarifa de demanda fora-ponta (THS Azul)" />
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
                                            ['hibrido', 'Híbrido', '#2563EB'],
                                            ['microinversor', 'Microinversor', '#0891B2'],
                                        ].map(([cat, label, cor]) => (
                                            <FormControlLabel key={cat} control={<Checkbox size="small" checked={categoriasFiltro.has(cat)} onChange={() => setCategoriasFiltro(prev => { const n = new Set(prev); n.size === 1 && n.has(cat) ? null : n.has(cat) ? n.delete(cat) : n.add(cat); return new Set(n); })} sx={{ color: cor, '&.Mui-checked': { color: cor } }} />}
                                            label={<Typography variant="caption" fontWeight={categoriasFiltro.has(cat) ? 700 : 400} sx={{ color: categoriasFiltro.has(cat) ? cor : 'text.secondary' }}>{label}</Typography>} sx={{ mx: 0 }} />
                                        ))}
                                    </FormGroup>
                                </Box>

                                <Button variant="contained" fullWidth onClick={calcular}
                                    disabled={calculando || !data.cliente_id || !data.estrutura_id || !data.consumo_ponta || !data.consumo_fora_ponta || !data.tarifa_kwh_ponta || !data.tarifa_kwh_fp}
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
                                            <Chip label={data.modalidade_tarifaria} size="small" sx={{ bgcolor: isAzul ? alpha('#2563EB', 0.12) : alpha('#10B981', 0.12), color: isAzul ? '#2563EB' : '#10B981', fontWeight: 600 }} />
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

                                                                                    {/* Redução de demanda */}
                                                                                    {kit.reducao_demanda && kit.reducao_demanda.reducao_kw > 0 && (
                                                                                        <Box sx={{ mt: 1, p: 1, borderRadius: 1, bgcolor: alpha(COR_GRUPO, 0.06), border: '1px solid', borderColor: alpha(COR_GRUPO, 0.3) }}>
                                                                                            <Typography variant="caption" color="text.secondary" display="block">Redução de demanda</Typography>
                                                                                            <Typography variant="caption" fontWeight={700} color={COR_GRUPO}>
                                                                                                −{kit.reducao_demanda.reducao_kw.toFixed(1)} kW ({kit.reducao_demanda.percentual_reducao}%)
                                                                                            </Typography>
                                                                                            {kit.economia_demanda_mes != null && kit.economia_demanda_mes > 0 && (
                                                                                                <Typography variant="caption" color="text.secondary" display="block">
                                                                                                    Economia demanda: {kit.economia_demanda_mes.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}/mês
                                                                                                </Typography>
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
                                <BoltRoundedIcon sx={{ color: COR_GRUPO }} />
                                <Typography variant="subtitle1" fontWeight={700} color={COR_GRUPO}>{grupo} — {grupo_info.label}</Typography>
                            </Box>} />
                            <Divider />
                            <CardContent>
                                <Typography variant="caption" color="text.secondary">
                                    Tarifação horo-sazonal com componente de demanda. THS Verde: demanda única + consumo diferenciado. THS Azul: demanda diferenciada ponta/fora-ponta.
                                </Typography>
                                <Box sx={{ mt: 1.5 }}>
                                    <Chip label={data.modalidade_tarifaria === 'THS_AZUL' ? 'THS Azul' : 'THS Verde'} size="small"
                                        sx={{ bgcolor: data.modalidade_tarifaria === 'THS_AZUL' ? alpha('#2563EB', 0.12) : alpha('#10B981', 0.12), color: data.modalidade_tarifaria === 'THS_AZUL' ? '#2563EB' : '#10B981', fontWeight: 600, fontSize: '0.7rem' }} />
                                </Box>
                                {data.subgrupo_tensao && (
                                    <Box sx={{ mt: 1 }}>
                                        <Typography variant="caption" color="text.secondary">Nível de tensão: </Typography>
                                        <Typography variant="caption" fontWeight={600}>{data.subgrupo_tensao}</Typography>
                                    </Box>
                                )}
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
                                        { label: 'Economia/mês', value: (kitSel.economia_total_mes ?? kitSel.economia?.economia_mensal ?? 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) },
                                        ...(kitSel.economia_demanda_mes != null && kitSel.economia_demanda_mes > 0 ? [{ label: 'Econ. demanda/mês', value: kitSel.economia_demanda_mes.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }] : []),
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
                            {processing ? 'Salvando...' : `Salvar Orçamento ${grupo}`}
                        </Button>
                        {!kitSel && <Typography variant="caption" color="text.secondary" display="block" textAlign="center" mt={1}>Calcule e selecione um kit para continuar</Typography>}
                    </Grid>
                </Grid>
            </Box>
        </AppLayout>
    );
}
