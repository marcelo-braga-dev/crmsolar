import React, { useEffect, useMemo, useState } from 'react';
import {
    Alert, Avatar, Box, Button, Card, CardContent, Chip, Grid, IconButton, InputAdornment, Stack, TextField, Tooltip, Typography,
} from '@mui/material';
import { ThemeProvider, alpha, getContrastRatio, useTheme } from '@mui/material/styles';
import UploadRoundedIcon from '@mui/icons-material/UploadRounded';
import DeleteOutlineRoundedIcon from '@mui/icons-material/DeleteOutlineRounded';
import RestartAltRoundedIcon from '@mui/icons-material/RestartAltRounded';
import ElectricBoltRoundedIcon from '@mui/icons-material/ElectricBoltRounded';
import DashboardRoundedIcon from '@mui/icons-material/DashboardRounded';
import ViewKanbanRoundedIcon from '@mui/icons-material/ViewKanbanRounded';
import PeopleRoundedIcon from '@mui/icons-material/PeopleRounded';
import ReplayRoundedIcon from '@mui/icons-material/ReplayRounded';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { ConfirmDialog } from '@/Components/UI/ConfirmDialog';
import { criarTema } from '@/theme';
import { rotuloTipoUsuario } from '@/Components/UI/rotuloTipoUsuario';
import type { Identidade } from '@/types';

type Cor = 'cor_primaria' | 'cor_secundaria' | 'menu_fundo' | 'menu_fonte';
type Arquivo = 'logo' | 'logo_clara' | 'favicon';

interface Props {
    atual: Identidade;
    padrao: Pick<Identidade, 'nome' | 'rodape' | Cor>;
    contraste_minimo: number;
}

const HEX = /^#[0-9A-Fa-f]{6}$/;

const ARQUIVOS: Record<Arquivo, { titulo: string; dica: string; aceita: string; fundo: 'menu' | 'claro' | 'neutro' }> = {
    logo: { titulo: 'Logo do menu', dica: 'Aparece num avatar redondo no menu lateral: prefira imagem quadrada (ex.: só o símbolo). PNG, JPG ou WEBP, até 2 MB.', aceita: 'image/png,image/jpeg,image/webp', fundo: 'menu' },
    logo_clara: { titulo: 'Logo para fundo claro', dica: 'Tela de login e PDFs de orçamento e contrato. Se vazia, usa a logo do menu.', aceita: 'image/png,image/jpeg,image/webp', fundo: 'claro' },
    favicon: { titulo: 'Favicon', dica: 'Ícone da aba do navegador. PNG quadrado (32×32 ou 64×64) ou ICO, até 512 KB.', aceita: 'image/png,image/x-icon,image/vnd.microsoft.icon,.ico', fundo: 'neutro' },
};

const CORES: { campo: Cor; titulo: string; dica: string }[] = [
    { campo: 'cor_primaria', titulo: 'Cor primária', dica: 'Botões, links, item ativo do menu e destaques' },
    { campo: 'cor_secundaria', titulo: 'Cor secundária', dica: 'Ícone da marca, avisos e detalhes de apoio' },
    { campo: 'menu_fundo', titulo: 'Fundo do menu lateral', dica: 'Cor de fundo do menu' },
    { campo: 'menu_fonte', titulo: 'Fonte do menu lateral', dica: 'Textos e ícones do menu' },
];

