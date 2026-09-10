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
} from '@mui/material';
import EditRoundedIcon from '@mui/icons-material/EditRounded';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { BoolChip } from '@/Components/UI/StatusChip';
import { PageProps } from '@/types';

interface Kit {
    id: number;
    nome: string;
    modelo?: string;
    potencia_kwp: string;
    preco_custo: string;
    ativo: boolean;
}

interface FornecedorDetail {
    id: number;
    nome: string;
    cnpj?: string;
    email?: string;
    telefone?: string;
    celular?: string;
    representante?: string;
    site?: string;
    margem_padrao?: string;
    anotacoes?: string;
    ativo: boolean;
    kits: Kit[];
}

interface Props extends PageProps {
    fornecedor: FornecedorDetail;
}

export default function FornecedoresShow({ fornecedor }: Props) {
    function field(label: string, value?: string | null) {
        return (
            <Box>
                <Typography variant="caption" color="text.secondary">{label}</Typography>
                <Typography variant="body2">{value ?? '—'}</Typography>
            </Box>
        );
    }

    return (
        <AppLayout>
            <Head title={fornecedor.nome} />

            <PageHeader
                title={fornecedor.nome}
                breadcrumbs={[
                    { label: 'Fornecedores', href: route('admin.fornecedores.index') },
                    { label: fornecedor.nome },
                ]}
                action={
                    <Button
                        component={Link}
                        href={route('admin.fornecedores.edit', fornecedor.id)}
                        variant="contained"
                        startIcon={<EditRoundedIcon />}
                    >
                        Editar
                    </Button>
                }
            />

            <Card sx={{ mb: 3 }}>
                <CardHeader title="Dados do Fornecedor" />
                <Divider />
                <CardContent>
                    <Grid container spacing={3}>
                        <Grid size={{ xs: 12, sm: 3 }}>{field('CNPJ', fornecedor.cnpj)}</Grid>
                        <Grid size={{ xs: 12, sm: 3 }}>{field('E-mail', fornecedor.email)}</Grid>
                        <Grid size={{ xs: 12, sm: 3 }}>{field('Telefone', fornecedor.telefone)}</Grid>
                        <Grid size={{ xs: 12, sm: 3 }}>{field('Celular', fornecedor.celular)}</Grid>
                        <Grid size={{ xs: 12, sm: 4 }}>{field('Representante', fornecedor.representante)}</Grid>
                        <Grid size={{ xs: 12, sm: 4 }}>{field('Site', fornecedor.site)}</Grid>
                        <Grid size={{ xs: 12, sm: 2 }}>
                            <Typography variant="caption" color="text.secondary">Margem Padrão</Typography>
                            <Typography variant="body2">
                                {fornecedor.margem_padrao ? `${parseFloat(fornecedor.margem_padrao).toFixed(2)}%` : '—'}
                            </Typography>
                        </Grid>
                        <Grid size={{ xs: 12, sm: 2 }}>
                            <Typography variant="caption" color="text.secondary">Status</Typography>
                            <Box mt={0.5}><BoolChip value={fornecedor.ativo} /></Box>
                        </Grid>
                        {fornecedor.anotacoes && (
                            <Grid size={{ xs: 12 }}>
                                <Typography variant="caption" color="text.secondary">Anotações</Typography>
                                <Typography variant="body2" sx={{ whiteSpace: 'pre-wrap' }}>{fornecedor.anotacoes}</Typography>
                            </Grid>
                        )}
                    </Grid>
                </CardContent>
            </Card>

            <Card>
                <CardHeader title={`Kits deste fornecedor (${fornecedor.kits.length})`} />
                <Divider />
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell>Kit</TableCell>
                            <TableCell>Modelo</TableCell>
                            <TableCell align="right">Potência (kWp)</TableCell>
                            <TableCell align="right">Preço Custo</TableCell>
                            <TableCell>Status</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {fornecedor.kits.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={5} align="center" sx={{ py: 4 }}>
                                    <Typography color="text.secondary">Nenhum kit cadastrado</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                        {fornecedor.kits.map((k) => (
                            <TableRow key={k.id} hover>
                                <TableCell>
                                    <Typography variant="body2" fontWeight={600}>{k.nome}</Typography>
                                </TableCell>
                                <TableCell>
                                    <Typography variant="body2">{k.modelo ?? '—'}</Typography>
                                </TableCell>
                                <TableCell align="right">
                                    <Typography variant="body2">{parseFloat(k.potencia_kwp).toFixed(3)}</Typography>
                                </TableCell>
                                <TableCell align="right">
                                    <Typography variant="body2">
                                        {parseFloat(k.preco_custo).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}
                                    </Typography>
                                </TableCell>
                                <TableCell><BoolChip value={k.ativo} /></TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </Card>
        </AppLayout>
    );
}
