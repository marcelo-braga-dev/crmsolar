import React from 'react';
import {
    Alert, Box, Button, Card, CardContent, CardHeader, Chip,
    Divider, Grid, Table, TableBody, TableCell, TableHead,
    TableRow, Tooltip, Typography,
} from '@mui/material';
import SyncRoundedIcon from '@mui/icons-material/SyncRounded';
import CheckCircleRoundedIcon from '@mui/icons-material/CheckCircleRounded';
import ErrorRoundedIcon from '@mui/icons-material/ErrorRounded';
import AccessTimeRoundedIcon from '@mui/icons-material/AccessTimeRounded';
import WarningAmberRoundedIcon from '@mui/icons-material/WarningAmberRounded';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { PageProps } from '@/types';

interface FornecedorInfo { id: number; nome: string; kits_count: number; }
interface HistoricoRow {
    id: number; status: 'iniciado' | 'concluido' | 'erro';
    itens_importados: number; itens_atualizados: number; itens_desativados: number;
    alertas?: string; iniciado_em: string; finalizado_em?: string; duracao_s?: number;
}
interface Props extends PageProps {
    fornecedor?: FornecedorInfo;
    historicos: HistoricoRow[];
    configurado: boolean;
    demonstracao: boolean;
    /** Nome exibido: na demonstração o nome real da distribuidora é confidencial e vem "Distribuidora". */
    distribuidora: string;
    variaveis_credenciais: string[];
}

function fmtDuracao(s?: number | null): string {
    if (!s) return '—';
    if (s < 60) return `${s}s`;
    return `${Math.floor(s / 60)}min ${s % 60}s`;
}

function StatusChip({ status }: { status: HistoricoRow['status'] }) {
    const map: Record<string, { label: string; color: 'success' | 'error' | 'warning' }> = {
        concluido: { label: 'Concluído', color: 'success' },
        erro:      { label: 'Erro',      color: 'error' },
        iniciado:  { label: 'Em andamento', color: 'warning' },
    };
    const { label, color } = map[status] ?? { label: status, color: 'warning' };
    return <Chip label={label} color={color} size="small" />;
}