export default function IdentidadeVisualIndex({ atual, padrao, contraste_minimo }: Props) {
    const { data, setData, post, processing, errors, transform } = useForm({
        nome: atual.nome,
        rodape: atual.rodape,
        cor_primaria: atual.cor_primaria,
        cor_secundaria: atual.cor_secundaria,
        menu_fundo: atual.menu_fundo,
        menu_fonte: atual.menu_fonte,
        logo: null as File | null,
        logo_clara: null as File | null,
        favicon: null as File | null,
        remover_logo: false,
        remover_logo_clara: false,
        remover_favicon: false,
    });
    const [restaurar, setRestaurar] = useState(false);

    // Pré-visualização das imagens escolhidas (antes de salvar).
    const previas = usePrevias({ logo: data.logo, logo_clara: data.logo_clara, favicon: data.favicon });
    const urlDe = (campo: Arquivo): string | null => {
        if (previas[campo]) return previas[campo];
        if (data[`remover_${campo}`]) return null;
        return atual[`${campo}_url`];
    };

    const coresValidas = CORES.every(({ campo }) => HEX.test(data[campo]));
    const contraste = coresValidas ? getContrastRatio(data.menu_fundo, data.menu_fonte) : 0;
    const legivel = contraste >= contraste_minimo;

    const identidadePrevia: Identidade = {
        ...atual,
        nome: data.nome || padrao.nome,
        rodape: data.rodape,
        ...(coresValidas ? { cor_primaria: data.cor_primaria, cor_secundaria: data.cor_secundaria, menu_fundo: data.menu_fundo, menu_fonte: data.menu_fonte } : {}),
        logo_url: urlDe('logo'),
        logo_clara_url: urlDe('logo_clara'),
        favicon_url: urlDe('favicon'),
    };

    const salvar = (e: React.FormEvent) => {
        e.preventDefault();
        // Sem arquivo novo, os campos de imagem não vão no envio (o servidor mantém os atuais).
        transform((d) => Object.fromEntries(Object.entries(d).filter(([, v]) => v !== null)));
        post(route('admin.configuracoes.identidade.update'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => setData((d) => ({ ...d, logo: null, logo_clara: null, favicon: null, remover_logo: false, remover_logo_clara: false, remover_favicon: false })),
        });
    };

    const escolherArquivo = (campo: Arquivo, arquivo: File | null) => {
        setData((d) => ({ ...d, [campo]: arquivo, [`remover_${campo}`]: false }));
    };
    const removerArquivo = (campo: Arquivo) => {
        setData((d) => ({ ...d, [campo]: null, [`remover_${campo}`]: Boolean(atual[`${campo}_url`]) }));
    };

    return (
        <AppLayout title="Identidade visual">
            <Head title="Identidade visual" />
            <PageHeader
                title="Identidade visual"
                subtitle="Nome, logos, favicon e cores da plataforma — valem para todos os usuários, na tela de login e nos PDFs"
                breadcrumbs={[{ label: 'Configurações' }, { label: 'Identidade visual' }]}
                action={
                    <Button color="inherit" startIcon={<RestartAltRoundedIcon />} onClick={() => setRestaurar(true)}>
                        Restaurar padrão
                    </Button>
                }
            />

            <Box component="form" onSubmit={salvar}>
                <Grid container spacing={2.5}>
                    <Grid size={{ xs: 12, lg: 7 }}>
                        <Stack spacing={2.5}>
                            {/* Nome e textos */}
                            <Card>
                                <CardContent>
                                    <Titulo titulo="Nome e textos" subtitulo="Título das abas do navegador, menu, tela de login e e-mails do sistema" />
                                    <Grid container spacing={2}>
                                        <Grid size={{ xs: 12, sm: 6 }}>
                                            <TextField
                                                label="Nome da plataforma" required fullWidth value={data.nome}
                                                onChange={(e) => setData('nome', e.target.value)} inputProps={{ maxLength: 40 }}
                                                error={!!errors.nome} helperText={errors.nome ?? `Padrão: ${padrao.nome}`}
                                            />
                                        </Grid>
                                        <Grid size={{ xs: 12, sm: 6 }}>
                                            <TextField
                                                label="Texto do rodapé da tela de login" fullWidth value={data.rodape}
                                                onChange={(e) => setData('rodape', e.target.value)} inputProps={{ maxLength: 80 }}
                                                error={!!errors.rodape} helperText={errors.rodape ?? `Exibido como "© ${new Date().getFullYear()} ${data.nome || padrao.nome} · ${data.rodape || padrao.rodape}"`}
                                            />
                                        </Grid>
                                    </Grid>
                                </CardContent>
                            </Card>

                            {/* Imagens */}
                            <Card>
                                <CardContent>
                                    <Titulo titulo="Logos e favicon" subtitulo="SVG não é aceito por segurança (pode conter scripts)" />
                                    <Stack spacing={2}>
                                        {(Object.keys(ARQUIVOS) as Arquivo[]).map((campo) => (
                                            <CampoImagem
                                                key={campo}
                                                campo={campo}
                                                url={urlDe(campo)}
                                                pendente={Boolean(data[campo]) || data[`remover_${campo}`]}
                                                fundoMenu={coresValidas ? data.menu_fundo : atual.menu_fundo}
                                                erro={errors[campo]}
                                                onEscolher={(f) => escolherArquivo(campo, f)}
                                                onRemover={() => removerArquivo(campo)}
                                            />
                                        ))}
                                    </Stack>
                                </CardContent>
                            </Card>

                            {/* Cores */}
                            <Card>
                                <CardContent>
                                    <Titulo titulo="Cores" subtitulo="A pré-visualização ao lado muda enquanto você escolhe" />
                                    <Grid container spacing={2}>
                                        {CORES.map(({ campo, titulo, dica }) => (
                                            <Grid key={campo} size={{ xs: 12, sm: 6 }}>
                                                <CampoCor
                                                    titulo={titulo} dica={dica} valor={data[campo]} padrao={padrao[campo]}
                                                    erro={errors[campo]} onChange={(v) => setData(campo, v)}
                                                />
                                            </Grid>
                                        ))}
                                    </Grid>
                                    {coresValidas && (
                                        <Alert severity={legivel ? 'success' : 'error'} sx={{ mt: 2 }}>
                                            Contraste do menu: <b>{contraste.toFixed(1)}:1</b>{' '}
                                            {legivel ? '— textos legíveis.' : `— ilegível. O mínimo é ${contraste_minimo}:1; afaste as cores do fundo e da fonte.`}
                                        </Alert>
                                    )}
                                </CardContent>
                            </Card>

                            <Box sx={{ display: 'flex', justifyContent: 'flex-end', gap: 1 }}>
                                <Button color="inherit" onClick={() => router.reload()} disabled={processing}>Descartar alterações</Button>
                                <Button type="submit" variant="contained" disabled={processing || !coresValidas || !legivel || !data.nome.trim()}>
                                    Salvar identidade visual
                                </Button>
                            </Box>
                        </Stack>
                    </Grid>

                    <Grid size={{ xs: 12, lg: 5 }}>
                        <Box sx={{ position: { lg: 'sticky' }, top: { lg: 88 } }}>
                            <Previa identidade={identidadePrevia} />
                        </Box>
                    </Grid>
                </Grid>
            </Box>

            <ConfirmDialog
                open={restaurar}
                title="Restaurar identidade padrão"
                message="Volta o nome, os textos e as cores de fábrica e remove as logos e o favicon enviados. Continuar?"
                confirmLabel="Restaurar"
                onConfirm={() => router.delete(route('admin.configuracoes.identidade.restaurar'), { preserveScroll: true, onFinish: () => setRestaurar(false) })}
                onCancel={() => setRestaurar(false)}
            />
        </AppLayout>
    );
}

