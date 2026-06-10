<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $adminEmail = config('admin.email');
        $adminPassword = config('admin.password');

        if ($adminPassword === '' || $adminPassword === null) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'ADMIN_PASSWORD não configurada no servidor.']);
        }

        if ($credentials['email'] !== $adminEmail || $credentials['password'] !== $adminPassword) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Credenciais inválidas.']);
        }

        $request->session()->regenerate();
        $request->session()->put(config('admin.session_key'), true);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(config('admin.session_key'));
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
