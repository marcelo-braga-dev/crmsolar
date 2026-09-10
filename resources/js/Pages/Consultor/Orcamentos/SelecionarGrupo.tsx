import React, { useState } from 'react';
import {
    Box, Button, Card, CardContent, Chip, Collapse, Divider,
    FormControl, Grid, InputLabel, MenuItem, Select, Typography, alpha,
} from '@mui/material';
import HomeRoundedIcon from '@mui/icons-material/HomeRounded';
import AgricultureRoundedIcon from '@mui/icons-material/AgricultureRounded';
import StorefrontRoundedIcon from '@mui/icons-material/StorefrontRounded';
import FactoryRoundedIcon from '@mui/icons-material/FactoryRounded';
import ArrowForwardRoundedIcon from '@mui/icons-material/ArrowForwardRounded';
import CheckCircleRoundedIcon from '@mui/icons-material/CheckCircleRounded';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';

type GrupoKey = 'B1' | 'B2' | 'B3' | 'A';

interface Opcao {
    key: GrupoKey;
    icon: React.ReactNode;
    titulo: string;
    subtitulo: string;
    descricao: string;
    exemplos: string;
    cor: string;
    href?: string;
}

const OPCOES: Opcao[] = [
    {
        key: 'B1',
        icon: <HomeRoundedIcon sx={{ fontSize: 36 }} />,
        titulo: 'Residencial',
        subtitulo: 'Grupo B1',
        descricao: 'Para casas e apartamentos com conta de luz comum.',
        exemplos: 'Casa, apartamento, condomínio residencial',
        cor: '#2563EB',
        href: '/consultor/orcamentos/grupo/b1/create',
    },
    {
        key: 'B2',
        icon: <AgricultureRoundedIcon sx={{ fontSize: 36 }} />,
        titulo: 'Rural',
        subtitulo: 'Grupo B2',
        descricao: 'Para propriedades rurais com tarifa subsidiada. Inclui irrigação e bombeamento.',
        exemplos: 'Sítio, fazenda, chácara, irrigação agrícola',
        cor: '#059669',
        href: '/consultor/orcamentos/grupo/b2/create',
    },
    {
        key: 'B3',
        icon: <StorefrontRoundedIcon sx={{ fontSize: 36 }} />,
        titulo: 'Comercial',
        subtitulo: 'Grupo B3',
        descricao: 'Para comércios e pequenas empresas ligadas à rede comum (baixa tensão).',
        exemplos: 'Loja, escritório, restaurante, clínica, escola',
        cor: '#D97706',
        href: '/consultor/orcamentos/grupo/b3/create',
    },
    {
        key: 'A',
        icon: <FactoryRoundedIcon sx={{ fontSize: 36 }} />,
        titulo: 'Industrial / Grande Porte',
        subtitulo: 'Grupo A (Média/Alta Tensão)',
        descricao: 'Para empresas com transformador próprio e conta com demanda contratada.',
        exemplos: 'Indústria, shopping, hospital, grande armazém',
        cor: '#7C3AED',
    },
];

const SUBGRUPOS_A = [
    { value: 'A4',  label: 'A4 — 2,3 kV a 25 kV  (mais comum)' },
    { value: 'A3a', label: 'A3a — 30 kV a 44 kV' },
    { value: 'A3',  label: 'A3 — 69 kV' },
    { value: 'A2',  label: 'A2 — 88 kV a 138 kV' },
    { value: 'A1',  label: 'A1 — ≥ 230 kV' },
];

