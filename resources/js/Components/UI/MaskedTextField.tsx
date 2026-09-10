import React from 'react';
import { TextField, TextFieldProps } from '@mui/material';
import { maskCpf, maskCnpj, maskCep, maskPhone } from '@/utils/masks';

type MaskType = 'cpf' | 'cnpj' | 'cep' | 'phone' | 'celular';

const applyMask: Record<MaskType, (v: string) => string> = {
    cpf: maskCpf,
    cnpj: maskCnpj,
    cep: maskCep,
    phone: maskPhone,
    celular: maskPhone,
};

interface MaskedTextFieldProps extends Omit<TextFieldProps, 'onChange'> {
    mask: MaskType;
    value: string;
    onChange: (maskedValue: string, rawValue: string) => void;
}

export function MaskedTextField({ mask, value, onChange, ...rest }: MaskedTextFieldProps) {
    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const masked = applyMask[mask](e.target.value);
        const raw = masked.replace(/\D/g, '');
        onChange(masked, raw);
    };

    return (
        <TextField
            {...rest}
            value={value}
            onChange={handleChange}
            inputProps={{ ...rest.inputProps, inputMode: 'numeric' }}
        />
    );
}
