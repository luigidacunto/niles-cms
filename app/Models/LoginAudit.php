<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Registro accessi (chi, quando, da quale IP, riuscito/fallito) per admin e soci. Solo audit di sicurezza:
 * nessun altro uso. Conservazione 12 mesi: ogni scrittura cancella le righe più vecchie (nessuno scheduler
 * da configurare sui server).
 */
class LoginAudit extends Model
{
    public const UPDATED_AT = null;

    public const RETENTION_MONTHS = 12;

    protected $table = 'login_audit';

    protected $fillable = ['guard', 'event', 'subject_id', 'label', 'ip'];

    /** @param Admin|Member|null $subject l'utente, se riconosciuto; altrimenti $label = email digitata */
    public static function record(string $guard, string $event, Admin|Member|null $subject, ?string $label, Request $request): void
    {
        static::where('created_at', '<', now()->subMonths(self::RETENTION_MONTHS))->delete();

        static::create([
            'guard' => $guard,
            'event' => $event,
            'subject_id' => $subject?->getKey(),
            'label' => $subject ? ($subject instanceof Member ? $subject->nomeCompleto() : $subject->name) : $label,
            'ip' => $request->ip(),
        ]);
    }
}
