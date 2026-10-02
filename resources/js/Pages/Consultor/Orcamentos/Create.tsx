import React, { useState } from 'react';
import {
    Alert, Autocomplete, Box, Button, Card, CardContent, CardHeader, Chip,
    CircularProgress, Divider, FormControl, FormControlLabel, FormHelperText,
    Grid, InputAdornment, InputLabel, LinearProgress, MenuItem, Radio, RadioGroup,
    Select, TextField, ToggleButton, ToggleButtonGroup, Tooltip, Typography, alpha,
    Checkbox, FormGroup,
} from '@mui/material';
import CalculateRoundedIcon from '@mui/icons-material/CalculateRounded';
import SaveRoundedIcon from '@mui/icons-material/SaveRounded';
import ElectricBoltRoundedIcon from '@mui/icons-material/ElectricBoltRounded';
import InfoOutlinedIcon from '@mui/icons-material/InfoOutlined';
import WarningAmberRoundedIcon from '@mui/icons-material/WarningAmberRounded';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { PageProps } from '@/types';

interface Estrutura { id: number; nome: string }
interface ClienteOption {
    id: number; tipo_pessoa: 'pf' | 'pj'; nome?: string; razao_social?: string;
    cidade_id?: number; cidade?: { id: number; cidade: string; estado: string; sigla?: string };
}
type CategoriaKit = 'ongrid' | 'offgrid' | 'hibrido' | 'bomba' | 'microinversor';

const CATEGORIAS_CONFIG: Record<CategoriaKit, { label: string; color: string; bg: string }> = {
    ongrid:       { label: 'On-Grid',       color: '#2563EB', bg: '#EFF6FF' },
    offgrid:      { label: 'Off-Grid',      color: '#D97706', bg: '#FFFBEB' },
    hibrido:      { label: 'Híbrido',       color: '#7C3AED', bg: '#F5F3FF' },
    microinversor:{ label: 'Microinversor', color: '#0891B2', bg: '#ECFEFF' },
    bomba:        { label: 'Bomba Solar',   color: '#059669', bg: '#ECFDF5' },
};

const TODAS_CATEGORIAS = Object.keys(CATEGORIAS_CONFIG) as CategoriaKit[];

interface KitResult {
    id: number; nome: string; modelo?: string; categoria: CategoriaKit; potencia_kwp: number;
    fornecedor?: string; geracao: number; preco_custo: number; preco_venda: number; margem_total: number;
}
interface PR {
    pr_sistema: number; fator_orientacao: number; pr_total: number;
    margem_seguranca: number; perdas_sistema: number; perdas_orientacao: number; perdas_totais: number;
}
interface AnaliseMes { hsp: number; geracao: number; consumo: number; cobertura: number; }
interface AnaliseMensal {
    meses: Record<string, AnaliseMes>;
    pior_mes: string; pior_cobertura: number;
    melhor_mes: string; melhor_cobertura: number;
    geracao_anual: number; consumo_anual: number;
}
interface CalcResult {
    potencia_calculada: number; hsp: number; pr: PR; kits: KitResult[]; analise_mensal: AnaliseMensal | null;
}
interface Concessionaria { id: number; nome: string; estado: string; tarifa_ponta: string; tarifa_fora_ponta: string; }

interface Props extends PageProps {
    tipo: 'convencional' | 'demanda';
    estruturas: Estrutura[];
    clientes: ClienteOption[];
    concessionarias?: Concessionaria[];
}

const GRUPOS_CONVENCIONAL = [
    { value: 'B1', label: 'B1 — Residencial' },
    { value: 'B2', label: 'B2 — Rural' },
    { value: 'B3', label: 'B3 — Comercial / Industrial' },
];

const GRUPOS_DEMANDA = [
    { value: 'A4', label: 'A4 — 2,3 a 25 kV' },
    { value: 'A3a', label: 'A3a — 30 a 44 kV' },
    { value: 'A3', label: 'A3 — 69 kV' },
    { value: 'A2', label: 'A2 — 88 a 138 kV' },
    { value: 'A1', label: 'A1 — 230 kV ou mais' },
];

