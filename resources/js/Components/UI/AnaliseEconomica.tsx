import React from 'react';
import {
    Box, Card, CardContent, CardHeader, Chip, Divider,
    LinearProgress, Table, TableBody, TableCell, TableHead, TableRow, Tooltip, Typography, alpha,
} from '@mui/material';
import TrendingUpRoundedIcon from '@mui/icons-material/TrendingUpRounded';
import AccountBalanceRoundedIcon from '@mui/icons-material/AccountBalanceRounded';
import AccessTimeRoundedIcon from '@mui/icons-material/AccessTimeRounded';
import WarningAmberRoundedIcon from '@mui/icons-material/WarningAmberRounded';
import CheckCircleRoundedIcon from '@mui/icons-material/CheckCircleRounded';
import { formatarMoeda, type Numerico } from '@/utils/formatar';

export interface EconomiaKit {
    // Kit base
    id: number; nome: string; categoria?: string; potencia_kwp: number;
    geracao: number; preco_venda: number; preco_custo: number;
    fornecedor?: string; margem_total: number;
    // Economia
    economia?: {
        economia_mensal: number; economia_anual: number; saldo_kwh?: number;
        custo_residual?: number; disponibilidade_kwh?: number;
        consumo_atendido_kwh?: number; percentual_atendido?: number;
        geracao_autoconsumo?: number; geracao_injetada?: number;
        economia_ponta_mensal?: number; economia_fp_mensal?: number;
    };
    economia_total_mes?: number;
    economia_demanda_mes?: number;
    reducao_demanda?: { reducao_kw: number; nova_demanda_kw: number; percentual_reducao: number };
    payback_simples?: number; payback_descontado?: number;
    vpl_25a?: number; tir_25a?: number; economia_total_25a?: number; roi_percentual?: number;
}

const fmtMoney = (v: Numerico) => formatarMoeda(v);
const fmtNum   = (v: number, dec = 0) => v.toLocaleString('pt-BR', { minimumFractionDigits: dec, maximumFractionDigits: dec });

interface Props {
    kit: EconomiaKit;
    corGrupo?: string;
}

