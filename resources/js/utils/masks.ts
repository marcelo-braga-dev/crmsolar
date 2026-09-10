export function maskCpf(v: string): string {
    return v.replace(/\D/g, '').slice(0, 11)
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
}

export function maskCnpj(v: string): string {
    return v.replace(/\D/g, '').slice(0, 14)
        .replace(/(\d{2})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1/$2')
        .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
}

export function maskCep(v: string): string {
    return v.replace(/\D/g, '').slice(0, 8)
        .replace(/(\d{5})(\d{1,3})$/, '$1-$2');
}

export function maskPhone(v: string): string {
    const d = v.replace(/\D/g, '').slice(0, 11);
    if (d.length <= 10) {
        return d
            .replace(/(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{4})(\d{1,4})$/, '$1-$2');
    }
    return d
        .replace(/(\d{2})(\d)/, '($1) $2')
        .replace(/(\d{5})(\d{1,4})$/, '$1-$2');
}

export function maskCurrency(v: string): string {
    const num = v.replace(/\D/g, '');
    if (!num) return '';
    const cents = parseInt(num, 10);
    return (cents / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

export function unmask(v: string): string {
    return v.replace(/\D/g, '');
}
