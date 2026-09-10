import React, { useState } from 'react';
import {
    Box,
    Button,
    Card,
    CardContent,
    CardHeader,
    Divider,
    Grid,
    TextField,
    Typography,
} from '@mui/material';
import SaveRoundedIcon from '@mui/icons-material/SaveRounded';
import SettingsRoundedIcon from '@mui/icons-material/SettingsRounded';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { PageProps } from '@/types';

interface ConfigItem {
    id: number;
    chave: string;
    valor: string | null;
    grupo: string;
    descricao?: string;
}

interface Props extends PageProps {
    configs: Record<string, ConfigItem[]>;
}

const grupoLabels: Record<string, string> = {
    empresa: 'Dados da Empresa',
    proposta: 'Proposta Comercial',
    geral: 'Configurações Gerais',
};

export default function SistemaIndex({ configs }: Props) {
    const [values, setValues] = useState<Record<string, string>>(
        Object.fromEntries(
            Object.values(configs)
                .flat()
                .map((c) => [c.chave, c.valor ?? ''])
        )
    );
    const [processing, setProcessing] = useState(false);

    function handleSave() {
        setProcessing(true);
        const allConfigs = Object.values(configs).flat().map((c) => ({
            chave: c.chave,
            valor: values[c.chave] ?? '',
            grupo: c.grupo,
        }));
        router.put(
            route('admin.configuracoes.sistema.update', 'sistema'),
            { configs: allConfigs },
            { onFinish: () => setProcessing(false) }
        );
    }

    function setVal(chave: string, val: string) {
        setValues((prev) => ({ ...prev, [chave]: val }));
    }

    return (
        <AppLayout>
            <Head title="Sistema" />

            <PageHeader
                title="Configurações do Sistema"
                breadcrumbs={[{ label: 'Configurações' }, { label: 'Sistema' }]}
            />

            {Object.entries(configs).map(([grupo, items]) => (
                <Card key={grupo} sx={{ mb: 3 }}>
                    <CardHeader
                        title={grupoLabels[grupo] ?? grupo}
                        avatar={<SettingsRoundedIcon color="primary" />}
                    />
                    <Divider />
                    <CardContent>
                        <Grid container spacing={3}>
                            {items.map((c) => (
                                <Grid key={c.chave} size={{ xs: 12, sm: 6 }}>
                                    <TextField
                                        fullWidth
                                        size="small"
                                        label={c.descricao ?? c.chave}
                                        value={values[c.chave] ?? ''}
                                        onChange={(e) => setVal(c.chave, e.target.value)}
                                        helperText={
                                            <Typography component="span" variant="caption" fontFamily="monospace" color="text.disabled">
                                                {c.chave}
                                            </Typography>
                                        }
                                    />
                                </Grid>
                            ))}
                        </Grid>
                    </CardContent>
                </Card>
            ))}

            {Object.keys(configs).length === 0 && (
                <Card sx={{ p: 4, textAlign: 'center' }}>
                    <SettingsRoundedIcon sx={{ fontSize: 48, color: 'text.disabled', mb: 1 }} />
                    <Typography color="text.secondary">Nenhuma configuração cadastrada</Typography>
                </Card>
            )}

            <Box sx={{ display: 'flex', justifyContent: 'flex-end', mt: 3 }}>
                <Button
                    variant="contained"
                    startIcon={<SaveRoundedIcon />}
                    onClick={handleSave}
                    disabled={processing}
                >
                    Salvar configurações
                </Button>
            </Box>
        </AppLayout>
    );
}