export default function SelecionarGrupo() {
    const [selecionado, setSelecionado] = useState<GrupoKey | null>(null);
    const [subgrupoA, setSubgrupoA] = useState('A4');

    function getHref(): string {
        if (!selecionado) return '#';
        if (selecionado === 'A') return `/consultor/orcamentos/grupo/${subgrupoA}/create`;
        return OPCOES.find((o) => o.key === selecionado)?.href ?? '#';
    }

    return (
        <AppLayout>
            <Head title="Novo Orçamento" />
            <PageHeader
                title="Novo Orçamento"
                breadcrumbs={[{ label: 'Orçamentos', href: route('consultor.orcamentos.index') }, { label: 'Novo' }]}
            />

            {/* Pergunta principal */}
            <Box sx={{ mb: 4 }}>
                <Typography variant="h6" fontWeight={600} gutterBottom>
                    Qual é o tipo de instalação do cliente?
                </Typography>
                <Typography variant="body2" color="text.secondary">
                    Isso determina o cálculo correto e a tarifa aplicada. A informação está na conta de luz do cliente.
                </Typography>
            </Box>

            {/* Cards de seleção */}
            <Grid container spacing={2} sx={{ mb: 3 }}>
                {OPCOES.map((op) => {
                    const ativo = selecionado === op.key;
                    return (
                        <Grid key={op.key} size={{ xs: 12, sm: 6, md: 3 }}>
                            <Card
                                onClick={() => setSelecionado(op.key)}
                                variant="outlined"
                                sx={{
                                    cursor: 'pointer',
                                    height: '100%',
                                    borderRadius: 3,
                                    border: '2px solid',
                                    borderColor: ativo ? op.cor : 'divider',
                                    bgcolor: ativo ? alpha(op.cor, 0.04) : 'background.paper',
                                    transition: 'all 0.18s ease',
                                    position: 'relative',
                                    '&:hover': {
                                        borderColor: op.cor,
                                        boxShadow: `0 4px 24px ${op.cor}25`,
                                        transform: 'translateY(-2px)',
                                    },
                                }}
                            >
                                {/* Checkmark quando selecionado */}
                                {ativo && (
                                    <CheckCircleRoundedIcon
                                        sx={{
                                            position: 'absolute', top: 12, right: 12,
                                            color: op.cor, fontSize: 22,
                                        }}
                                    />
                                )}

                                <CardContent sx={{ p: 2.5 }}>
                                    {/* Ícone */}
                                    <Box sx={{
                                        display: 'inline-flex', p: 1.5, borderRadius: 2.5, mb: 2,
                                        bgcolor: ativo ? op.cor : alpha(op.cor, 0.12),
                                        color: ativo ? '#fff' : op.cor,
                                        transition: 'all 0.18s',
                                    }}>
                                        {op.icon}
                                    </Box>

                                    {/* Título */}
                                    <Typography variant="h6" fontWeight={700} sx={{ color: ativo ? op.cor : 'text.primary', lineHeight: 1.2, mb: 0.5 }}>
                                        {op.titulo}
                                    </Typography>
                                    <Chip label={op.subtitulo} size="small" sx={{ mb: 1.5, bgcolor: alpha(op.cor, 0.1), color: op.cor, fontWeight: 600, fontSize: '0.7rem' }} />

                                    {/* Descrição */}
                                    <Typography variant="body2" color="text.secondary" sx={{ mb: 1 }}>
                                        {op.descricao}
                                    </Typography>

                                    {/* Exemplos */}
                                    <Typography variant="caption" color="text.disabled" sx={{ fontStyle: 'italic' }}>
                                        Ex.: {op.exemplos}
                                    </Typography>
                                </CardContent>
                            </Card>
                        </Grid>
                    );
                })}
            </Grid>

            {/* Subgrupo A (aparece só quando Industrial é selecionado) */}
            <Collapse in={selecionado === 'A'}>
                <Card variant="outlined" sx={{ mb: 3, borderRadius: 3, borderColor: '#7C3AED', bgcolor: alpha('#7C3AED', 0.03) }}>
                    <CardContent>
                        <Typography variant="subtitle2" fontWeight={700} color="#7C3AED" gutterBottom>
                            Qual é o nível de tensão da ligação?
                        </Typography>
                        <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
                            Verifique na conta de energia ou com o responsável elétrico da empresa.
                        </Typography>
                        <FormControl size="small" sx={{ minWidth: 320 }}>
                            <InputLabel>Subgrupo tarifário</InputLabel>
                            <Select
                                value={subgrupoA}
                                label="Subgrupo tarifário"
                                onChange={(e) => setSubgrupoA(e.target.value)}
                            >
                                {SUBGRUPOS_A.map((s) => (
                                    <MenuItem key={s.value} value={s.value}>{s.label}</MenuItem>
                                ))}
                            </Select>
                        </FormControl>
                    </CardContent>
                </Card>
            </Collapse>

            {/* Botão de confirmação */}
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
                <Button
                    component={Link}
                    href={getHref()}
                    variant="contained"
                    size="large"
                    disabled={!selecionado}
                    endIcon={<ArrowForwardRoundedIcon />}
                    sx={{
                        px: 4, py: 1.5, borderRadius: 2.5, fontWeight: 700,
                        bgcolor: selecionado ? OPCOES.find((o) => o.key === selecionado)?.cor : 'primary.main',
                        '&:hover': {
                            bgcolor: selecionado ? OPCOES.find((o) => o.key === selecionado)?.cor : 'primary.main',
                            filter: 'brightness(0.9)',
                        },
                    }}
                >
                    {selecionado
                        ? `Continuar com ${OPCOES.find((o) => o.key === selecionado)?.titulo}`
                        : 'Selecione um tipo acima'}
                </Button>

                <Button component={Link} href={route('consultor.orcamentos.index')} variant="text" color="inherit">
                    Cancelar
                </Button>
            </Box>

            {/* Dica */}
            {!selecionado && (
                <Typography variant="caption" color="text.disabled" display="block" sx={{ mt: 2 }}>
                    Não sabe qual escolher? O grupo tarifário está impresso na fatura de energia do cliente, normalmente na primeira página.
                </Typography>
            )}
        </AppLayout>
    );
}
