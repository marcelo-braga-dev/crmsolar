/**
 * Valores monetários chegam do Laravel como texto quando o model usa cast `decimal:N`
 * ("38113.37"); `String.prototype.toLocaleString` devolve o texto sem formatar.
 * Converta sempre por aqui.
 */
export type Numerico = number | string | null | undefined;

export function paraNumero(v: Numerico): number | null {
    if (v === null || v === undefined || v === '') return null;
    const n = typeof v === 'number' ? v : Number(v);
    return Number.isFinite(n) ? n : null;
}

export function formatarMoeda(v: Numerico, casas = 2, vazio = '—'): string {
    const n = paraNumero(v);
    if (n === null) return vazio;
    return n.toLocaleString('pt-BR', {
        style: 'currency', currency: 'BRL', minimumFractionDigits: casas, maximumFractionDigits: casas,
    });
}