export function AnaliseEconomica({ kit, corGrupo = '#2563EB' }: Props) {
    const eco = kit.economia;
    const economiaMensal  = kit.economia_total_mes ?? eco?.economia_mensal ?? 0;
    const economiaAnual   = economiaMensal * 12;
    const payback         = kit.payback_simples;
    const paybackDesc     = kit.payback_descontado;
    const vpl             = kit.vpl_25a ?? 0;
    const tir             = kit.tir_25a ?? 0;
    const economiaTotal   = kit.economia_total_25a ?? 0;
    const roi             = kit.roi_percentual ?? 0;
    const viavel          = vpl > 0 && payback != null && payback <= 25;

    return (
        <Box>
            {/* Alerta de viabilidade */}
            {vpl > 0 ? (
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 2, p: 1.5, borderRadius: 2, bgcolor: alpha('#10B981', 0.08), border: '1px solid', borderColor: '#10B981' }}>
                    <CheckCircleRoundedIcon sx={{ color: '#10B981', fontSize: 20 }} />
                    <Typography variant="body2" fontWeight={600} sx={{ color: '#10B981' }}>
                        Projeto viável — VPL positivo em {paybackDesc ?? payback} anos
                    </Typography>
                </Box>
            ) : (
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 2, p: 1.5, borderRadius: 2, bgcolor: alpha('#F59E0B', 0.08), border: '1px solid', borderColor: '#F59E0B' }}>
                    <WarningAmberRoundedIcon sx={{ color: '#F59E0B', fontSize: 20 }} />
                    <Typography variant="body2" fontWeight={600} sx={{ color: '#F59E0B' }}>
                        Verificar: VPL negativo — retorno acima de 25 anos
                    </Typography>
                </Box>
            )}

            {/* KPIs principais — colunas pela largura do card (fica estreito ao lado da lista de kits), não da tela */}
            <Box sx={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(150px, 1fr))', gap: 1.5, mb: 2 }}>
                {[
                    { label: 'Economia / Mês', value: fmtMoney(economiaMensal), icon: <TrendingUpRoundedIcon />, color: '#10B981', sub: `${fmtMoney(economiaAnual)}/ano` },
                    { label: 'Payback Simples', value: payback ? `${payback} anos` : '> 25 anos', icon: <AccessTimeRoundedIcon />, color: payback && payback <= 10 ? '#10B981' : payback && payback <= 15 ? '#F59E0B' : '#EF4444', sub: paybackDesc ? `Descontado: ${paybackDesc} anos` : '—' },
                    { label: 'VPL 25 anos', value: formatarMoeda(vpl, 0), icon: <AccountBalanceRoundedIcon />, color: vpl > 0 ? '#10B981' : '#EF4444', sub: `TIR: ${tir}% a.a.` },
                    { label: 'Retorno total', value: formatarMoeda(economiaTotal, 0), icon: <TrendingUpRoundedIcon />, color: corGrupo, sub: `ROI: ${roi}%` },
                ].map(({ label, value, icon, color, sub }) => (
                    <Box key={label} sx={{ minWidth: 0 }}>
                        <Box sx={{ p: 1.5, height: '100%', borderRadius: 2, border: '1px solid', borderColor: 'divider', bgcolor: 'background.paper' }}>
                            <Box sx={{ color, mb: 0.5 }}>{React.cloneElement(icon as React.ReactElement, { sx: { fontSize: 18 } })}</Box>
                            <Typography variant="caption" color="text.secondary" display="block">{label}</Typography>
                            <Typography variant="subtitle2" fontWeight={700} sx={{ color, whiteSpace: 'nowrap' }}>{value}</Typography>
                            <Typography variant="caption" color="text.disabled" display="block">{sub}</Typography>
                        </Box>
                    </Box>
                ))}
            </Box>

            {/* Detalhamento da economia */}
            {eco && (
                <Box sx={{ mb: 2 }}>
                    <Typography variant="caption" color="text.secondary" display="block" mb={0.5}>Composição da economia mensal</Typography>
                    <Box sx={{ display: 'flex', flexDirection: 'column', gap: 0.5 }}>
                        {eco.economia_ponta_mensal != null && (
                            <Box sx={{ display: 'flex', justifyContent: 'space-between' }}>
                                <Typography variant="caption" color="text.secondary">Redução consumo ponta</Typography>
                                <Typography variant="caption" fontWeight={600} color="error.main">{fmtMoney(eco.economia_ponta_mensal)}</Typography>
                            </Box>
                        )}
                        {eco.economia_fp_mensal != null && (
                            <Box sx={{ display: 'flex', justifyContent: 'space-between' }}>
                                <Typography variant="caption" color="text.secondary">Redução consumo fora-ponta</Typography>
                                <Typography variant="caption" fontWeight={600}>{fmtMoney(eco.economia_fp_mensal)}</Typography>
                            </Box>
                        )}
                        {kit.economia_demanda_mes != null && kit.economia_demanda_mes > 0 && (
                            <Box sx={{ display: 'flex', justifyContent: 'space-between' }}>
                                <Typography variant="caption" color="text.secondary">Redução de demanda (est.)</Typography>
                                <Typography variant="caption" fontWeight={600} color="secondary.main">{fmtMoney(kit.economia_demanda_mes)}</Typography>
                            </Box>
                        )}
                        {eco.custo_residual != null && (
                            <Box sx={{ display: 'flex', justifyContent: 'space-between' }}>
                                <Typography variant="caption" color="text.disabled">Custo de disponibilidade (residual)</Typography>
                                <Typography variant="caption" color="text.disabled">−{fmtMoney(eco.custo_residual)}</Typography>
                            </Box>
                        )}
                        <Divider sx={{ my: 0.5 }} />
                        <Box sx={{ display: 'flex', justifyContent: 'space-between' }}>
                            <Typography variant="caption" fontWeight={700}>Total economia/mês</Typography>
                            <Typography variant="caption" fontWeight={700} color="success.main">{fmtMoney(economiaMensal)}</Typography>
                        </Box>
                    </Box>

                    {eco.percentual_atendido != null && (
                        <Box sx={{ mt: 1.5 }}>
                            <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 0.3 }}>
                                <Typography variant="caption" color="text.secondary">Consumo atendido pelo sistema</Typography>
                                <Typography variant="caption" fontWeight={700} color={corGrupo}>{eco.percentual_atendido}%</Typography>
                            </Box>
                            <LinearProgress
                                variant="determinate" value={Math.min(eco.percentual_atendido, 100)}
                                sx={{ height: 6, borderRadius: 3, bgcolor: alpha(corGrupo, 0.15), '& .MuiLinearProgress-bar': { bgcolor: corGrupo, borderRadius: 3 } }}
                            />
                        </Box>
                    )}
                </Box>
            )}

            {/* Redução de demanda (Grupo A) */}
            {kit.reducao_demanda && kit.reducao_demanda.reducao_kw > 0 && (
                <Box sx={{ p: 1.5, borderRadius: 2, bgcolor: alpha('#7C3AED', 0.06), border: '1px solid', borderColor: '#7C3AED', mb: 2 }}>
                    <Typography variant="caption" fontWeight={700} color="#7C3AED" display="block" mb={0.5}>Redução de demanda (conservadora)</Typography>
                    <Box sx={{ display: 'flex', gap: 3 }}>
                        <Box>
                            <Typography variant="caption" color="text.secondary">Redução estimada</Typography>
                            <Typography variant="body2" fontWeight={700}>{fmtNum(kit.reducao_demanda.reducao_kw, 1)} kW ({kit.reducao_demanda.percentual_reducao}%)</Typography>
                        </Box>
                        <Box>
                            <Typography variant="caption" color="text.secondary">Nova demanda</Typography>
                            <Typography variant="body2" fontWeight={700}>{fmtNum(kit.reducao_demanda.nova_demanda_kw, 1)} kW</Typography>
                        </Box>
                    </Box>
                </Box>
            )}

            {/* Premissas */}
            <Box sx={{ p: 1, borderRadius: 1, bgcolor: 'grey.50' }}>
                <Typography variant="caption" color="text.disabled" display="block">
                    Premissas do cálculo: crescimento tarifário 5,5% a.a. · degradação painéis 0,5% a.a. · taxa desconto 8% a.a. · vida útil 25 anos · Lei 14.300/2022
                </Typography>
            </Box>
        </Box>
    );
}
