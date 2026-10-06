import { usePage } from '@inertiajs/react';
import { IDENTIDADE_PADRAO } from '@/theme';
import type { Identidade, PageProps } from '@/types';

/** Identidade visual atual (nome, logos, cores) — prop compartilhada por todas as páginas. */
export function useIdentidade(): Identidade {
    return usePage<PageProps>().props.identidade ?? IDENTIDADE_PADRAO;
}
