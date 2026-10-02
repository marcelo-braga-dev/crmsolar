<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrcamentoPublicoResource;
use App\Models\Orcamento;

class OrcamentosController extends Controller
{
    public function show(string $token): OrcamentoPublicoResource
    {
        $orcamento = Orcamento::where('token', $token)
            ->with([
                'cliente:id,tipo_pessoa,nome,razao_social',
                'cidade:id,cidade,estado',
                'consultor:id,name,email,celular',
                'itens',
                'info.estrutura:id,nome',
            ])
            ->firstOrFail();

        return new OrcamentoPublicoResource($orcamento);
    }
}
