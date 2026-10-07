import React from 'react';
import {
    Avatar,
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
import AddRoundedIcon from '@mui/icons-material/AddRounded';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { ClienteStatusChip, OrcamentoStatusChip } from '@/Components/UI/StatusChip';
import { PageProps } from '@/types';
import { formatarMoeda } from '@/utils/formatar';

interface Props extends PageProps {
    cliente: {
        id: number;
        tipo_pessoa: 'pf' | 'pj';
        nome?: string;
        razao_social?: string;
        cpf?: string;
        cnpj?: string;
        rg?: string;
        data_nascimento?: string;
        email?: string;
        telefone?: string;
        celular?: string;
        cep?: string;
        rua?: string;
        numero?: string;
        complemento?: string;
        bairro?: string;
        status: string;
        anotacoes?: string;
        created_at: string;
        consultor?: { id: number; name: string };
        cidade?: { id: number; cidade: string; estado: string };
        orcamentos?: Array<{
            id: number;
            status: string;
            preco_total: number;
            geracao_estimada: number;
            created_at: string;
        }>;
        visitas?: Array<{
            id: number;
            status: string;
            data_agendada: string;
        }>;
    };
}

function InfoRow({ label, value }: { label: string; value?: React.ReactNode }) {
    return (
        <Box sx={{ py: 1, borderBottom: '1px solid', borderColor: 'divider', display: 'flex', gap: 2 }}>
            <Typography variant="body2" color="text.secondary" sx={{ minWidth: 160 }}>
                {label}
            </Typography>
            <Typography variant="body2" fontWeight={500}>
                {value ?? '—'}
            </Typography>
        </Box>
    );
}

export default function ClientesShow({ cliente }: Props) {
    const nome = cliente.tipo_pessoa === 'pj' ? cliente.razao_social : cliente.nome;

    return (
        <AppLayout>
            <Head title={nome ?? 'Cliente'} />

            <PageHeader
                title={nome ?? 'Cliente'}
                breadcrumbs={[
                    { label: 'Clientes', href: route('admin.clientes.index') },
                    { label: nome ?? 'Detalhe' },
                ]}
                action={
                    <Button
                        component={Link}
                        href={route('admin.clientes.edit', cliente.id)}
                        variant="outlined"
                        startIcon={<EditRoundedIcon />}
                    >
                        Editar
                    </Button>
                }
            />

            <Grid container spacing={3}>
                {/* Dados principais */}
                <Grid size={{ xs: 12, md: 8 }}>
                    <Card sx={{ mb: 3 }}>
                        <CardHeader
                            title="Dados pessoais"
                            avatar={
                                <Avatar sx={{ bgcolor: 'primary.light', width: 48, height: 48 }}>
                                    {(nome ?? '?').charAt(0).toUpperCase()}
                                </Avatar>
                            }
                        />
                        <Divider />
                        <CardContent>
                            <InfoRow label="Tipo" value={
                                <Chip label={cliente.tipo_pessoa === 'pj' ? 'Pessoa Jurídica' : 'Pessoa Física'} size="small" />
                            } />
                            {cliente.tipo_pessoa === 'pf' ? (
                                <>
                                    <InfoRow label="Nome" value={cliente.nome} />
                                    <InfoRow label="CPF" value={cliente.cpf} />
                                    <InfoRow label="RG" value={cliente.rg} />
                                    <InfoRow label="Nascimento" value={
                                        cliente.data_nascimento
                                            ? new Date(cliente.data_nascimento).toLocaleDateString('pt-BR')
                                            : undefined
                                    } />
                                </>
                            ) : (
                                <>
                                    <InfoRow label="Razão Social" value={cliente.razao_social} />
                                    <InfoRow label="CNPJ" value={cliente.cnpj} />
                                </>
                            )}
                            <InfoRow label="E-mail" value={cliente.email} />
                            <InfoRow label="Telefone" value={cliente.telefone} />
                            <InfoRow label="Celular" value={cliente.celular} />
                        </CardContent>
                    </Card>

                    <Card sx={{ mb: 3 }}>
                        <CardHeader title="Endereço" />
                        <Divider />
                        <CardContent>
                            <InfoRow label="CEP" value={cliente.cep} />
                            <InfoRow label="Logradouro" value={
                                [cliente.rua, cliente.numero, cliente.complemento].filter(Boolean).join(', ')
                            } />
                            <InfoRow label="Bairro" value={cliente.bairro} />
                            <InfoRow label="Cidade" value={
                                cliente.cidade ? `${cliente.cidade.cidade} - ${cliente.cidade.estado}` : undefined
                            } />
                        </CardContent>
                    </Card>

                    {/* Orçamentos */}
                    <Card>
                        <CardHeader
                            title="Orçamentos"
                            action={
                                <Button
                                    size="small"
                                    startIcon={<AddRoundedIcon />}
                                    variant="outlined"
                                    component={Link}
                                    href={route('admin.orcamentos.index') + `?cliente_id=${cliente.id}`}
                                >
                                    Ver todos
                                </Button>
                            }
                        />
                        <Divider />
                        {(!cliente.orcamentos || cliente.orcamentos.length === 0) ? (
                            <CardContent>
                                <Typography color="text.secondary" variant="body2">Nenhum orçamento ainda.</Typography>
                            </CardContent>
                        ) : (
                            <Table size="small">
                                <TableHead>
                                    <TableRow>
                                        <TableCell>#</TableCell>
                                        <TableCell>Status</TableCell>
                                        <TableCell>Valor</TableCell>
                                        <TableCell>Geração</TableCell>
                                        <TableCell>Data</TableCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {cliente.orcamentos.map((o) => (
                                        <TableRow key={o.id} hover>
                                            <TableCell>
                                                <Link href={route('admin.orcamentos.show', o.id)} style={{ color: 'inherit' }}>
                                                    #{o.id}
                                                </Link>
                                            </TableCell>
                                            <TableCell><OrcamentoStatusChip status={o.status as any} /></TableCell>
                                            <TableCell>
                                                {formatarMoeda(o.preco_total)}
                                            </TableCell>
                                            <TableCell>{o.geracao_estimada} kWh/mês</TableCell>
                                            <TableCell>{new Date(o.created_at).toLocaleDateString('pt-BR')}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </Card>
                </Grid>

                {/* Sidebar */}
                <Grid size={{ xs: 12, md: 4 }}>
                    <Card sx={{ mb: 3 }}>
                        <CardHeader title="Informações" />
                        <Divider />
                        <CardContent>
                            <InfoRow label="Status" value={<ClienteStatusChip status={cliente.status} />} />
                            <InfoRow label="Vendedor" value={cliente.consultor?.name} />
                            <InfoRow label="Cadastrado em" value={
                                new Date(cliente.created_at).toLocaleDateString('pt-BR')
                            } />
                        </CardContent>
                    </Card>

                    {cliente.anotacoes && (
                        <Card>
                            <CardHeader title="Anotações" />
                            <Divider />
                            <CardContent>
                                <Typography variant="body2" color="text.secondary" sx={{ whiteSpace: 'pre-wrap' }}>
                                    {cliente.anotacoes}
                                </Typography>
                            </CardContent>
                        </Card>
                    )}
                </Grid>
            </Grid>
        </AppLayout>
    );
}