/** URLs temporárias das imagens escolhidas, liberadas quando trocam. */
function usePrevias(arquivos: Record<Arquivo, File | null>): Record<Arquivo, string | null> {
    const [urls, setUrls] = useState<Record<Arquivo, string | null>>({ logo: null, logo_clara: null, favicon: null });

    useEffect(() => {
        const novas = Object.fromEntries(
            Object.entries(arquivos).map(([campo, f]) => [campo, f ? URL.createObjectURL(f) : null]),
        ) as Record<Arquivo, string | null>;
        setUrls(novas);

        return () => Object.values(novas).forEach((u) => u && URL.revokeObjectURL(u));
    }, [arquivos.logo, arquivos.logo_clara, arquivos.favicon]); // eslint-disable-line react-hooks/exhaustive-deps

    return urls;
}

function Titulo({ titulo, subtitulo }: { titulo: string; subtitulo: string }) {
    return (
        <Box sx={{ mb: 2 }}>
            <Typography variant="subtitle1" fontWeight={700}>{titulo}</Typography>
            <Typography variant="caption" color="text.secondary">{subtitulo}</Typography>
        </Box>
    );
}

function CampoCor({ titulo, dica, valor, padrao, erro, onChange }: {
    titulo: string; dica: string; valor: string; padrao: string; erro?: string; onChange: (v: string) => void;
}) {
    const valido = HEX.test(valor);

    return (
        <Box sx={{ display: 'flex', gap: 1.5, alignItems: 'flex-start' }}>
            <Box
                component="input"
                type="color"
                aria-label={titulo}
                value={valido ? valor : '#000000'}
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => onChange(e.target.value.toUpperCase())}
                sx={{
                    width: 48, height: 40, p: 0.4, border: '1px solid', borderColor: 'divider', borderRadius: 1.5,
                    bgcolor: 'background.paper', cursor: 'pointer', flexShrink: 0,
                }}
            />
            <TextField
                label={titulo} size="small" fullWidth value={valor}
                onChange={(e) => onChange(e.target.value.trim().toUpperCase())}
                error={!!erro || !valido} helperText={erro ?? (valido ? dica : 'Formato #RRGGBB')}
                inputProps={{ maxLength: 7, style: { fontFamily: 'monospace' } }}
                InputProps={{
                    endAdornment: valor !== padrao ? (
                        <InputAdornment position="end">
                            <Tooltip title={`Voltar ao padrão (${padrao})`}>
                                <IconButton size="small" onClick={() => onChange(padrao)} edge="end"><ReplayRoundedIcon fontSize="small" /></IconButton>
                            </Tooltip>
                        </InputAdornment>
                    ) : undefined,
                }}
            />
        </Box>
    );
}

