import React from 'react';
import {
    Box,
    Button,
    Card,
    CardContent,
    CardHeader,
    Chip,
    Divider,
    Grid,
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableRow,
    Typography,
    alpha,
} from '@mui/material';
import EditRoundedIcon from '@mui/icons-material/EditRounded';
import ArrowBackRoundedIcon from '@mui/icons-material/ArrowBackRounded';
import ElectricBoltRoundedIcon from '@mui/icons-material/ElectricBoltRounded';
import LocalOfferRoundedIcon from '@mui/icons-material/LocalOfferRounded';
import SolarPowerRoundedIcon from '@mui/icons-material/SolarPowerRounded';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { PageProps } from '@/types';
import { formatarMoeda, type Numerico } from '@/utils/formatar';

interface Componente {
    id: number;
    nome: string;
    modelo?: string;
    categoria?: { nome: string };
    marca?: { nome: string };
    pivot: { quantidade: number; observacao?: string };
}

interface Kit {
    id: number;
    nome: string;
    modelo?: string;
    sku?: string;
    potencia_kwp: number;
    categoria: string;
    tensao?: number;
    inclui_trafo: boolean;
    preco_custo?: number;
    ativo: boolean;
    ativo_fornecedor: boolean;
    observacoes?: string;
    fornecedor?: { id: number; nome: string; email?: string; telefone?: string };
    estrutura?: { id: number; nome: string };
    componentes: Componente[];
    created_at: string;
    updated_at: string;
}

interface Props extends PageProps { kit: Kit; categorias: Record<string, string> }

function InfoRow({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <Box sx={{ display: 'flex', py: 1.25, borderBottom: '1px solid', borderColor: 'divider', '&:last-child': { borderBottom: 'none' } }}>
            <Typography variant="body2" color="text.secondary" sx={{ minWidth: 160, fontWeight: 500 }}>
                {label}
            </Typography>
            <Typography variant="body2">{value ?? '—'}</Typography>
        </Box>
    );
}

