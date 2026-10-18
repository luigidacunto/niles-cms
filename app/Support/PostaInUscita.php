<?php

namespace App\Support;

use App\Models\CommitteeInfo;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Posta in uscita: impostazioni salvate nel database (`committee_info.mail_*`, password cifrata) oppure nel `.env`.
 *
 * Precedenza: basta UNA variabile MAIL_* nel `.env` (elenco calcolato in config/mail.php, chiave `mail.env_presenti`)
 * e vale solo il `.env`, il pannello è ignorato del tutto. Altrimenti, se nel database c'è una configurazione, viene
 * applicata alla configurazione di Laravel (`applica()`, chiamata alla prima risoluzione del mail manager); senza
 * nessuna delle due resta il predefinito `log`.
 */
class PostaInUscita
{
    public const CIFRATURE = ['ssl', 'tls', 'none'];

    /** Variabili necessarie nel .env per un SMTP completo (MAIL_SCHEME solo per la porta 465). */
    private const ENV_SMTP = ['MAIL_MAILER', 'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_FROM_ADDRESS'];

    /** @return string[] variabili MAIL_* presenti nel .env */
    public static function envPresenti(): array
    {
        return (array) config('mail.env_presenti', []);
    }

    public static function daEnv(): bool
    {
        return self::envPresenti() !== [];
    }

    /** @return string[] variabili che mancano nel .env per il tipo di invio scelto (vuoto = completo o non richiesto) */
    public static function envMancanti(): array
    {
        if (! in_array('MAIL_MAILER', self::envPresenti(), true)) {
            return self::daEnv() ? ['MAIL_MAILER'] : [];
        }
        if (config('mail.default') !== 'smtp') {
            return [];
        }
        $richieste = self::ENV_SMTP;
        if ((int) config('mail.mailers.smtp.port') === 465) {
            $richieste[] = 'MAIL_SCHEME';
        }

        return array_values(array_diff($richieste, self::envPresenti()));
    }

    /** Impostazioni del pannello: null se non c'è nulla di salvato (o tabella/colonne non ancora create). */
    public static function dalDatabase(): ?CommitteeInfo
    {
        try {
            $info = CommitteeInfo::find(1);
        } catch (Throwable) {
            return null; // prima delle migrazioni, durante l'installer, nei test senza schema
        }

        return $info && filled($info->getAttributes()['mail_mailer'] ?? null) ? $info : null;
    }

    /** true se la password salvata non si può decifrare (APP_KEY cambiata). */
    public static function passwordIllegibile(?CommitteeInfo $info): bool
    {
        if (! $info || blank($info->getAttributes()['mail_password'] ?? null)) {
            return false;
        }
        try {
            $info->mail_password;
        } catch (DecryptException) {
            return true;
        }

        return false;
    }

    /** Applica le impostazioni del pannello alla configurazione, se il .env non ha la precedenza. */
    public static function applica(): void
    {
        if (self::daEnv() || ! ($info = self::dalDatabase()) || self::passwordIllegibile($info)) {
            return;
        }

        if ($info->mail_mailer === 'log') {
            config(['mail.default' => 'log']);

            return;
        }

        $valori = self::valori($info);
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp' => array_merge(config('mail.mailers.smtp'), self::mailer($valori)),
            'mail.from.address' => $valori['from_address'],
            'mail.from.name' => $valori['from_name'] ?: config('mail.from.name'),
        ]);
    }

    /** Valori SMTP dal modello (mittente vuoto → utente SMTP). */
    public static function valori(CommitteeInfo $info): array
    {
        return [
            'host' => $info->mail_host,
            'port' => $info->mail_port,
            'encryption' => $info->mail_encryption ?: 'ssl',
            'username' => $info->mail_username,
            'password' => $info->mail_password,
            'from_address' => $info->mail_from_address ?: $info->mail_username,
            'from_name' => $info->mail_from_name,
        ];
    }

    /** Configurazione del mailer `smtp` di Laravel. Cifratura ssl = TLS implicito (schema smtps); tls/none = schema predefinito. */
    public static function mailer(array $v): array
    {
        return [
            'transport' => 'smtp',
            'scheme' => $v['encryption'] === 'ssl' ? 'smtps' : null,
            'host' => $v['host'],
            'port' => (int) $v['port'],
            'username' => $v['username'] ?: null,
            'password' => $v['password'] ?: null,
            'timeout' => 15,
        ];
    }

    /** Parametri senza segreti, da mostrare come «ultima prova». */
    public static function parametri(array $v): array
    {
        return [
            'server' => $v['host'], 'porta' => (int) $v['port'], 'cifratura' => $v['encryption'],
            'utente' => $v['username'], 'mittente' => $v['from_address'],
        ];
    }

    /** Invia una mail di prova con i valori dati (anche non salvati). Ritorna null se riuscita, altrimenti il messaggio d'errore. */
    public static function invia(array $v, string $destinatario): ?string
    {
        try {
            Mail::build(self::mailer($v))->raw(
                "Questa è una email di prova inviata da NILES.\nSe la leggi, la posta in uscita funziona.",
                fn ($m) => $m->to($destinatario)->from($v['from_address'], $v['from_name'] ?: config('app.name'))->subject('Prova posta in uscita')
            );
        } catch (Throwable $e) {
            return trim($e->getMessage()) ?: $e::class;
        }

        return null;
    }
}