function CampoImagem({ campo, url, pendente, fundoMenu, erro, onEscolher, onRemover }: {
    campo: Arquivo; url: string | null; pendente: boolean; fundoMenu: string; erro?: string;
    onEscolher: (f: File | null) => void; onRemover: () => void;
}) {
    const info = ARQUIVOS[campo];
    const fundo = info.fundo === 'menu' ? fundoMenu : info.fundo === 'claro' ? '#F8FAFC' : '#E2E8F0';
    const id = `arquivo-${campo}`;

    return (
        <Box sx={{ display: 'flex', gap: 2, alignItems: 'center', flexWrap: { xs: 'wrap', sm: 'nowrap' } }}>
            <Box
                sx={{
                    width: 150, height: 72, flexShrink: 0, borderRadius: 2, bgcolor: fundo, border: '1px solid', borderColor: 'divider',
                    display: 'flex', alignItems: 'center', justifyContent: 'center', overflow: 'hidden',
                }}
            >
                {url && campo === 'logo' ? (
                    <AvatarLogo url={url} tamanho={52} />
                ) : url ? (
                    <Box component="img" src={url} alt={info.titulo} sx={{ maxWidth: campo === 'favicon' ? 32 : '88%', maxHeight: campo === 'favicon' ? 32 : '78%', objectFit: 'contain' }} />
                ) : (
                    <Typography variant="caption" sx={{ color: info.fundo === 'menu' ? alpha('#FFFFFF', 0.6) : 'text.disabled' }}>Sem imagem</Typography>
                )}
            </Box>
            <Box sx={{ flex: 1, minWidth: 200 }}>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                    <Typography variant="body2" fontWeight={600}>{info.titulo}</Typography>
                    {pendente && <Chip label="não salvo" size="small" color="warning" variant="outlined" sx={{ height: 20 }} />}
                </Box>
                <Typography variant="caption" color={erro ? 'error' : 'text.secondary'} component="div" sx={{ mb: 1 }}>
                    {erro ?? info.dica}
                </Typography>
                <Box sx={{ display: 'flex', gap: 1 }}>
                    <Button component="label" htmlFor={id} size="small" variant="outlined" startIcon={<UploadRoundedIcon />}>
                        {url ? 'Trocar' : 'Enviar'}
                        <input
                            id={id} type="file" hidden accept={info.aceita}
                            onChange={(e) => { onEscolher(e.target.files?.[0] ?? null); e.target.value = ''; }}
                        />
                    </Button>
                    {url && (
                        <Button size="small" color="error" startIcon={<DeleteOutlineRoundedIcon />} onClick={onRemover}>Remover</Button>
                    )}
                </Box>
            </Box>
        </Box>
    );
}

