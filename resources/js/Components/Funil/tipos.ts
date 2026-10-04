/** Tipos e utilitários do funil de vendas (docs/funil-de-vendas.md). */

export type TipoEtapa = 'aberta' | 'aprovacao' | 'ganho' | 'perdido';

export interface CardFunil {
    id: number;
    cliente: string;
    cidade: string | null;
    valor: number;
    potencia_kwp: number | null;
    grupo: string | null;
    status: string;
    consultor: { id: number; nome: string } | null;
    dias_na_etapa: number;
    sla_estourado: boolean;
    proximo_contato_em: string | null;
    contato_atrasado: boolean;
    reprovado: boolean;
    tem_contrato: boolean;
    motivo_perda: string | null;
    reativavel: boolean;
    tentativas_reativacao: number;
    esfriando?: boolean;
}

export interface ColunaFunil {
    id: number;
    nome: string;
    cor: string;
    tipo: TipoEtapa;
    probabilidade: number | null;
    sla_dias: number | null;
    quantidade: number;
    valor: number;
    ponderado: number;
    cards: CardFunil[];
}

export interface ResumoFunil {
    abertos: number;
    valor_aberto: number;
    ponderado: number;
    atrasados: number;
    caixa: number;
    dias_caixa_entrada: number;
}

export interface MotivoPerda { id: number; nome: string; reativavel: boolean }

export type Area = 'admin' | 'consultor';

/** Coluna de onde o card saiu, como o servidor espera ("caixa" ou id da etapa). */
export const CAIXA = 'caixa';

export const moeda = (v: number) =>
    v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL', maximumFractionDigits: 0 });

/** Valores grandes compactos para cabeçalhos ("R$ 1,2 mi", "R$ 320 mil"). */
export const moedaCompacta = (v: number) => {
    if (v >= 1_000_000) return `R$ ${(v / 1_000_000).toLocaleString('pt-BR', { maximumFractionDigits: 1 })} mi`;
    if (v >= 1_000) return `R$ ${Math.round(v / 1_000).toLocaleString('pt-BR')} mil`;
    return moeda(v);
};

export const dataContato = (iso: string) => {
    const d = new Date(iso);
    const hoje = new Date();
    const amanha = new Date(); amanha.setDate(hoje.getDate() + 1);
    const mesmoDia = (a: Date, b: Date) => a.toDateString() === b.toDateString();
    const hora = d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    if (mesmoDia(d, hoje)) return `hoje ${hora}`;
    if (mesmoDia(d, amanha)) return `amanhã ${hora}`;
    return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' }) + ` ${hora}`;
};

/** Valor para <input type="datetime-local"> (horário local, sem fuso). */
export const paraInputDataHora = (d: Date) => {
    const p = (n: number) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}T${p(d.getHours())}:${p(d.getMinutes())}`;
};

export const iniciais = (nome: string) =>
    nome.split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase();
