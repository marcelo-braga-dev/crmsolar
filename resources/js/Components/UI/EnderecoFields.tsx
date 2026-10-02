import React, { useCallback, useEffect, useState } from 'react';
import {
    CircularProgress, FormControl, FormHelperText, Grid, InputAdornment, InputLabel,
    MenuItem, Select, TextField, Tooltip,
} from '@mui/material';
import SearchRoundedIcon from '@mui/icons-material/SearchRounded';
import { maskCep } from '@/utils/masks';

export const ESTADOS = [
    { sigla: 'AC', nome: 'Acre' }, { sigla: 'AL', nome: 'Alagoas' }, { sigla: 'AM', nome: 'Amazonas' },
    { sigla: 'AP', nome: 'Amapá' }, { sigla: 'BA', nome: 'Bahia' }, { sigla: 'CE', nome: 'Ceará' },
    { sigla: 'DF', nome: 'Distrito Federal' }, { sigla: 'ES', nome: 'Espírito Santo' }, { sigla: 'GO', nome: 'Goiás' },
    { sigla: 'MA', nome: 'Maranhão' }, { sigla: 'MG', nome: 'Minas Gerais' }, { sigla: 'MS', nome: 'Mato Grosso do Sul' },
    { sigla: 'MT', nome: 'Mato Grosso' }, { sigla: 'PA', nome: 'Pará' }, { sigla: 'PB', nome: 'Paraíba' },
    { sigla: 'PE', nome: 'Pernambuco' }, { sigla: 'PI', nome: 'Piauí' }, { sigla: 'PR', nome: 'Paraná' },
    { sigla: 'RJ', nome: 'Rio de Janeiro' }, { sigla: 'RN', nome: 'Rio Grande do Norte' }, { sigla: 'RO', nome: 'Rondônia' },
    { sigla: 'RR', nome: 'Roraima' }, { sigla: 'RS', nome: 'Rio Grande do Sul' }, { sigla: 'SC', nome: 'Santa Catarina' },
    { sigla: 'SE', nome: 'Sergipe' }, { sigla: 'SP', nome: 'São Paulo' }, { sigla: 'TO', nome: 'Tocantins' },
];

export type CampoEndereco = 'cep' | 'rua' | 'numero' | 'complemento' | 'bairro' | 'cidade_id';

interface CidadeOpt { id: number; cidade: string; sigla: string }

interface Props {
    data: Record<CampoEndereco, string>;
    setCampo: (campo: CampoEndereco, valor: string) => void;
    errors: Partial<Record<string, string>>;
    /** Sigla da UF da cidade já salva (edição). */
    siglaInicial?: string;
}

/**
 * Bloco de endereço com busca de CEP (/api/cep) e seleção de estado → cidade (/api/cidades).
 * A cidade (cidade_id) é o que o dimensionamento usa para buscar a irradiação solar.
 */
export function EnderecoFields({ data, setCampo, errors, siglaInicial = '' }: Props) {
    const [buscandoCep, setBuscandoCep] = useState(false);
    const [estadoSel, setEstadoSel] = useState(siglaInicial);
    const [cidades, setCidades] = useState<CidadeOpt[]>([]);
    const [carregandoCidades, setCarregandoCidades] = useState(false);

    const carregarCidades = useCallback(async (sigla: string) => {
        if (!sigla) { setCidades([]); return; }
        setCarregandoCidades(true);
        try {
            const res = await fetch(route('api.cidades', sigla));
            setCidades(await res.json());
        } catch { setCidades([]); }
        finally { setCarregandoCidades(false); }
    }, []);

    useEffect(() => { carregarCidades(estadoSel); }, [estadoSel, carregarCidades]);

    const buscarCep = async (cep: string) => {
        const raw = cep.replace(/\D/g, '');
        if (raw.length !== 8) return;
        setBuscandoCep(true);
        try {
            const res = await fetch(route('api.cep', raw));
            if (!res.ok) return;
            const json = await res.json();
            setCampo('rua', json.logradouro ?? '');
            setCampo('bairro', json.bairro ?? '');
            if (json.cidade_id) {
                setEstadoSel(json.sigla);
                setCampo('cidade_id', String(json.cidade_id));
            }
        } catch { /* CEP fora do ar: usuário preenche manualmente */ }
        finally { setBuscandoCep(false); }
    };

    return (
        <Grid container spacing={3}>
            <Grid size={{ xs: 12, sm: 2 }}>
                <TextField
                    fullWidth size="small" label="CEP" value={data.cep} placeholder="00000-000" inputProps={{ maxLength: 9 }}
                    onChange={(e) => setCampo('cep', maskCep(e.target.value))}
                    onBlur={(e) => buscarCep(e.target.value)}
                    error={!!errors.cep} helperText={errors.cep}
                    InputProps={{
                        endAdornment: buscandoCep
                            ? <InputAdornment position="end"><CircularProgress size={14} /></InputAdornment>
                            : <Tooltip title="Buscar CEP"><InputAdornment position="end" sx={{ cursor: 'pointer' }} onClick={() => buscarCep(data.cep)}><SearchRoundedIcon fontSize="small" /></InputAdornment></Tooltip>,
                    }}
                />
            </Grid>
            <Grid size={{ xs: 12, sm: 5 }}>
                <TextField fullWidth size="small" label="Rua" value={data.rua} onChange={(e) => setCampo('rua', e.target.value)} />
            </Grid>
            <Grid size={{ xs: 12, sm: 2 }}>
                <TextField fullWidth size="small" label="Número" value={data.numero} onChange={(e) => setCampo('numero', e.target.value)} />
            </Grid>
            <Grid size={{ xs: 12, sm: 3 }}>
                <TextField fullWidth size="small" label="Complemento" value={data.complemento} onChange={(e) => setCampo('complemento', e.target.value)} />
            </Grid>
            <Grid size={{ xs: 12, sm: 4 }}>
                <TextField fullWidth size="small" label="Bairro" value={data.bairro} onChange={(e) => setCampo('bairro', e.target.value)} />
            </Grid>
            <Grid size={{ xs: 12, sm: 3 }}>
                <FormControl fullWidth size="small">
                    <InputLabel>Estado</InputLabel>
                    <Select
                        value={estadoSel}
                        label="Estado"
                        onChange={(e) => { setEstadoSel(e.target.value); setCampo('cidade_id', ''); }}
                    >
                        <MenuItem value="">Selecione...</MenuItem>
                        {ESTADOS.map((e) => <MenuItem key={e.sigla} value={e.sigla}>{e.nome}</MenuItem>)}
                    </Select>
                </FormControl>
            </Grid>
            <Grid size={{ xs: 12, sm: 5 }}>
                <FormControl fullWidth size="small" error={!!errors.cidade_id}>
                    <InputLabel>Cidade</InputLabel>
                    <Select
                        value={cidades.some((c) => String(c.id) === data.cidade_id) ? data.cidade_id : ''}
                        label="Cidade"
                        onChange={(e) => setCampo('cidade_id', String(e.target.value))}
                        disabled={!estadoSel || carregandoCidades}
                        startAdornment={carregandoCidades ? <InputAdornment position="start"><CircularProgress size={14} /></InputAdornment> : undefined}
                    >
                        <MenuItem value="">Selecione...</MenuItem>
                        {cidades.map((c) => <MenuItem key={c.id} value={String(c.id)}>{c.cidade}</MenuItem>)}
                    </Select>
                    <FormHelperText>{errors.cidade_id ?? 'Necessária para o dimensionamento (irradiação solar)'}</FormHelperText>
                </FormControl>
            </Grid>
        </Grid>
    );
}