/** Logo do menu em avatar redondo (igual ao menu lateral); sem logo, o ícone padrão. */
function AvatarLogo({ url, tamanho }: { url: string | null; tamanho: number }) {
    const t = useTheme();

    return (
        <Avatar
            src={url ?? undefined}
            sx={{
                width: tamanho, height: tamanho, flexShrink: 0,
                bgcolor: url ? '#FFFFFF' : undefined,
                background: url ? undefined : `linear-gradient(135deg, ${t.palette.secondary.main}, ${t.palette.secondary.dark})`,
                border: `2px solid ${alpha(t.palette.sidebar.text, 0.18)}`,
                '& .MuiAvatar-img': { objectFit: 'contain', p: `${Math.round(tamanho / 9)}px` },
            }}
        >
            <ElectricBoltRoundedIcon sx={{ fontSize: tamanho * 0.5, color: t.palette.secondary.contrastText }} />
        </Avatar>
    );
}

/** Mini versão da plataforma com a identidade escolhida (menu, topo, botões e tela de login). */
function Previa({ identidade }: { identidade: Identidade }) {
    const tema = useMemo(() => criarTema(identidade), [identidade]);

    return (
        <ThemeProvider theme={tema}>
            <PreviaConteudo identidade={identidade} />
        </ThemeProvider>
    );
}

