import React from 'react';
import {
    Alert,
    Box,
    Button,
    Card,
    CardContent,
    CardHeader,
    Chip,
    Divider,
    Grid,
    Typography,
} from '@mui/material';
import SyncRoundedIcon from '@mui/icons-material/SyncRounded';
import CheckCircleRoundedIcon from '@mui/icons-material/CheckCircleRounded';
import ErrorRoundedIcon from '@mui/icons-material/ErrorRounded';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { PageProps } from '@/types';

interface Fornecedor {
    id: number;
    nome: string;
    ativo: boolean;
}

interface UltimaIntegracao {
    id: number;
    status: 'iniciado' | 'concluido' | 'erro';
    itens_importados: number;
    itens_atualizados: number;
    itens_desativados: number;
    iniciado_em: string;
    finalizado_em?: string;
    alertas?: string;
}

interface Props extends PageProps {
    fornecedor?: Fornecedor;
    ultima_integracao?: UltimaIntegracao;
    disponivel: boolean;
}

export default function AldoIndex({ fornecedor, ultima_integracao, disponivel }: Props) {
    const [loading, setLoading] = React.useState(false);

    function handleIntegrar() {
        if (!confirm('Iniciar integração com a Aldo? Isso pode levar alguns minutos.')) return;
        setLoading(true);
        router.post(route('admin.integracoes.aldo.integrar'), {}, {
            onFinish: () => setLoading(false),
        });
    }

    return (
        <AppLayout>
            <Head title="Integração Aldo" />

            <PageHeader
                title="Integração Aldo"
                breadcrumbs={[{ label: 'Integrações' }, { label: 'Aldo' }]}
                action={
                    <Button
                        variant="contained"
                        startIcon={<SyncRoundedIcon />}
                        onClick={handleIntegrar}
                        disabled={loading || !disponivel}
                    >
                        {loading ? 'Processando...' : 'Executar integração'}
                    </Button>
                }
            />

            {disponivel ? (
                <Alert severity="info" sx={{ mb: 3 }}>
                    A integração Aldo sincroniza o catálogo de produtos via download de arquivo ZIP/XML do distribuidor.
                    Os produtos são inseridos ou atualizados conforme os mapeamentos configurados.
                </Alert>
            ) : (
                <Alert severity="warning" sx={{ mb: 3 }}>
                    Integração ainda não disponível: aguardando a especificação do feed da Aldo
                    (URL, credenciais e formato do arquivo ZIP/XML). Cadastre os produtos pelo Catálogo enquanto isso.
                </Alert>
            )}

            <Grid container spacing={3}>
                <Grid size={{ xs: 12, md: 4 }}>
                    <Card>
                        <CardHeader title="Fornecedor" />
                        <Divider />
                        <CardContent>
                            {fornecedor ? (
                                <Box>
                                    <Typography variant="body1" fontWeight={600}>{fornecedor.nome}</Typography>
                                    <Box mt={1}>
                                        <Chip
                                            label={fornecedor.ativo ? 'Ativo' : 'Inativo'}
                                            color={fornecedor.ativo ? 'success' : 'default'}
                                            size="small"
                                        />
                                    </Box>
                                </Box>
                            ) : (
                                <Typography color="text.secondary">Fornecedor Aldo não cadastrado</Typography>
                            )}
                        </CardContent>
                    </Card>
                </Grid>

                <Grid size={{ xs: 12, md: 8 }}>
                    <Card>
                        <CardHeader title="Última Execução" />
                        <Divider />
                        <CardContent>
                            {ultima_integracao ? (
                                <Grid container spacing={3}>
                                    <Grid size={{ xs: 12 }}>
                                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                                            {ultima_integracao.status === 'concluido'
                                                ? <CheckCircleRoundedIcon color="success" />
                                                : <ErrorRoundedIcon color="error" />}
                                            <Chip
                                                label={ultima_integracao.status === 'concluido' ? 'Concluído' : ultima_integracao.status === 'erro' ? 'Erro' : 'Em andamento'}
                                                color={ultima_integracao.status === 'concluido' ? 'success' : ultima_integracao.status === 'erro' ? 'error' : 'warning'}
                                                size="small"
                                            />
                                        </Box>
                                    </Grid>
                                    <Grid size={{ xs: 6, sm: 3 }}>
                                        <Typography variant="caption" color="text.secondary">Importados</Typography>
                                        <Typography variant="h6">{ultima_integracao.itens_importados}</Typography>
                                    </Grid>
                                    <Grid size={{ xs: 6, sm: 3 }}>
                                        <Typography variant="caption" color="text.secondary">Atualizados</Typography>
                                        <Typography variant="h6">{ultima_integracao.itens_atualizados}</Typography>
                                    </Grid>
                                    <Grid size={{ xs: 6, sm: 3 }}>
                                        <Typography variant="caption" color="text.secondary">Desativados</Typography>
                                        <Typography variant="h6">{ultima_integracao.itens_desativados}</Typography>
                                    </Grid>
                                    <Grid size={{ xs: 6, sm: 3 }}>
                                        <Typography variant="caption" color="text.secondary">Iniciado em</Typography>
                                        <Typography variant="body2">
                                            {new Date(ultima_integracao.iniciado_em).toLocaleString('pt-BR')}
                                        </Typography>
                                    </Grid>
                                    {ultima_integracao.alertas && (
                                        <Grid size={{ xs: 12 }}>
                                            <Alert severity="warning" sx={{ mt: 1 }}>
                                                {ultima_integracao.alertas}
                                            </Alert>
                                        </Grid>
                                    )}
                                </Grid>
                            ) : (
                                <Typography color="text.secondary">Nenhuma execução registrada</Typography>
                            )}
                        </CardContent>
                    </Card>
                </Grid>
            </Grid>
        </AppLayout>
    );
}
