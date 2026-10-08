<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // password_login_enabled entra nella ricerca stessa (non è verificato dopo), così un account con il
        // flag spento fallisce come con una password sbagliata: nessuna fuga di informazioni su quale
        // metodo di accesso gli è consentito.
        $credentials['password_login_enabled'] = true;
        $credentials['active'] = true;

        if (! Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            LoginAudit::record('admin', 'failed', null, $credentials['email'], $request);

            return back()->withErrors(['email' => 'Credenziali non valide.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        LoginAudit::record('admin', 'success', Auth::guard('admin')->user(), null, $request);

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
