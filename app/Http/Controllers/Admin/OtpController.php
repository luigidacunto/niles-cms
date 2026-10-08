<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\Admin;
use App\Models\AdminLoginCode;
use App\Models\LoginAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class OtpController extends Controller
{
    public function showRequest()
    {
        return view('admin.login-otp-request');
    }

    public function sendCode(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $admin = Admin::where('email', $request->email)->where('active', true)->first();

        // Stessa risposta che l'email corrisponda o no a un account, così il modulo non si può usare per
        // scoprire gli indirizzi email dello staff.
        if ($admin) {
            $code = AdminLoginCode::issueFor($admin);
            Mail::to($admin->email)->send(new OtpMail($code));
        }

        $request->session()->put('admin_otp_email', $request->email);

        return redirect()->route('admin.login.otp.verify');
    }

    public function showVerify(Request $request)
    {
        if (! $request->session()->has('admin_otp_email')) {
            return redirect()->route('admin.login');
        }

        return view('admin.login-otp-verify', ['email' => $request->session()->get('admin_otp_email')]);
    }

    public function confirmCode(Request $request)
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $email = $request->session()->get('admin_otp_email');
        $admin = $email ? Admin::where('email', $email)->where('active', true)->first() : null;

        if (! $admin || ! AdminLoginCode::verifyFor($admin, $request->code)) {
            LoginAudit::record('admin', 'failed', $admin, $email, $request);

            return back()->withErrors(['code' => 'Codice non valido o scaduto.']);
        }

        $request->session()->forget('admin_otp_email');
        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();
        LoginAudit::record('admin', 'success', $admin, null, $request);

        return redirect()->route('admin.dashboard');
    }
}
