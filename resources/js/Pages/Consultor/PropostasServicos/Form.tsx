import React, { useEffect } from 'react';
import {
    Alert, Autocomplete, Box, Button, Card, CardContent, CardHeader,
    Divider, FormControl, Grid, InputAdornment, InputLabel,
    MenuItem, Select, TextField, Typography,
} from '@mui/material';
import SaveRoundedIcon from '@mui/icons-material/SaveRounded';
import ArticleRoundedIcon from '@mui/icons-material/ArticleRounded';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { PageProps } from '@/types';

interface ClienteOpt { id: number; tipo_pessoa: string; nome?: string; razao_social?: string }
interface PropostaData {
    id?: number; cliente_id: number; titulo: string; valor: number;
    validade: string; conteudo: string; observacoes?: string; status: string;
    cliente?: ClienteOpt;
}
interface Props extends PageProps { clientes: ClienteOpt[]; proposta: PropostaData | null }

// Placeholders para agilizar a escrita do conteúdo
const TEMPLATES = [
    { label: 'Instalação Solar', texto: `Prezado(a) cliente,\n\nÉ com satisfação que apresentamos nossa proposta para instalação do sistema de energia solar fotovoltaica em sua propriedade.\n\nSERVIÇOS INCLUÍDOS:\n• Fornecimento e instalação dos módulos fotovoltaicos\n• Fornecimento e instalação do inversor/microinversor\n• Instalação da estrutura de fixação\n• Cabeamento CC e CA\n• Quadro de distribuição e proteções\n• Homologação junto à concessionária de energia\n• Vistoria e comissionamento do sistema\n• Treinamento de operação\n\nGARANTIAS:\n• Módulos fotovoltaicos: 25 anos de desempenho\n• Inversor: conforme fabricante\n• Serviços de instalação: 12 meses\n\nCondições de pagamento a combinar.\n\nAtenciosamente,\n[Nome do Consultor]` },
    { label: 'Manutenção Preventiva', texto: `Prezado(a) cliente,\n\nApresentamos proposta para serviço de manutenção preventiva do sistema fotovoltaico.\n\nSERVIÇOS:\n• Limpeza e inspeção dos módulos fotovoltaicos\n• Verificação das conexões elétricas\n• Análise de desempenho via monitoramento\n• Relatório técnico de vistoria\n• Verificação de parafusos e estrutura\n\nPERIODICIDADE: Semestral\n\nObservações:\n[Descreva condições específicas]\n\nAtenciosamente,\n[Nome do Consultor]` },
    { label: 'Ampliação de Sistema', texto: `Prezado(a) cliente,\n\nPropomos a ampliação do sistema fotovoltaico existente para atender ao aumento de demanda.\n\nESCOPO DA AMPLIAÇÃO:\n• Adição de [X] módulos de [X] Wp\n• Verificação da capacidade do inversor atual\n• Adequação elétrica se necessário\n• Atualização do projeto junto à concessionária\n\nPré-requisitos:\n• Sistema atual em pleno funcionamento\n• Área disponível no telhado/solo\n\nAtenciosamente,\n[Nome do Consultor]` },
];

