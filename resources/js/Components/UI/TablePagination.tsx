import React from 'react';
import { Box, Button, Typography } from '@mui/material';
import { router } from '@inertiajs/react';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    from?: number;
    to?: number;
    total: number;
    last_page: number;
    links: PaginationLink[];
    label?: string;
}

export function TablePagination({ from, to, total, last_page, links, label = 'registros' }: Props) {
    return (
        <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', px: 2, py: 1.5, flexWrap: 'wrap', gap: 1 }}>
            <Typography variant="caption" color="text.secondary">
                {total === 0 ? 'Nenhum registro' : `${from}–${to} de ${total} ${label}`}
            </Typography>
            {last_page > 1 && (
                <Box sx={{ display: 'flex', gap: 0.5 }}>
                    {links.map((link, i) => (
                        <Button
                            key={i}
                            size="small"
                            variant={link.active ? 'contained' : 'text'}
                            color={link.active ? 'primary' : 'inherit'}
                            disabled={!link.url}
                            onClick={() => link.url && router.visit(link.url)}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                            sx={{ minWidth: 32, px: 0.75, fontSize: '0.78rem' }}
                        />
                    ))}
                </Box>
            )}
        </Box>
    );
}