export default function DistribuidoraIndex({ fornecedor, historicos, configurado, demonstracao, distribuidora, variaveis_credenciais }: Props) {
    const titulo = demonstracao ? 'Integração com a distribuidora' : `Integração ${distribuidora}`;
    const [loading, setLoading] = React.useState(false);
    const ultima = historicos[0] ?? null;

    function handleIntegrar() {
        if (!confirm(`Iniciar integração com a ${distribuidora}? Pode levar alguns minutos.`)) return;
        setLoading(true);
        router.post(route('admin.integracoes.distribuidora.integrar'), {}, {
            onFinish: () => setLoading(false),
        });
    }

    return (
        <AppLayout>
            <Head title={titulo} />
            <PageHeader
                title={titulo}
                subtitle="Sincroniza kits e preços via API do distribuidor"
                breadcrumbs={[{ label: 'Integrações' }, { label: distribuidora }]}
                action={
                    <Button
                        variant="contained" startIcon={<SyncRoundedIcon />}
                        onClick={handleIntegrar}
                        disabled={loading || !configurado}
                    >
                        {loading ? 'Sincronizando...' : 'Executar agora'}
                    </Button>
                }
            />

            {demonstracao ? (
                <Alert severity="info" sx={{ mb: 3 }}>
                    Nesta demonstração a sincronização com a distribuidora fica desligada. Os kits e o histórico abaixo são da base de exemplo.
                </Alert>
            ) : !configurado && (
                <Alert severity="error" sx={{ mb: 3 }}>
                    Credenciais não configuradas. Adicione {variaveis_credenciais.map((v, i) => (
                        <React.Fragment key={v}>{i > 0 && ' e '}<strong>{v}</strong></React.Fragment>
                    ))} no arquivo <code>.env</code>.
                </Alert>
            )}

            <Grid container spacing={3} sx={{ mb: 3 }}>
                {/* Fornecedor */}
                <Grid size={{ xs: 12, md: 4 }}>
                    <Card variant="outlined" sx={{ borderRadius: 2, height: '100%' }}>
                        <CardHeader title="Fornecedor" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                        <Divider />
                        <CardContent>
                            {fornecedor ? (
                                <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1.5 }}>
                                    <Typography variant="h6" fontWeight={700}>{fornecedor.nome}</Typography>
                                    <Box sx={{ display: 'flex', gap: 2 }}>
                                        <Box>
                                            <Typography variant="caption" color="text.secondary">Kits no catálogo</Typography>
                                            <Typography variant="h5" fontWeight={700} color="primary.main">{fornecedor.kits_count}</Typography>
                                        </Box>
                                    </Box>
                                    <Typography variant="caption" color="text.secondary">
                                        Sincroniza automaticamente toda madrugada às 04h.
                                    </Typography>
                                </Box>
                            ) : (
                                <Alert severity="warning" sx={{ mt: 0 }}>
                                    Fornecedor da integração não encontrado. Cadastre em <strong>Fornecedores</strong> um fornecedor com "{distribuidora}" no nome antes de integrar.
                                </Alert>
                            )}
                        </CardContent>
                    </Card>
                </Grid>

                {/* Última execução */}
                <Grid size={{ xs: 12, md: 8 }}>
                    <Card variant="outlined" sx={{ borderRadius: 2, height: '100%' }}>
                        <CardHeader title="Última Execução" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                        <Divider />
                        <CardContent>
                            {ultima ? (
                                <Box>
                                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, mb: 2 }}>
                                        {ultima.status === 'concluido'
                                            ? <CheckCircleRoundedIcon color="success" />
                                            : ultima.status === 'erro'
                                                ? <ErrorRoundedIcon color="error" />
                                                : <AccessTimeRoundedIcon color="warning" />}
                                        <StatusChip status={ultima.status} />
                                        <Typography variant="caption" color="text.secondary">{ultima.iniciado_em}</Typography>
                                        {ultima.duracao_s != null && (
                                            <Chip icon={<AccessTimeRoundedIcon />} label={fmtDuracao(ultima.duracao_s)} size="small" variant="outlined" />
                                        )}
                                    </Box>

                                    <Grid container spacing={3}>
                                        {[
                                            { label: 'Importados (novos)',    value: ultima.itens_importados,  color: 'success.main' },
                                            { label: 'Atualizados',           value: ultima.itens_atualizados, color: 'info.main' },
                                            { label: 'Desativados (saíram)',  value: ultima.itens_desativados, color: 'text.secondary' },
                                        ].map(({ label, value, color }) => (
                                            <Grid key={label} size={{ xs: 4 }}>
                                                <Typography variant="caption" color="text.secondary">{label}</Typography>
                                                <Typography variant="h5" fontWeight={700} color={color}>{value}</Typography>
                                            </Grid>
                                        ))}
                                    </Grid>

                                    {ultima.alertas && (
                                        <Alert severity="warning" icon={<WarningAmberRoundedIcon />} sx={{ mt: 2 }}>
                                            <Typography variant="body2" sx={{ whiteSpace: 'pre-wrap', maxHeight: 100, overflow: 'auto' }}>
                                                {ultima.alertas}
                                            </Typography>
                                        </Alert>
                                    )}
                                </Box>
                            ) : (
                                <Typography color="text.secondary">Nenhuma execução registrada ainda.</Typography>
                            )}
                        </CardContent>
                    </Card>
                </Grid>
            </Grid>

            {/* Histórico */}
            {historicos.length > 0 && (
                <Card variant="outlined" sx={{ borderRadius: 2 }}>
                    <CardHeader title="Histórico de execuções" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                    <Divider />
                    <Table size="small">
                        <TableHead>
                            <TableRow>
                                <TableCell>Data/hora</TableCell>
                                <TableCell>Status</TableCell>
                                <TableCell align="right">Importados</TableCell>
                                <TableCell align="right">Atualizados</TableCell>
                                <TableCell align="right">Desativados</TableCell>
                                <TableCell align="right">Duração</TableCell>
                                <TableCell>Alertas</TableCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {historicos.map((h) => (
                                <TableRow key={h.id} hover>
                                    <TableCell>
                                        <Typography variant="caption">{h.iniciado_em}</Typography>
                                    </TableCell>
                                    <TableCell><StatusChip status={h.status} /></TableCell>
                                    <TableCell align="right">{h.itens_importados}</TableCell>
                                    <TableCell align="right">{h.itens_atualizados}</TableCell>
                                    <TableCell align="right">{h.itens_desativados}</TableCell>
                                    <TableCell align="right">{fmtDuracao(h.duracao_s)}</TableCell>
                                    <TableCell>
                                        {h.alertas ? (
                                            <Tooltip title={h.alertas} arrow>
                                                <Chip icon={<WarningAmberRoundedIcon />} label="Ver alertas" size="small" color="warning" variant="outlined" sx={{ cursor: 'help' }} />
                                            </Tooltip>
                                        ) : (
                                            <Typography variant="caption" color="text.secondary">—</Typography>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </Card>
            )}
        </AppLayout>
    );
}
