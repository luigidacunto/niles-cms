<?php

namespace App\Http\Controllers\Soci;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\LoginAudit;
use App\Models\Member;
use App\Models\MemberLoginCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

/**
 * Login area soci con codice OTP via email (stesso meccanismo di Admin\OtpController, guard `member`).
 * Possono accedere solo membri con email, non disabilitati e non rimossi (il soft-delete li esclude da solo).
 * Dopo il login si torna alla home pubblica: la landing per i soci verrà definita in seguito.
 */
class OtpController extends Controller
{
    public function showRequest()
    {
        return view('soci.login-request');
    }

    public function sendCode(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $member = $this->eligibleMember($request->email);

        // Stessa risposta con o senza account corrispondente: il form non deve permettere di
        // scoprire quali email sono registrate.
        if ($member) {
            Mail::to($member->email)->send(new OtpMail(MemberLoginCode::issueFor($member)));
        }

        $request->session()->put('member_otp_email', $request->email);

        return redirect()->route('soci.login.verify');
    }

    public function showVerify(Request $request)
    {
        if (! $request->session()->has('member_otp_email')) {
            return redirect()->route('soci.login');
        }

        return view('soci.login-verify', ['email' => $request->session()->get('member_otp_email')]);
    }

    public function confirmCode(Request $request)
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $email = $request->session()->get('member_otp_email');
        $member = $email ? $this->eligibleMember($email) : null;

        if (! $member || ! MemberLoginCode::verifyFor($member, $request->code)) {
            LoginAudit::record('member', 'failed', $member, $email, $request);

            return back()->withErrors(['code' => 'Codice non valido o scaduto.']);
        }

        $request->session()->forget('member_otp_email');
        Auth::guard('member')->login($member);
        $request->session()->regenerate();
        LoginAudit::record('member', 'success', $member, null, $request);

        return redirect()->route('home');
    }

    public function logout(Request $request)
    {
        Auth::guard('member')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function eligibleMember(string $email): ?Member
    {
        return Member::where('email', strtolower(trim($email)))->where('disabilitato', false)->first();
    }
}
