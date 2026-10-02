<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CidadeEstado;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeografiaController extends Controller
{
    public function estados(): JsonResponse
    {
        // Return distinct siglas with full state name
        $estados = CidadeEstado::select('sigla', 'estado')
            ->distinct()
            ->orderBy('estado')
            ->get(['sigla', 'estado']);

        return response()->json($estados);
    }

    public function cidades(string $estado): JsonResponse
    {
        // $estado can be sigla (2 chars) or full name
        $cidades = CidadeEstado::where('sigla', strtoupper($estado))
            ->orWhere('estado', $estado)
            ->orderBy('cidade')
            ->get(['id', 'cidade', 'estado', 'sigla']);

        return response()->json($cidades);
    }

    public function buscarCep(string $cep): JsonResponse
    {
        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) !== 8) {
            return response()->json(['error' => 'CEP inválido.'], 422);
        }

        $dados = Cache::remember("cep_{$cep}", 86400, function () use ($cep) {
            try {
                $res = Http::timeout(5)->get("https://viacep.com.br/ws/{$cep}/json/");
                if ($res->ok() && ! isset($res->json()['erro'])) {
                    return $res->json();
                }
            } catch (ConnectionException) {
                // fallback to null
            }

            return null;
        });

        if (! $dados) {
            return response()->json(['error' => 'CEP não encontrado.'], 404);
        }

        $cidade = CidadeEstado::where('sigla', strtoupper($dados['uf'] ?? ''))
            ->whereRaw('LOWER(cidade) LIKE ?', ['%'.strtolower(str_replace(["'", '-'], ['', ''], $dados['localidade'])).'%'])
            ->first(['id', 'cidade', 'estado', 'sigla']);

        return response()->json([
            'cep' => $cep,
            'logradouro' => $dados['logradouro'] ?? '',
            'bairro' => $dados['bairro'] ?? '',
            'cidade' => $dados['localidade'] ?? '',
            'sigla' => strtoupper($dados['uf'] ?? ''),
            'estado' => $cidade?->estado ?? '',
            'cidade_id' => $cidade?->id,
            'cidade_obj' => $cidade,
        ]);
    }

    public function buscarCidades(string $estado, string $q = ''): JsonResponse
    {
        $cidades = CidadeEstado::where('sigla', strtoupper($estado))
            ->where('cidade', 'like', "%{$q}%")
            ->orderBy('cidade')
            ->limit(50)
            ->get(['id', 'cidade', 'estado', 'sigla']);

        return response()->json($cidades);
    }
}