function PreviaConteudo({ identidade }: { identidade: Identidade }) {
    const t = useTheme();
    const s = t.palette.sidebar;
    const itens = [
        { icone: <DashboardRoundedIcon fontSize="small" />, nome: 'Dashboard' },
        { icone: <ViewKanbanRoundedIcon fontSize="small" />, nome: 'Funil (Kanban)', ativo: true },
        { icone: <PeopleRoundedIcon fontSize="small" />, nome: 'Clientes' },
    ];

    return (
        <Card>
            <CardContent>
                <Titulo titulo="Pré-visualização" subtitulo="Como a plataforma vai ficar" />

                {/* Aba do navegador */}
                <Box sx={{ display: 'inline-flex', alignItems: 'center', gap: 1, px: 1.5, py: 0.75, mb: 1.5, borderRadius: '8px 8px 0 0', bgcolor: '#E2E8F0', maxWidth: '100%' }}>
                    {identidade.favicon_url
                        ? <Box component="img" src={identidade.favicon_url} alt="" sx={{ width: 16, height: 16 }} />
                        : <Box sx={{ width: 16, height: 16, borderRadius: 0.5, bgcolor: '#94A3B8' }} />}
                    <Typography variant="caption" noWrap sx={{ color: '#0F172A' }}>Dashboard - {identidade.nome}</Typography>
                </Box>

                {/* App */}
                <Box sx={{ display: 'flex', borderRadius: 2, overflow: 'hidden', border: '1px solid', borderColor: 'divider', height: 260 }}>
                    <Box sx={{ width: 170, bgcolor: s.bg, display: 'flex', flexDirection: 'column', flexShrink: 0 }}>
                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, p: 1.5, borderBottom: `1px solid ${s.border}` }}>
                            <AvatarLogo url={identidade.logo_url} tamanho={30} />
                            <Box sx={{ minWidth: 0 }}>
                                <Typography noWrap sx={{ fontSize: '0.75rem', fontWeight: 700, color: s.strong, lineHeight: 1.2 }}>{identidade.nome}</Typography>
                                <Typography noWrap sx={{ fontSize: '0.6rem', color: s.textMuted }}>{rotuloTipoUsuario('admin')}</Typography>
                            </Box>
                        </Box>
                        <Box sx={{ p: 0.75 }}>
                            {itens.map((i) => (
                                <Box
                                    key={i.nome}
                                    sx={{
                                        display: 'flex', alignItems: 'center', gap: 1, px: 1, py: 0.75, borderRadius: 1.5, mb: 0.25,
                                        color: i.ativo ? s.strong : s.text, bgcolor: i.ativo ? alpha(t.palette.primary.main, 0.15) : 'transparent',
                                        '& svg': { color: i.ativo ? s.active : s.textMuted, fontSize: 16 },
                                    }}
                                >
                                    {i.icone}
                                    <Typography sx={{ fontSize: '0.72rem', fontWeight: i.ativo ? 600 : 400 }} noWrap>{i.nome}</Typography>
                                </Box>
                            ))}
                        </Box>
                    </Box>

                    <Box sx={{ flex: 1, bgcolor: 'background.default', p: 1.5, minWidth: 0 }}>
                        <Typography sx={{ fontSize: '0.8rem', fontWeight: 700, mb: 1 }}>Funil de vendas</Typography>
                        <Box sx={{ display: 'flex', gap: 0.75, flexWrap: 'wrap', mb: 1.5 }}>
                            <Button size="small" variant="contained" sx={{ fontSize: '0.65rem', py: 0.25 }}>Novo orçamento</Button>
                            <Button size="small" variant="outlined" sx={{ fontSize: '0.65rem', py: 0.25 }}>Filtrar</Button>
                        </Box>
                        <Box sx={{ bgcolor: 'background.paper', borderRadius: 1.5, p: 1, border: '1px solid', borderColor: 'divider' }}>
                            <Typography sx={{ fontSize: '0.7rem', fontWeight: 600 }}>Padaria Bom Jesus</Typography>
                            <Typography sx={{ fontSize: '0.65rem', color: 'primary.main', fontWeight: 600 }}>R$ 38.400</Typography>
                            <Chip label="Contrato" size="small" color="secondary" sx={{ height: 18, fontSize: '0.6rem', mt: 0.5 }} />
                        </Box>
                    </Box>
                </Box>

                {/* Tela de login */}
                <Typography variant="caption" color="text.secondary" component="div" sx={{ mt: 2, mb: 0.75 }}>Tela de login</Typography>
                <Box sx={{ borderRadius: 2, border: '1px solid', borderColor: 'divider', bgcolor: '#F8FAFC', p: 2, textAlign: 'center' }}>
                    {(identidade.logo_clara_url ?? identidade.logo_url)
                        ? <Box component="img" src={identidade.logo_clara_url ?? identidade.logo_url ?? ''} alt="" sx={{ maxHeight: 34, maxWidth: 180, objectFit: 'contain' }} />
                        : <Typography sx={{ fontWeight: 700 }}>{identidade.nome}</Typography>}
                    <Box sx={{ mx: 'auto', mt: 1, mb: 1, width: '70%', height: 8, borderRadius: 1, bgcolor: '#E2E8F0' }} />
                    <Button size="small" variant="contained" sx={{ fontSize: '0.65rem', px: 3 }}>Entrar</Button>
                    <Typography variant="caption" component="div" sx={{ mt: 1, fontSize: '0.6rem' }}>
                        © {new Date().getFullYear()} {identidade.nome}{identidade.rodape ? ` · ${identidade.rodape}` : ''}
                    </Typography>
                </Box>
            </CardContent>
        </Card>
    );
}