export default function KitsShow({ kit, categorias }: Props) {
    const fmtMoney = (v?: Numerico) => formatarMoeda(v);

    return (
        <AppLayout>
            <Head title={kit.nome} />

            <PageHeader
                title={kit.nome}
                subtitle={`${kit.potencia_kwp} kWp${kit.estrutura ? ` — ${kit.estrutura.nome}` : ''}`}
                breadcrumbs={[
                    { label: 'Produtos' },
                    { label: 'Kits Solares', href: route('admin.produtos.kits.index') },
                    { label: kit.nome },
                ]}
                action={
                    <Box sx={{ display: 'flex', gap: 1 }}>
                        <Button component={Link} href={route('admin.produtos.kits.index')} startIcon={<ArrowBackRoundedIcon />}>
                            Voltar
                        </Button>
                        <Button
                            component={Link}
                            href={route('admin.produtos.kits.edit', kit.id)}
                            variant="contained"
                            startIcon={<EditRoundedIcon />}
                        >
                            Editar
                        </Button>
                    </Box>
                }
            />

            <Grid container spacing={3}>
                {/* Destaque */}
                <Grid size={{ xs: 12 }}>
                    <Grid container spacing={3}>
                        {[
                            { label: 'Potência', value: `${kit.potencia_kwp} kWp`, color: '#f59e0b', icon: <ElectricBoltRoundedIcon /> },
                            { label: 'Preço de Custo', value: fmtMoney(kit.preco_custo), color: '#6366f1', icon: <LocalOfferRoundedIcon /> },
                            { label: 'Tipo de sistema', value: categorias[kit.categoria] ?? kit.categoria, color: '#22c55e', icon: <SolarPowerRoundedIcon /> },
                            { label: 'Tensão de saída', value: kit.tensao ? `${kit.tensao} V` : '—', color: '#3b82f6', icon: <ElectricBoltRoundedIcon /> },
                        ].map((item) => (
                            <Grid key={item.label} size={{ xs: 6, sm: 3 }}>
                                <Card>
                                    <CardContent sx={{ p: '16px !important' }}>
                                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                                            <Box sx={{ width: 40, height: 40, borderRadius: 2, bgcolor: alpha(item.color, 0.12), display: 'flex', alignItems: 'center', justifyContent: 'center', color: item.color, '& svg': { fontSize: 20 } }}>
                                                {item.icon}
                                            </Box>
                                            <Box>
                                                <Typography variant="h6" fontWeight={700} sx={{ lineHeight: 1.2 }}>{item.value}</Typography>
                                                <Typography variant="caption" color="text.secondary">{item.label}</Typography>
                                            </Box>
                                        </Box>
                                    </CardContent>
                                </Card>
                            </Grid>
                        ))}
                    </Grid>
                </Grid>

                {/* Detalhes */}
                <Grid size={{ xs: 12, md: 7 }}>
                    <Card>
                        <CardHeader title="Informações do Kit" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                        <Divider />
                        <CardContent>
                            <InfoRow label="Modelo" value={kit.modelo} />
                            <InfoRow label="SKU" value={kit.sku} />
                            <InfoRow label="Inclui Trafo" value={kit.inclui_trafo ? 'Sim' : 'Não'} />
                            <InfoRow label="Fornecedor" value={kit.fornecedor?.nome} />
                            <InfoRow label="Estrutura" value={kit.estrutura?.nome} />
                            <InfoRow
                                label="Status"
                                value={
                                    <Box sx={{ display: 'flex', gap: 1 }}>
                                        <Chip label={kit.ativo ? 'Ativo' : 'Inativo'} size="small" color={kit.ativo ? 'success' : 'default'} variant={kit.ativo ? 'filled' : 'outlined'} />
                                        {!kit.ativo_fornecedor && <Chip label="Forn. inativo" size="small" color="warning" variant="outlined" />}
                                    </Box>
                                }
                            />
                            {kit.observacoes && (
                                <InfoRow label="Observações" value={kit.observacoes} />
                            )}
                        </CardContent>
                    </Card>

                    {/* Componentes */}
                    {kit.componentes.length > 0 && (
                        <Card sx={{ mt: 3 }}>
                            <CardHeader
                                title="Componentes do Kit"
                                titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }}
                                subheader={`${kit.componentes.length} produto(s)`}
                            />
                            <Divider />
                            <Table size="small">
                                <TableHead>
                                    <TableRow>
                                        <TableCell>Produto</TableCell>
                                        <TableCell>Categoria</TableCell>
                                        <TableCell>Marca</TableCell>
                                        <TableCell align="right">Qtd.</TableCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {kit.componentes.map((c) => (
                                        <TableRow key={c.id} hover>
                                            <TableCell>
                                                <Typography variant="body2" fontWeight={500}>{c.nome}</Typography>
                                                {c.modelo && <Typography variant="caption" color="text.secondary">{c.modelo}</Typography>}
                                            </TableCell>
                                            <TableCell>
                                                <Typography variant="body2">{c.categoria?.nome ?? '—'}</Typography>
                                            </TableCell>
                                            <TableCell>
                                                <Typography variant="body2">{c.marca?.nome ?? '—'}</Typography>
                                            </TableCell>
                                            <TableCell align="right">
                                                <Chip label={c.pivot.quantidade} size="small" variant="outlined" />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </Card>
                    )}
                </Grid>

                {/* Fornecedor info */}
                <Grid size={{ xs: 12, md: 5 }}>
                    {kit.fornecedor && (
                        <Card>
                            <CardHeader title="Fornecedor" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                            <Divider />
                            <CardContent>
                                <Typography variant="body1" fontWeight={600} gutterBottom>{kit.fornecedor.nome}</Typography>
                                {kit.fornecedor.email && (
                                    <Typography variant="body2" color="text.secondary">{kit.fornecedor.email}</Typography>
                                )}
                                {kit.fornecedor.telefone && (
                                    <Typography variant="body2" color="text.secondary">{kit.fornecedor.telefone}</Typography>
                                )}
                            </CardContent>
                        </Card>
                    )}

                    <Card sx={{ mt: kit.fornecedor ? 2 : 0 }}>
                        <CardHeader title="Registro" titleTypographyProps={{ variant: 'subtitle1', fontWeight: 600 }} />
                        <Divider />
                        <CardContent>
                            <InfoRow label="Criado em" value={new Date(kit.created_at).toLocaleDateString('pt-BR')} />
                            <InfoRow label="Atualizado em" value={new Date(kit.updated_at).toLocaleDateString('pt-BR')} />
                        </CardContent>
                    </Card>
                </Grid>
            </Grid>
        </AppLayout>
    );
}