const MESES_LABEL: Record<string, string> = {
    jan: 'Jan', fev: 'Fev', mar: 'Mar', abr: 'Abr', mai: 'Mai', jun: 'Jun',
    jul: 'Jul', ago: 'Ago', set: 'Set', out: 'Out', nov: 'Nov', dez: 'Dez',
};

export default function OrcamentosCreate({ tipo, estruturas, clientes, concessionarias = [] }: Props) {
    const [calcResult, setCalcResult]         = useState<CalcResult | null>(null);
    const [calculando, setCalculando]         = useState(false);
    const [calcError, setCalcError]           = useState<string | null>(null);
    const [kitSelecionado, setKitSelecionado] = useState<KitResult | null>(null);
    const [modoEntrada, setModoEntrada]       = useState<'kwh' | 'kwp'>('kwh');
    // Categorias selecionadas — todas ativas por padrão
    const [categoriasFiltro, setCategoriasFiltro] = useState<Set<CategoriaKit>>(new Set(TODAS_CATEGORIAS));

    function toggleCategoria(cat: CategoriaKit) {
        setCategoriasFiltro(prev => {
            const next = new Set(prev);
            if (next.has(cat)) {
                // Não permite desmarcar todas
                if (next.size === 1) return prev;
                next.delete(cat);
            } else {
                next.add(cat);
            }
            return next;
        });
    }

    const isDemanda = tipo === 'demanda';

    const { data, setData, post, processing, errors } = useForm({
        cliente_id: '', estrutura_id: '', tensao: '220', orientacao: 'norte', qtd_kits: '1',
        consumo: '', consumo_ponta: '', consumo_fora_ponta: '', concessionaria_id: '',
        kit_id: '', anotacoes: '', anotacoes_tecnicas: '',
        grupo_tarifario: isDemanda ? 'A4' : 'B1',
    });

    const clienteSelecionado = clientes.find((c) => String(c.id) === String(data.cliente_id)) ?? null;
    const fmtMoney = (v: number) => v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

    async function calcular() {
        const baseOk = data.cliente_id && data.estrutura_id;
        const consumoOk = isDemanda
            ? (data.consumo_ponta && data.consumo_fora_ponta && data.concessionaria_id)
            : data.consumo;

        if (!baseOk || !consumoOk) {
            setCalcError('Preencha todos os campos obrigatórios antes de calcular.');
            return;
        }

        setCalculando(true);
        setCalcError(null);
        setCalcResult(null);
        setKitSelecionado(null);
        setData('kit_id', '');

        try {
            const url = isDemanda
                ? route('consultor.dimensionamento.demanda.buscar_kits')
                : route('consultor.dimensionamento.buscar_kits');

            let payload: Record<string, unknown> = {
                cliente_id:   Number(data.cliente_id),
                estrutura_id: Number(data.estrutura_id),
                tensao:       Number(data.tensao),
                qtd_kits:     Number(data.qtd_kits),
                orientacao:   data.orientacao,
                categorias:   Array.from(categoriasFiltro),
            };

            if (isDemanda) {
                payload = {
                    ...payload,
                    consumo_ponta:      Number(data.consumo_ponta),
                    consumo_fora_ponta: Number(data.consumo_fora_ponta),
                    concessionaria_id:  Number(data.concessionaria_id),
                };
            } else if (modoEntrada === 'kwh') {
                payload.consumo = Number(data.consumo);
            } else {
                // Modo kWp direto: consumo fictício para que o service encontre kits com a potência informada.
                // Passamos consumo = 0 e usamos kwp_direto como override.
                payload.consumo      = 1; // não será usado para dimensionar
                payload.kwp_direto   = Number(data.consumo); // campo extra para o backend
            }

            const res = await window.axios.post(url, payload);
            setCalcResult(res.data);
            if (res.data.kits.length === 0) {
                setCalcError('Nenhum kit encontrado para os critérios informados. Ajuste a estrutura, tensão ou a potência desejada.');
            }
        } catch (err: any) {
            const msg = err?.response?.data?.error ?? err?.response?.data?.message ?? 'Erro ao calcular.';
            setCalcError(msg);
        } finally {
            setCalculando(false);
        }
    }

    function selecionarKit(kit: KitResult) {
        setKitSelecionado(kit);
        setData('kit_id', String(kit.id));
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(isDemanda
            ? route('consultor.dimensionamento.demanda.store')
            : route('consultor.dimensionamento.convencional.store'));
    }

    const consumoAtual = isDemanda
        ? (Number(data.consumo_ponta) + Number(data.consumo_fora_ponta))
        : Number(data.consumo);

    return (
        <AppLayout>
            <Head title="Novo Orçamento" />
            <PageHeader
                title="Novo Orçamento"
                subtitle={isDemanda ? 'Dimensionamento por demanda (horo-sazonal)' : 'Dimensionamento convencional'}
                breadcrumbs={[{ label: 'Orçamentos', href: route('consultor.orcamentos.index') }, { label: 'Novo' }]}
            />

            <Box component="form" onSubmit={handleSubmit}>
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
                                    value={clienteSelecionado}
                                    onChange={(_, v) => setData({ ...data, cliente_id: v ? String(v.id) : '' })}
                                    renderInput={(params) => (
                                        <TextField {...params} label="Selecionar cliente *" size="small" fullWidth error={!!errors.cliente_id} helperText={errors.cliente_id} />
                                    )}
                                    renderOption={(props, c) => (
                                        <Box component="li" {...props} key={c.id}>
                                            <Box>
                                                <Typography variant="body2">{c.tipo_pessoa === 'pj' ? c.razao_social : c.nome}</Typography>
                                                {c.cidade && <Typography variant="caption" color="text.secondary">{c.cidade.cidade} — {c.cidade.estado}</Typography>}
                                            </Box>
                                        </Box>
                                    )}
                                    noOptionsText="Nenhum cliente encontrado"
                                />
                                {clienteSelecionado?.cidade && (
                                    <Box sx={{ mt: 1.5 }}>
                                        <Chip size="small" icon={<ElectricBoltRoundedIcon />} color="info" variant="outlined"
                                            label={`${clienteSelecionado.cidade.cidade} — ${clienteSelecionado.cidade.estado}`} />
                                    </Box>
                                )}
                                {clienteSelecionado && !clienteSelecionado.cidade && (
                                    <Alert severity="warning" sx={{ mt: 1.5 }}>
                                        Este cliente não tem cidade cadastrada. Edite o cadastro antes de continuar.
                                    </Alert>
                                )}
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
                                                {estruturas.map((e) => <MenuItem key={e.id} value={String(e.id)}>{e.nome}</MenuItem>)}
                                            </Select>
                                            {errors.estrutura_id && <FormHelperText>{errors.estrutura_id}</FormHelperText>}
                                        </FormControl>
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <FormControl fullWidth size="small">
                                            <InputLabel>Orientação dos painéis</InputLabel>
                                            <Select value={data.orientacao} label="Orientação dos painéis" onChange={(e) => setData({ ...data, orientacao: e.target.value })}>
                                                <MenuItem value="norte">Norte (melhor — 0% perda)</MenuItem>
                                                <MenuItem value="nordeste_noroeste">Nordeste / Noroeste (−5%)</MenuItem>
                                                <MenuItem value="leste_oeste">Leste / Oeste (−12%)</MenuItem>
                                                <MenuItem value="sudeste_sudoeste">Sudeste / Sudoeste (−18%)</MenuItem>
                                                <MenuItem value="sul">Sul (−25%)</MenuItem>
                                            </Select>
                                        </FormControl>
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <FormControl fullWidth size="small" error={!!errors.grupo_tarifario}>
                                            <InputLabel>Grupo tarifário *</InputLabel>
                                            <Select value={data.grupo_tarifario} label="Grupo tarifário *" onChange={(e) => setData({ ...data, grupo_tarifario: e.target.value })}>
                                                {(isDemanda ? GRUPOS_DEMANDA : GRUPOS_CONVENCIONAL).map((g) => (
                                                    <MenuItem key={g.value} value={g.value}>{g.label}</MenuItem>
                                                ))}
                                            </Select>
                                            {errors.grupo_tarifario && <FormHelperText>{errors.grupo_tarifario}</FormHelperText>}
                                        </FormControl>
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <Typography variant="body2" color="text.secondary" mb={1}>Tensão da rede *</Typography>
                                        <ToggleButtonGroup exclusive value={data.tensao} onChange={(_, v) => v && setData({ ...data, tensao: v })} size="small">
                                            <ToggleButton value="127">127V</ToggleButton>
                                            <ToggleButton value="220">220V</ToggleButton>
                                            <ToggleButton value="380">380V</ToggleButton>
                                        </ToggleButtonGroup>
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <TextField
                                            fullWidth size="small" label="Quantidade de kits" type="number"
                                            inputProps={{ min: 1, max: 10 }} value={data.qtd_kits}
                                            onChange={(e) => setData({ ...data, qtd_kits: e.target.value })}
                                            helperText="Para instalações com mais de 1 inversor"
                                        />
                                    </Grid>
                                </Grid>
                            </CardContent>
                        </Card>

                        {/* 3. Dimensionamento */}
                        <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                            <CardHeader title="3. Dimensionamento" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                            <Divider />
                            <CardContent>
                                {/* Filtro de categorias */}
                                <Box sx={{ mb: 2.5 }}>
                                    <Typography variant="body2" color="text.secondary" mb={1}>
                                        Tipo de sistema
                                        <Typography component="span" variant="caption" color="text.disabled" ml={1}>
                                            (filtra os kits exibidos)
                                        </Typography>
                                    </Typography>
                                    <FormGroup row sx={{ gap: 0.5, flexWrap: 'wrap' }}>
                                        {TODAS_CATEGORIAS.map((cat) => {
                                            const cfg = CATEGORIAS_CONFIG[cat];
                                            const ativo = categoriasFiltro.has(cat);
                                            return (
                                                <FormControlLabel
                                                    key={cat}
                                                    control={
                                                        <Checkbox
                                                            size="small"
                                                            checked={ativo}
                                                            onChange={() => toggleCategoria(cat)}
                                                            sx={{ py: 0.5, color: cfg.color, '&.Mui-checked': { color: cfg.color } }}
                                                        />
                                                    }
                                                    label={
                                                        <Box sx={{
                                                            px: 1, py: 0.25, borderRadius: 1.5,
                                                            bgcolor: ativo ? cfg.bg : 'transparent',
                                                            border: '1px solid',
                                                            borderColor: ativo ? cfg.color : 'divider',
                                                            transition: 'all 0.15s',
                                                        }}>
                                                            <Typography variant="caption" fontWeight={ativo ? 600 : 400} sx={{ color: ativo ? cfg.color : 'text.secondary' }}>
                                                                {cfg.label}
                                                            </Typography>
                                                        </Box>
                                                    }
                                                    sx={{ mx: 0, alignItems: 'center' }}
                                                />
                                            );
                                        })}
                                    </FormGroup>
                                </Box>

                                {/* Toggle modo de entrada (só no convencional) */}
                                {!isDemanda && (
                                    <Box sx={{ mb: 2.5 }}>
                                        <Typography variant="body2" color="text.secondary" mb={1}>Como deseja dimensionar?</Typography>
                                        <ToggleButtonGroup exclusive value={modoEntrada} onChange={(_, v) => v && setModoEntrada(v)} size="small">
                                            <ToggleButton value="kwh">
                                                <Box sx={{ textAlign: 'left' }}>
                                                    <Typography variant="caption" fontWeight={600} display="block">Por consumo</Typography>
                                                    <Typography variant="caption" color="text.secondary">kWh/mês da conta</Typography>
                                                </Box>
                                            </ToggleButton>
                                            <ToggleButton value="kwp">
                                                <Box sx={{ textAlign: 'left' }}>
                                                    <Typography variant="caption" fontWeight={600} display="block">Por potência</Typography>
                                                    <Typography variant="caption" color="text.secondary">kWp desejado</Typography>
                                                </Box>
                                            </ToggleButton>
                                        </ToggleButtonGroup>
                                    </Box>
                                )}

                                <Grid container spacing={2} alignItems="flex-start">
                                    {!isDemanda ? (
                                        <Grid size={{ xs: 12, sm: 6 }}>
                                            <TextField
                                                fullWidth size="small"
                                                label={modoEntrada === 'kwh' ? 'Consumo mensal *' : 'Potência desejada *'}
                                                type="number" inputProps={{ min: 0.1, step: modoEntrada === 'kwh' ? 10 : 0.1 }}
                                                InputProps={{ endAdornment: <InputAdornment position="end">{modoEntrada === 'kwh' ? 'kWh/mês' : 'kWp'}</InputAdornment> }}
                                                value={data.consumo}
                                                onChange={(e) => setData({ ...data, consumo: e.target.value })}
                                                error={!!errors.consumo}
                                                helperText={errors.consumo ?? (modoEntrada === 'kwh'
                                                    ? 'Média dos últimos 12 meses na conta de luz'
                                                    : 'Potência de pico desejada para o sistema')}
                                            />
                                        </Grid>
                                    ) : (
                                        <>
                                            <Grid size={{ xs: 12, sm: 4 }}>
                                                <TextField
                                                    fullWidth size="small" label="Consumo Ponta *" type="number" inputProps={{ min: 1, step: 10 }}
                                                    InputProps={{ endAdornment: <InputAdornment position="end">kWh/mês</InputAdornment> }}
                                                    value={data.consumo_ponta} onChange={(e) => setData({ ...data, consumo_ponta: e.target.value })}
                                                    error={!!errors.consumo_ponta} helperText={errors.consumo_ponta ?? 'Horário de ponta (18h–21h)'}
                                                />
                                            </Grid>
                                            <Grid size={{ xs: 12, sm: 4 }}>
                                                <TextField
                                                    fullWidth size="small" label="Consumo Fora Ponta *" type="number" inputProps={{ min: 1, step: 10 }}
                                                    InputProps={{ endAdornment: <InputAdornment position="end">kWh/mês</InputAdornment> }}
                                                    value={data.consumo_fora_ponta} onChange={(e) => setData({ ...data, consumo_fora_ponta: e.target.value })}
                                                    error={!!errors.consumo_fora_ponta} helperText={errors.consumo_fora_ponta ?? 'Fora do horário de ponta'}
                                                />
                                            </Grid>
                                            <Grid size={{ xs: 12, sm: 4 }}>
                                                <FormControl fullWidth size="small" error={!!errors.concessionaria_id}>
                                                    <InputLabel>Concessionária *</InputLabel>
                                                    <Select value={data.concessionaria_id} label="Concessionária *" onChange={(e) => setData({ ...data, concessionaria_id: e.target.value })}>
                                                        {concessionarias.map((c) => <MenuItem key={c.id} value={String(c.id)}>{c.estado} — {c.nome}</MenuItem>)}
                                                    </Select>
                                                    {errors.concessionaria_id && <FormHelperText>{errors.concessionaria_id}</FormHelperText>}
                                                </FormControl>
                                            </Grid>
                                        </>
                                    )}

                                    <Grid size={{ xs: 12, sm: isDemanda ? 12 : 6 }}>
                                        <Button
                                            variant="contained" onClick={calcular} fullWidth sx={{ height: 40 }}
                                            disabled={calculando || !data.cliente_id || !data.estrutura_id || (isDemanda ? (!data.consumo_ponta || !data.consumo_fora_ponta || !data.concessionaria_id) : !data.consumo)}
                                            startIcon={calculando ? <CircularProgress size={16} color="inherit" /> : <CalculateRoundedIcon />}
                                        >
                                            {calculando ? 'Calculando...' : 'Calcular Dimensionamento'}
                                        </Button>
                                    </Grid>
                                </Grid>

                                {calcError && <Alert severity="warning" sx={{ mt: 2 }}>{calcError}</Alert>}

                                {/* Resultados */}
                                {calcResult && (
                                    <Box sx={{ mt: 3 }}>
                                        {/* Chips de resumo */}
                                        <Box sx={{ display: 'flex', gap: 1.5, flexWrap: 'wrap', mb: 2 }}>
                                            <Chip label={`${calcResult.potencia_calculada} kWp necessário`} color="primary" size="small" />
                                            <Chip label={`HSP: ${calcResult.hsp} kWh/m²/dia`} variant="outlined" size="small" />
                                            <Tooltip title={`PR sistema: ${calcResult.pr.pr_sistema}% | Perda orientação: ${calcResult.pr.perdas_orientacao}% | Margem segurança: ${calcResult.pr.margem_seguranca}%`}>
                                                <Chip
                                                    icon={<InfoOutlinedIcon fontSize="small" />}
                                                    label={`PR total: ${calcResult.pr.pr_total}%`}
                                                    variant="outlined"
                                                    size="small"
                                                    sx={{ cursor: 'help' }}
                                                />
                                            </Tooltip>
                                        </Box>

                                        {/* Análise mensal */}
                                        {calcResult.analise_mensal && (
                                            <Box sx={{ mb: 2.5 }}>
                                                {calcResult.analise_mensal.pior_cobertura < 100 && (
                                                    <Alert severity="warning" icon={<WarningAmberRoundedIcon />} sx={{ mb: 1.5, py: 0.5 }}>
                                                        Atenção: em <strong>{MESES_LABEL[calcResult.analise_mensal.pior_mes]}</strong> o sistema cobrirá apenas <strong>{calcResult.analise_mensal.pior_cobertura}%</strong> do consumo — menor irradiação do ano.
                                                    </Alert>
                                                )}
                                                <Typography variant="caption" color="text.secondary" display="block" mb={0.5}>
                                                    Cobertura mensal estimada
                                                </Typography>
                                                <Grid container spacing={0.5}>
                                                    {Object.entries(calcResult.analise_mensal.meses).map(([mes, m]) => {
                                                        const pct = Math.min(m.cobertura, 130);
                                                        const ok = m.cobertura >= 100;
                                                        return (
                                                            <Grid key={mes} size={{ xs: 2, sm: 1 }}>
                                                                <Tooltip title={`${MESES_LABEL[mes]}: ${m.geracao} kWh gerado, ${m.cobertura}% do consumo`}>
                                                                    <Box sx={{ textAlign: 'center', cursor: 'help' }}>
                                                                        <Typography variant="caption" color="text.secondary" sx={{ fontSize: '0.65rem' }}>{MESES_LABEL[mes]}</Typography>
                                                                        <LinearProgress
                                                                            variant="determinate"
                                                                            value={pct}
                                                                            sx={{
                                                                                height: 28, borderRadius: 1, my: 0.3,
                                                                                bgcolor: alpha(ok ? '#10B981' : '#F59E0B', 0.15),
                                                                                '& .MuiLinearProgress-bar': { bgcolor: ok ? '#10B981' : '#F59E0B', borderRadius: 1 },
                                                                            }}
                                                                        />
                                                                        <Typography variant="caption" fontWeight={600} sx={{ fontSize: '0.62rem', color: ok ? 'success.main' : 'warning.main' }}>
                                                                            {m.cobertura}%
                                                                        </Typography>
                                                                    </Box>
                                                                </Tooltip>
                                                            </Grid>
                                                        );
                                                    })}
                                                </Grid>
                                            </Box>
                                        )}

                                        {/* Kits */}
                                        {calcResult.kits.length > 0 && (
                                            <>
                                                <Typography variant="subtitle2" gutterBottom>Selecione um kit</Typography>
                                                {errors.kit_id && <FormHelperText error sx={{ mb: 1 }}>{errors.kit_id}</FormHelperText>}
                                                <RadioGroup
                                                    value={kitSelecionado ? String(kitSelecionado.id) : ''}
                                                    onChange={(e) => {
                                                        const kit = calcResult.kits.find((k) => String(k.id) === e.target.value);
                                                        if (kit) selecionarKit(kit);
                                                    }}
                                                >
                                                    <Grid container spacing={1.5}>
                                                        {calcResult.kits.map((kit) => {
                                                            const selected = kitSelecionado?.id === kit.id;
                                                            return (
                                                                <Grid key={kit.id} size={{ xs: 12, sm: 6 }}>
                                                                    <Box
                                                                        onClick={() => selecionarKit(kit)}
                                                                        sx={{
                                                                            border: '1.5px solid', borderRadius: 2, p: 1.5, cursor: 'pointer',
                                                                            borderColor: selected ? 'primary.main' : 'divider',
                                                                            bgcolor: selected ? alpha('#6366f1', 0.06) : 'transparent',
                                                                            transition: 'all 0.15s', '&:hover': { borderColor: 'primary.main' },
                                                                        }}
                                                                    >
                                                                        <FormControlLabel
                                                                            value={String(kit.id)} control={<Radio size="small" />}
                                                                            sx={{ m: 0, alignItems: 'flex-start', gap: 0.5, width: '100%' }}
                                                                            label={
                                                                                <Box>
                                                                                    {/* Badge de categoria — destaque visual */}
                                                                                    {kit.categoria && CATEGORIAS_CONFIG[kit.categoria] && (
                                                                                        <Box
                                                                                            sx={{
                                                                                                display: 'inline-flex', alignItems: 'center',
                                                                                                px: 1, py: 0.2, borderRadius: 1, mb: 0.5,
                                                                                                bgcolor: CATEGORIAS_CONFIG[kit.categoria].bg,
                                                                                                border: '1px solid',
                                                                                                borderColor: CATEGORIAS_CONFIG[kit.categoria].color,
                                                                                            }}
                                                                                        >
                                                                                            <Typography
                                                                                                variant="caption"
                                                                                                fontWeight={700}
                                                                                                sx={{ color: CATEGORIAS_CONFIG[kit.categoria].color, fontSize: '0.65rem', letterSpacing: 0.5, textTransform: 'uppercase' }}
                                                                                            >
                                                                                                {CATEGORIAS_CONFIG[kit.categoria].label}
                                                                                            </Typography>
                                                                                        </Box>
                                                                                    )}
                                                                                    <Typography variant="body2" fontWeight={600} display="block">{kit.nome}</Typography>
                                                                                    {kit.modelo && <Typography variant="caption" color="text.secondary" display="block">{kit.modelo}</Typography>}
                                                                                    <Box sx={{ display: 'flex', gap: 1.5, mt: 0.5, flexWrap: 'wrap', alignItems: 'center' }}>
                                                                                        <Chip label={`${kit.potencia_kwp} kWp`} size="small" sx={{ height: 18, fontSize: '0.7rem' }} />
                                                                                        <Typography variant="caption" color="text.secondary">{kit.geracao} kWh/mês</Typography>
                                                                                        <Typography variant="caption" color="success.main" fontWeight={700}>{fmtMoney(kit.preco_venda)}</Typography>
                                                                                    </Box>
                                                                                    {kit.fornecedor && <Typography variant="caption" color="text.secondary">{kit.fornecedor}</Typography>}
                                                                                </Box>
                                                                            }
                                                                        />
                                                                    </Box>
                                                                </Grid>
                                                            );
                                                        })}
                                                    </Grid>
                                                </RadioGroup>
                                            </>
                                        )}
                                    </Box>
                                )}
                            </CardContent>
                        </Card>
                    </Grid>

                    {/* Sidebar */}
                    <Grid size={{ xs: 12, md: 4 }}>
                        <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                            <CardHeader title="Anotações" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                            <Divider />
                            <CardContent sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
                                <TextField fullWidth multiline rows={4} size="small" label="Anotações para o cliente" value={data.anotacoes} onChange={(e) => setData({ ...data, anotacoes: e.target.value })} />
                                <TextField fullWidth multiline rows={3} size="small" label="Notas técnicas" value={data.anotacoes_tecnicas} onChange={(e) => setData({ ...data, anotacoes_tecnicas: e.target.value })} />
                            </CardContent>
                        </Card>

                        {kitSelecionado && (
                            <Card variant="outlined" sx={{ mb: 3, borderRadius: 2, borderColor: 'success.light' }}>
                                <CardHeader title="Resumo do Orçamento" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                                <Divider />
                                <CardContent>
                                    {/* Categoria do kit selecionado */}
                                    {kitSelecionado.categoria && CATEGORIAS_CONFIG[kitSelecionado.categoria] && (
                                        <Box sx={{ mb: 1.5 }}>
                                            <Box sx={{
                                                display: 'inline-flex', px: 1.5, py: 0.4, borderRadius: 2,
                                                bgcolor: CATEGORIAS_CONFIG[kitSelecionado.categoria].bg,
                                                border: '1.5px solid', borderColor: CATEGORIAS_CONFIG[kitSelecionado.categoria].color,
                                            }}>
                                                <Typography variant="caption" fontWeight={700} sx={{ color: CATEGORIAS_CONFIG[kitSelecionado.categoria].color, textTransform: 'uppercase', letterSpacing: 0.5 }}>
                                                    {CATEGORIAS_CONFIG[kitSelecionado.categoria].label}
                                                </Typography>
                                            </Box>
                                        </Box>
                                    )}
                                    {[
                                        { label: 'Kit selecionado', value: kitSelecionado.nome },
                                        { label: 'Potência', value: `${kitSelecionado.potencia_kwp} kWp` },
                                        { label: 'Geração estimada', value: `${kitSelecionado.geracao} kWh/mês` },
                                        ...(consumoAtual > 0 ? [{ label: 'Cobertura estimada', value: `${Math.round((kitSelecionado.geracao / consumoAtual) * 100)}%` }] : []),
                                    ].map(({ label, value }) => (
                                        <Box key={label} sx={{ display: 'flex', justifyContent: 'space-between', py: 0.75 }}>
                                            <Typography variant="body2" color="text.secondary">{label}</Typography>
                                            <Typography variant="body2" fontWeight={500} sx={{ textAlign: 'right', maxWidth: '60%' }}>{value}</Typography>
                                        </Box>
                                    ))}
                                    <Divider sx={{ my: 1.5 }} />
                                    <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                        <Typography variant="body2" color="text.secondary">Valor Total</Typography>
                                        <Typography variant="h6" fontWeight={700} color="success.main">{fmtMoney(kitSelecionado.preco_venda)}</Typography>
                                    </Box>
                                </CardContent>
                            </Card>
                        )}

                        <Button
                            type="submit" variant="contained" fullWidth size="large"
                            disabled={processing || !kitSelecionado}
                            startIcon={processing ? <CircularProgress size={18} color="inherit" /> : <SaveRoundedIcon />}
                        >
                            {processing ? 'Salvando...' : 'Salvar Orçamento'}
                        </Button>
                        {!kitSelecionado && (
                            <Typography variant="caption" color="text.secondary" display="block" textAlign="center" mt={1}>
                                Calcule o dimensionamento e selecione um kit para continuar
                            </Typography>
                        )}
                    </Grid>
                </Grid>
            </Box>
        </AppLayout>
    );
}
