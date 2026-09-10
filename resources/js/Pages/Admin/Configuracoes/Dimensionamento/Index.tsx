import React, { useState } from 'react';
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
    TextField,
    Typography,
} from '@mui/material';
import SaveRoundedIcon from '@mui/icons-material/SaveRounded';
import TuneRoundedIcon from '@mui/icons-material/TuneRounded';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { PageProps } from '@/types';

interface Param {
    id: number;
    chave: string;
    nome: string;
    valor: string;
    unidade?: string;
    descricao?: string;
}

interface Props extends PageProps {
    params: Param[];
}

export default function DimensionamentoIndex({ params }: Props) {
    const [values, setValues] = useState<Record<number, string>>(
        Object.fromEntries(params.map((p) => [p.id, p.valor]))
    );
    const [processing, setProcessing] = useState(false);

    function handleSave() {
        setProcessing(true);
        router.put(
            route('admin.configuracoes.dimensionamento.update', 'dimensionamento'),
            { params: params.map((p) => ({ id: p.id, valor: values[p.id] ?? p.valor })) },
            { onFinish: () => setProcessing(false) }
        );
    }

    return (
        <AppLayout>
            <Head title="Dimensionamento" />

            <PageHeader
                title="Parâmetros de Dimensionamento"
                breadcrumbs={[{ label: 'Configurações' }, { label: 'Dimensionamento' }]}
            />

            <Card>
                <CardHeader
                    title="Parâmetros técnicos"
                    subheader="Estes valores afetam o cálculo de potência e geração estimada dos orçamentos."
                    avatar={<TuneRoundedIcon color="primary" />}
                />
                <Divider />
                <CardContent>
                    <Table>
                        <TableHead>
                            <TableRow>
                                <TableCell>Parâmetro</TableCell>
                                <TableCell>Descrição</TableCell>
                                <TableCell sx={{ width: 200 }}>Valor</TableCell>
                                <TableCell sx={{ width: 100 }}>Unidade</TableCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {params.map((p) => (
                                <TableRow key={p.id} hover>
                                    <TableCell>
                                        <Typography variant="body2" fontWeight={600}>{p.nome}</Typography>
                                        <Typography variant="caption" color="text.secondary" fontFamily="monospace">
                                            {p.chave}
                                        </Typography>
                                    </TableCell>
                                    <TableCell>
                                        <Typography variant="body2" color="text.secondary">
                                            {p.descricao ?? '—'}
                                        </Typography>
                                    </TableCell>
                                    <TableCell>
                                        <TextField
                                            size="small"
                                            fullWidth
                                            value={values[p.id] ?? p.valor}
                                            onChange={(e) => setValues((prev) => ({ ...prev, [p.id]: e.target.value }))}
                                        />
                                    </TableCell>
                                    <TableCell>
                                        <Chip label={p.unidade ?? '—'} size="small" variant="outlined" />
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <Box sx={{ display: 'flex', justifyContent: 'flex-end', mt: 3 }}>
                <Button
                    variant="contained"
                    startIcon={<SaveRoundedIcon />}
                    onClick={handleSave}
                    disabled={processing}
                >
                    Salvar parâmetros
                </Button>
            </Box>
        </AppLayout>
    );
}
