<?php

namespace App\Http\Controllers\Admin\Usuarios;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ConsultoresController extends Controller
{
    public function index(Request $request): Response
    {
        $consultores = User::query()
            ->whereIn('tipo', ['consultor', 'admin_consultor'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('cpf', 'like', "%{$s}%");
            }))
            ->when($request->has('status') && $request->status !== '', fn ($q) => $q->where('status', (bool) $request->status))
            ->withCount(['clientes', 'orcamentos'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Usuarios/Consultores/Index', [
            'consultores' => $consultores,
            'filters'    => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Usuarios/Consultores/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'email'               => 'required|email|max:255|unique:users,email',
            'password'            => 'required|string|min:8|confirmed',
            'tipo'                => 'required|in:consultor,admin_consultor',
            'cpf'                 => 'nullable|string|max:14',
            'rg'                  => 'nullable|string|max:20',
            'celular'             => 'nullable|string|max:20',
            'comissao_percentual' => 'nullable|numeric|min:0|max:100',
            'status'              => 'required|boolean',
        ]);

        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return redirect()->route('admin.usuarios.consultores.index')
            ->with('success', 'Consultor criado com sucesso.');
    }

    public function edit(User $consultor): Response
    {
        return Inertia::render('Admin/Usuarios/Consultores/Form', [
            'consultor' => $consultor->only(['id', 'name', 'email', 'tipo', 'cpf', 'rg', 'celular', 'comissao_percentual', 'status']),
        ]);
    }

    public function update(Request $request, User $consultor): RedirectResponse
    {
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'email'               => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($consultor->id)],
            'password'            => 'nullable|string|min:8|confirmed',
            'tipo'                => 'required|in:consultor,admin_consultor',
            'cpf'                 => 'nullable|string|max:14',
            'rg'                  => 'nullable|string|max:20',
            'celular'             => 'nullable|string|max:20',
            'comissao_percentual' => 'nullable|numeric|min:0|max:100',
            'status'              => 'required|boolean',
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $consultor->update($data);

        return redirect()->route('admin.usuarios.consultores.index')
            ->with('success', 'Consultor atualizado com sucesso.');
    }

    public function destroy(User $consultor): RedirectResponse
    {
        $consultor->delete();

        return redirect()->route('admin.usuarios.consultores.index')
            ->with('success', 'Consultor removido.');
    }
}
