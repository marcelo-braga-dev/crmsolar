/** Função do usuário por extenso (users.tipo). */
export const ROTULO_TIPO_USUARIO: Record<string, string> = {
    admin: 'Administrador',
    consultor: 'Consultor',
};

export const rotuloTipoUsuario = (tipo?: string | null) => (tipo ? ROTULO_TIPO_USUARIO[tipo] ?? tipo : '');
