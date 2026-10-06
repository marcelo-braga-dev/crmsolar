export type UserRole = 'admin' | 'consultor';

export interface User {
    id: number;
    name: string;
    email: string;
    tipo: UserRole;
    status: boolean;
    celular?: string;
    cpf?: string;
    cnpj?: string;
    comissao_percentual?: number;
    email_verified_at?: string;
}

/** Identidade visual (Admin → Configurações → Identidade visual), compartilhada com todas as páginas. */
export interface Identidade {
    nome: string;
    rodape: string;
    cor_primaria: string;
    cor_secundaria: string;
    menu_fundo: string;
    menu_fonte: string;
    logo_url: string | null;
    logo_clara_url: string | null;
    favicon_url: string | null;
}

export interface Flash {
    success?: string;
    error?: string;
    warning?: string;
    info?: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    flash?: Flash;
    identidade?: Identidade;
    ziggy?: {
        url: string;
        port: number | null;
        defaults: Record<string, string | number>;
        routes: Record<string, unknown>;
        location: string;
    };
};

// Common model shapes (expand as modules are built)
export interface Orcamento {
    id: number;
    status: OrcamentoStatus;
    preco_cliente: number;
    geracao: number;
    created_at: string;
    updated_at: string;
    cliente?: Cliente;
    consultor?: User;
}

export type OrcamentoStatus =
    | 'novo'
    | 'aprovando'
    | 'aprovado'
    | 'aprovacao_reprovada'
    | 'instalando'
    | 'finalizado';

export interface Cliente {
    id: number;
    nome?: string;
    razao_social?: string;
    status: string;
    created_at: string;
}

export interface Lead {
    id: number;
    nome?: string;
    telefone?: string;
    email?: string;
    status: 'novo' | 'encaminhado' | 'finalizado';
    origem?: string;
    created_at: string;
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}
