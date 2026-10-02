<?php

namespace App\Http\Controllers\Admin\Usuarios;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminsController extends Controller
{
    public function index(Request $request): Response
    {
        $admins = User::query()
            ->where('tipo', 'admin')
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%");
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Usuarios/Admins/Index', [
            'admins' => $admins,
            'filters' => $request->only(['search']),
            'current_id' => Auth::id(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Usuarios/Admins/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'cpf' => 'nullable|string|max:14',
            'celular' => 'nullable|string|max:20',
            'status' => 'required|boolean',
        ]);

        $data['tipo'] = 'admin';
        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return redirect()->route('admin.usuarios.admins.index')
            ->with('success', 'Administrador criado com sucesso.');
    }

    public function edit(User $admin): Response
    {
        abort_unless($admin->isAdmin(), 404);

        return Inertia::render('Admin/Usuarios/Admins/Form', [
            'admin' => $admin->only(['id', 'name', 'email', 'cpf', 'celular', 'status']),
        ]);
    }

    public function update(Request $request, User $admin): RedirectResponse
    {
        abort_unless($admin->isAdmin(), 404);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'cpf' => 'nullable|string|max:14',
            'celular' => 'nullable|string|max:20',
            'status' => 'required|boolean',
        ]);

        if ($admin->id === Auth::id() && ! $data['status']) {
            return back()->withErrors(['status' => 'Você não pode desativar sua própria conta.']);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $admin->update($data);

        return redirect()->route('admin.usuarios.admins.index')
            ->with('success', 'Administrador atualizado com sucesso.');
    }

    public function destroy(User $admin): RedirectResponse
    {
        abort_unless($admin->isAdmin(), 404);

        if ($admin->id === Auth::id()) {
            return back()->with('error', 'Você não pode excluir sua própria conta.');
        }

        $admin->delete();

        return redirect()->route('admin.usuarios.admins.index')
            ->with('success', 'Administrador removido.');
    }
}
