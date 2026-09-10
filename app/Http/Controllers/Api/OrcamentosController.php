<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Orcamento;
use Illuminate\Http\JsonResponse;

class OrcamentosController extends Controller
{
    public function show(string $token): JsonResponse
    {
        $orcamento = Orcamento::where('token', $token)
            ->with(['cliente', 'consultor', 'itens', 'info'])
            ->firstOrFail();

        return response()->json($orcamento);
    }
}
