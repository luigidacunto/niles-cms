<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommitteeInfo;
use App\Support\PostaInUscita;
use Illuminate\Http\Request;

/**
 * Posta in uscita (SMTP o log) configurabile dal pannello e prova di invio. Solo admin. I dati stanno su
 * `committee_info.mail_*`; il `.env` ha la precedenza (vedi App\Support\PostaInUscita).
 */
class PostaInUscitaController extends Controller
{
    public function edit()
    {
        $info = CommitteeInfo::current();
        $demo = (bool) config('app.demo');

        if ($demo) { // dati fasulli: la pagina mostra gli stati possibili senza toccare nulla
            $info = new CommitteeInfo([
                'mail_mailer' => 'smtp', 'mail_host' => 'smtp.example.org', 'mail_port' => 465, 'mail_encryption' => 'ssl',
                'mail_username' => 'no-reply@example.org', 'mail_from_address' => 'no-reply@example.org',
                'mail_from_name' => 'Comitato di esempio', 'mail_prova_at' => now()->subDay(), 'mail_prova_esito' => 'ok',
                'mail_prova_parametri' => ['server' => 'smtp.example.org', 'porta' => 465, 'cifratura' => 'ssl', 'utente' => 'no-reply@example.org', 'mittente' => 'no-reply@example.org'],
                'mail_modificata_da' => 'Amministratore di esempio', 'mail_modificata_at' => now()->subDays(2),
            ]);
        }

        return view('admin.posta.edit', [
            'info' => $info,
            'demo' => $demo,
            'daEnv' => ! $demo && PostaInUscita::daEnv(),
            'envPresenti' => PostaInUscita::envPresenti(),
            'envMancanti' => PostaInUscita::envMancanti(),
            'passwordSalvata' => ! $demo && filled($info->getAttributes()['mail_password'] ?? null),
            'passwordIllegibile' => ! $demo && PostaInUscita::passwordIllegibile($info),
            'envHost' => config('mail.mailers.smtp.host'),
            'envUtente' => config('mail.mailers.smtp.username'),
            'envMailer' => config('mail.default'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate($this->regole($request));
        $info = CommitteeInfo::current();

        $valori = [
            'mail_mailer' => $data['mail_mailer'],
            'mail_modificata_da' => $request->user('admin')->name,
            'mail_modificata_at' => now(),
        ];
        if ($data['mail_mailer'] === 'smtp') {
            $valori += [
                'mail_host' => $data['mail_host'], 'mail_port' => $data['mail_port'], 'mail_encryption' => $data['mail_encryption'],
                'mail_username' => $data['mail_username'] ?? null, 'mail_from_address' => $data['mail_from_address'] ?? null,
                'mail_from_name' => $data['mail_from_name'] ?? null,
                // La password non viene mai rimostrata: campo vuoto = tieni quella già salvata (se illeggibile, si perde).
                'mail_password' => filled($data['mail_password'] ?? null) ? $data['mail_password']
                    : (PostaInUscita::passwordIllegibile($info) ? null : $info->mail_password),
            ];
        }
        $info->update($valori);

        return redirect()->route('admin.posta.edit')->with('status', 'Impostazioni della posta salvate.');
    }

    /** Invia la mail di prova SOLO all'amministratore loggato, con i valori del modulo (anche non salvati). */
    public function prova(Request $request)
    {
        $request->merge(['mail_mailer' => 'smtp']);
        $data = $request->validate($this->regole($request));
        $info = CommitteeInfo::current();

        $password = filled($data['mail_password'] ?? null) ? $data['mail_password']
            : (PostaInUscita::passwordIllegibile($info) ? null : $info->mail_password);
        $v = [
            'host' => $data['mail_host'], 'port' => $data['mail_port'], 'encryption' => $data['mail_encryption'],
            'username' => $data['mail_username'] ?? null, 'password' => $password,
            'from_address' => ($data['mail_from_address'] ?? null) ?: ($data['mail_username'] ?? null),
            'from_name' => $data['mail_from_name'] ?? null,
        ];
        if (! filter_var($v['from_address'], FILTER_VALIDATE_EMAIL)) {
            return back()->withInput($request->except('mail_password'))
                ->withErrors(['mail_from_address' => 'Indica un indirizzo mittente valido (o un utente SMTP che sia un indirizzo email).']);
        }

        $destinatario = $request->user('admin')->email;
        $errore = PostaInUscita::invia($v, $destinatario);
        $info->update([
            'mail_prova_at' => now(),
            'mail_prova_esito' => $errore ?? 'ok',
            'mail_prova_parametri' => PostaInUscita::parametri($v),
        ]);

        $indietro = back()->withInput($request->except('mail_password'));

        return $errore
            ? $indietro->with('prova_errore', "La prova non è riuscita: {$errore}")
            : $indietro->with('status', "Email di prova inviata a {$destinatario}: controlla la casella (anche lo spam).");
    }

    private function regole(Request $request): array
    {
        $smtp = $request->input('mail_mailer') === 'smtp';

        return [
            'mail_mailer' => ['required', 'in:smtp,log'],
            'mail_host' => [$smtp ? 'required' : 'nullable', 'string', 'max:255'],
            'mail_port' => [$smtp ? 'required' : 'nullable', 'integer', 'between:1,65535'],
            'mail_encryption' => [$smtp ? 'required' : 'nullable', 'in:'.implode(',', PostaInUscita::CIFRATURE)],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
