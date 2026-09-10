import React from 'react';
import { Chip, ChipProps } from '@mui/material';
import { OrcamentoStatus } from '@/types';

const orcamentoStatusConfig: Record<OrcamentoStatus, { label: string; color: ChipProps['color']; sx?: object }> = {
    novo: { label: 'Novo', color: 'info' },
    aprovando: { label: 'Para Aprovação', color: 'warning' },
    aprovado: { label: 'Aprovado', color: 'success' },
    aprovacao_reprovada: { label: 'Reprovado', color: 'error' },
    instalando: { label: 'Em Instalação', color: 'secondary' },
    finalizado: { label: 'Finalizado', color: 'default', sx: { backgroundColor: '#E2E8F0', color: '#475569', fontWeight: 600 } },
};

export function OrcamentoStatusChip({ status }: { status: OrcamentoStatus }) {
    const cfg = orcamentoStatusConfig[status] ?? { label: status, color: 'default' as const };
    return (
        <Chip
            label={cfg.label}
            color={cfg.color}
            size="small"
            sx={{
                fontWeight: 600,
                fontSize: '0.72rem',
                height: 24,
                borderRadius: 1.5,
                ...cfg.sx,
            }}
        />
    );
}

const leadStatusConfig = {
    novo: { label: 'Novo', color: 'info' as ChipProps['color'] },
    contatado: { label: 'Contatado', color: 'secondary' as ChipProps['color'] },
    encaminhado: { label: 'Encaminhado', color: 'warning' as ChipProps['color'] },
    convertido: { label: 'Convertido', color: 'success' as ChipProps['color'] },
    perdido: { label: 'Perdido', color: 'error' as ChipProps['color'] },
};

export function LeadStatusChip({ status }: { status: string }) {
    const cfg = leadStatusConfig[status as keyof typeof leadStatusConfig] ?? { label: status, color: 'default' as ChipProps['color'] };
    return (
        <Chip
            label={cfg.label}
            color={cfg.color}
            size="small"
            sx={{ fontWeight: 600, fontSize: '0.72rem', height: 24, borderRadius: 1.5 }}
        />
    );
}

const clienteStatusConfig = {
    novo: { label: 'Novo', color: 'info' as ChipProps['color'] },
    orcamento_gerado: { label: 'Orçamento Gerado', color: 'warning' as ChipProps['color'] },
    visita_agendada: { label: 'Visita Agendada', color: 'secondary' as ChipProps['color'] },
    finalizado: { label: 'Finalizado', color: 'success' as ChipProps['color'] },
};

export function ClienteStatusChip({ status }: { status: string }) {
    const cfg = clienteStatusConfig[status as keyof typeof clienteStatusConfig] ?? { label: status, color: 'default' as ChipProps['color'] };
    return (
        <Chip
            label={cfg.label}
            color={cfg.color}
            size="small"
            sx={{ fontWeight: 600, fontSize: '0.72rem', height: 24, borderRadius: 1.5 }}
        />
    );
}

export function BoolChip({ value }: { value: boolean }) {
    return (
        <Chip
            label={value ? 'Ativo' : 'Inativo'}
            color={value ? 'success' : 'default'}
            size="small"
            sx={{
                fontWeight: 600,
                fontSize: '0.72rem',
                height: 24,
                borderRadius: 1.5,
                ...(!value && { backgroundColor: '#F1F5F9', color: '#94A3B8' }),
            }}
        />
    );
}
