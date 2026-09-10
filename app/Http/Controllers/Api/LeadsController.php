<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadsController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nome'           => 'required|string|max:255',
            'email'          => 'required|email|max:255',
            'telefone'       => 'required|string|max:20',
            'cidade'         => 'nullable|string|max:100',
            'estado'         => 'nullable|string|size:2',
            'consumo_mensal' => 'nullable|numeric|min:0',
            'origem'         => 'nullable|string|max:50',
            'dados_extras'   => 'nullable|array',
        ]);

        $data['status'] = 'novo';
        $data['origem'] = $data['origem'] ?? 'api';

        $lead = Lead::create($data);

        return response()->json(['message' => 'Lead recebido com sucesso.', 'id' => $lead->id], 201);
    }
}
