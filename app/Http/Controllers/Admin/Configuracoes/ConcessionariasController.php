<?php

namespace App\Http\Controllers\Admin\Configuracoes;

use App\Http\Controllers\Controller;
use App\Models\Concessionaria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConcessionariasController extends Controller
{
    public function index(Request $request): Response
    {
        $concessionarias = Concessionaria::query()
            ->when($request->search, fn ($q, $s) => $q->where('nome', 'like', "%{$s}%"))
            ->when($request->estado, fn ($q, $uf) => $q->where('estado', $uf))
            ->orderBy('estado')->orderBy('nome')
            ->paginate(30)
            ->withQueryString();

        $estados = Concessionaria::select('estado')->distinct()->orderBy('estado')->pluck('estado');

        return Inertia::render('Admin/Configuracoes/Concessionarias/Index', [
            'concessionarias' => $concessionarias,
            'estados'         => $estados,
            'filters'         => $request->only(['search', 'estado']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        Concessionaria::create($data);
        return back()->with('success', 'Concessionária criada com sucesso.');
    }

    public function update(Request $request, Concessionaria $concessionaria): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $concessionaria->update($data);
        return back()->with('success', 'Concessionária atualizada com sucesso.');
    }

    public function destroy(Concessionaria $concessionaria): RedirectResponse
    {
        $concessionaria->delete();
        return back()->with('success', 'Concessionária removida.');
    }

    private function rules(): array
    {
        return [
            'nome'                 => 'required|string|max:255',
            'estado'               => 'required|string|size:2',
            'tarifa_convencional'  => 'required|numeric|min:0',
            'tarifa_ponta'         => 'required|numeric|min:0',
            'tarifa_intermediaria' => 'nullable|numeric|min:0',
            'tarifa_fora_ponta'    => 'required|numeric|min:0',
            'ativo'                => 'required|boolean',
        ];
    }
}
