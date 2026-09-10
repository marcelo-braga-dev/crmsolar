<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PerfilController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Admin/Perfil/Edit', [
            'user' => Auth::user()->only(['id', 'name', 'email', 'cpf', 'rg', 'cnpj', 'celular']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'cpf'     => 'nullable|string|max:14',
            'rg'      => 'nullable|string|max:20',
            'cnpj'    => 'nullable|string|max:18',
            'celular' => 'nullable|string|max:20',
        ]);

        $user->update($data);

        return back()->with('success', 'Perfil atualizado.');
    }

    public function editSenha(): Response
    {
        return Inertia::render('Admin/Perfil/Senha');
    }

    public function updateSenha(Request $request): RedirectResponse
    {
        $request->validate([
            'senha_atual' => 'required|string',
            'password'    => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->senha_atual, $user->password)) {
            return back()->withErrors(['senha_atual' => 'Senha atual incorreta.']);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Senha alterada com sucesso.');
    }
}