export default function PropostasServicosForm({ clientes, proposta }: Props) {
    const isEdit = !!proposta;

    const { data, setData, post, put, processing, errors } = useForm({
        cliente_id:  String(proposta?.cliente_id ?? ''),
        titulo:      proposta?.titulo ?? '',
        valor:       String(proposta?.valor ?? ''),
        validade:    proposta?.validade ?? '',
        conteudo:    proposta?.conteudo ?? '',
        observacoes: proposta?.observacoes ?? '',
        status:      proposta?.status ?? 'rascunho',
    });

    const clienteSel = clientes.find((c) => String(c.id) === data.cliente_id) ?? null;
    const nomeCli = (c: ClienteOpt) => c.tipo_pessoa === 'pj' ? (c.razao_social ?? '') : (c.nome ?? '');
    const fmtMoney = (v: string) => {
        const n = parseFloat(v.replace(',', '.'));
        return isNaN(n) ? '' : n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    };

    // Define validade padrão: hoje + 30 dias
    useEffect(() => {
        if (!isEdit && !data.validade) {
            const d = new Date();
            d.setDate(d.getDate() + 30);
            setData('validade', d.toISOString().split('T')[0]);
        }
    }, []);

    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (isEdit) {
            put(route('consultor.proposta-servicos.update', proposta!.id));
        } else {
            post(route('consultor.proposta-servicos.store'));
        }
    }

    function aplicarTemplate(texto: string) {
        setData('conteudo', texto);
    }

    return (
        <AppLayout>
            <Head title={isEdit ? 'Editar Proposta' : 'Nova Proposta de Serviço'} />
            <PageHeader
                title={isEdit ? 'Editar Proposta' : 'Nova Proposta de Serviço'}
                subtitle="Preencha os dados para gerar a proposta"
                breadcrumbs={[
                    { label: 'Propostas de Serviços', href: route('consultor.proposta-servicos.index') },
                    { label: isEdit ? 'Editar' : 'Nova' },
                ]}
            />

            <Box component="form" onSubmit={submit}>
                <Grid container spacing={3}>
                    {/* Coluna principal */}
                    <Grid size={{ xs: 12, md: 8 }}>
                        {/* 1. Identificação */}
                        <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                            <CardHeader title="1. Identificação" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                            <Divider />
                            <CardContent>
                                <Grid container spacing={3}>
                                    <Grid size={{ xs: 12 }}>
                                        <Autocomplete
                                            options={clientes}
                                            getOptionLabel={nomeCli}
                                            value={clienteSel}
                                            onChange={(_, v) => setData('cliente_id', v ? String(v.id) : '')}
                                            renderInput={(p) => (
                                                <TextField {...p} label="Cliente *" error={!!errors.cliente_id}
                                                    helperText={errors.cliente_id} />
                                            )}
                                            renderOption={(p, c) => (
                                                <Box component="li" {...p} key={c.id}>
                                                    <Box>
                                                        <Typography variant="body2">{nomeCli(c)}</Typography>
                                                        <Typography variant="caption" color="text.secondary">
                                                            {c.tipo_pessoa === 'pj' ? 'Pessoa Jurídica' : 'Pessoa Física'}
                                                        </Typography>
                                                    </Box>
                                                </Box>
                                            )}
                                            noOptionsText="Nenhum cliente"
                                        />
                                    </Grid>
                                    <Grid size={{ xs: 12 }}>
                                        <TextField
                                            fullWidth label="Título da Proposta *"
                                            placeholder="Ex.: Instalação de sistema solar 5 kWp"
                                            value={data.titulo}
                                            onChange={(e) => setData('titulo', e.target.value)}
                                            error={!!errors.titulo} helperText={errors.titulo}
                                        />
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <TextField
                                            fullWidth label="Valor da Proposta *" type="number"
                                            inputProps={{ min: 0, step: 0.01 }}
                                            InputProps={{ startAdornment: <InputAdornment position="start">R$</InputAdornment> }}
                                            value={data.valor}
                                            onChange={(e) => setData('valor', e.target.value)}
                                            error={!!errors.valor} helperText={errors.valor || (data.valor ? fmtMoney(data.valor) : '')}
                                        />
                                    </Grid>
                                    <Grid size={{ xs: 12, sm: 6 }}>
                                        <TextField
                                            fullWidth label="Válida até *" type="date"
                                            InputLabelProps={{ shrink: true }}
                                            value={data.validade}
                                            onChange={(e) => setData('validade', e.target.value)}
                                            error={!!errors.validade} helperText={errors.validade}
                                        />
                                    </Grid>
                                </Grid>
                            </CardContent>
                        </Card>

                        {/* 2. Conteúdo */}
                        <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                            <CardHeader
                                title="2. Conteúdo da Proposta"
                                titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }}
                                subheader="Descreva os serviços, condições e informações relevantes"
                                action={
                                    <Box sx={{ display: 'flex', gap: 1, flexWrap: 'wrap', pr: 1 }}>
                                        {TEMPLATES.map((t) => (
                                            <Button key={t.label} size="small" variant="outlined"
                                                onClick={() => aplicarTemplate(t.texto)}
                                                sx={{ fontSize: '0.72rem' }}>
                                                {t.label}
                                            </Button>
                                        ))}
                                    </Box>
                                }
                            />
                            <Divider />
                            <CardContent>
                                <TextField
                                    fullWidth multiline rows={18} label="Conteúdo *"
                                    placeholder="Descreva detalhadamente os serviços, escopo, garantias, condições de pagamento..."
                                    value={data.conteudo}
                                    onChange={(e) => setData('conteudo', e.target.value)}
                                    error={!!errors.conteudo} helperText={errors.conteudo}
                                    sx={{ '& .MuiOutlinedInput-root': { fontFamily: 'monospace', fontSize: '0.875rem' } }}
                                />
                                <Typography variant="caption" color="text.secondary" sx={{ mt: 1, display: 'block' }}>
                                    {data.conteudo.length} caracteres · Use os botões acima para inserir um modelo
                                </Typography>
                            </CardContent>
                        </Card>
                    </Grid>

                    {/* Sidebar */}
                    <Grid size={{ xs: 12, md: 4 }}>
                        {/* Status */}
                        <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                            <CardHeader title="Status" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                            <Divider />
                            <CardContent>
                                <FormControl fullWidth>
                                    <InputLabel>Status da proposta</InputLabel>
                                    <Select value={data.status} label="Status da proposta"
                                        onChange={(e) => setData('status', e.target.value)}>
                                        <MenuItem value="rascunho">📝 Rascunho</MenuItem>
                                        <MenuItem value="enviada">📤 Enviada ao cliente</MenuItem>
                                        {isEdit && <MenuItem value="aceita">✅ Aceita</MenuItem>}
                                        {isEdit && <MenuItem value="recusada">❌ Recusada</MenuItem>}
                                        {isEdit && <MenuItem value="expirada">⏳ Expirada</MenuItem>}
                                    </Select>
                                </FormControl>
                                <Alert severity="info" sx={{ mt: 2 }}>
                                    {data.status === 'rascunho' && 'A proposta está sendo elaborada. Não enviada ao cliente ainda.'}
                                    {data.status === 'enviada' && 'Proposta enviada. Aguardando resposta do cliente.'}
                                    {data.status === 'aceita' && 'Proposta foi aceita pelo cliente. '}
                                    {data.status === 'recusada' && 'Proposta recusada pelo cliente.'}
                                    {data.status === 'expirada' && 'O prazo de validade expirou.'}
                                </Alert>
                            </CardContent>
                        </Card>

                        {/* Resumo */}
                        {(data.titulo || data.valor) && (
                            <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                                <CardHeader title="Resumo" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                                <Divider />
                                <CardContent>
                                    <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1.5 }}>
                                        {data.titulo && (
                                            <Box>
                                                <Typography variant="caption" color="text.secondary">Título</Typography>
                                                <Typography variant="body2" fontWeight={600}>{data.titulo}</Typography>
                                            </Box>
                                        )}
                                        {clienteSel && (
                                            <Box>
                                                <Typography variant="caption" color="text.secondary">Cliente</Typography>
                                                <Typography variant="body2" fontWeight={600}>{nomeCli(clienteSel)}</Typography>
                                            </Box>
                                        )}
                                        {data.valor && (
                                            <Box>
                                                <Typography variant="caption" color="text.secondary">Valor</Typography>
                                                <Typography variant="h5" fontWeight={800} color="success.main">
                                                    {fmtMoney(data.valor)}
                                                </Typography>
                                            </Box>
                                        )}
                                        {data.validade && (
                                            <Box>
                                                <Typography variant="caption" color="text.secondary">Válida até</Typography>
                                                <Typography variant="body2" fontWeight={600}>
                                                    {new Date(data.validade + 'T12:00:00').toLocaleDateString('pt-BR', { day: '2-digit', month: 'long', year: 'numeric' })}
                                                </Typography>
                                            </Box>
                                        )}
                                    </Box>
                                </CardContent>
                            </Card>
                        )}

                        {/* Observações internas */}
                        <Card variant="outlined" sx={{ mb: 3, borderRadius: 2 }}>
                            <CardHeader title="Observações Internas" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }}
                                subheader="Não aparece na proposta" />
                            <Divider />
                            <CardContent>
                                <TextField fullWidth multiline rows={4} label="Anotações"
                                    placeholder="Informações internas, histórico da negociação..."
                                    value={data.observacoes}
                                    onChange={(e) => setData('observacoes', e.target.value)}
                                    error={!!errors.observacoes} />
                            </CardContent>
                        </Card>

                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1.5 }}>
                            <Button type="submit" variant="contained" size="large" fullWidth
                                disabled={processing} startIcon={<SaveRoundedIcon />}
                                sx={{ borderRadius: 2.5, py: 1.5 }}>
                                {processing ? 'Salvando...' : isEdit ? 'Salvar Alterações' : 'Criar Proposta'}
                            </Button>
                            <Button component={Link} href={route('consultor.proposta-servicos.index')}
                                variant="outlined" fullWidth color="inherit">
                                Cancelar
                            </Button>
                        </Box>
                    </Grid>
                </Grid>
            </Box>
        </AppLayout>
    );
}
