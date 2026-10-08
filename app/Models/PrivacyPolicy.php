<?php

namespace App\Models;

use App\Support\PolicyPlaceholders;
use Illuminate\Database\Eloquent\Model;

/**
 * Informativa privacy per tipologia: un default (seedato, `is_default=true`, sempre presente) + un
 * eventuale override personalizzato (`is_default=false`, creato da pannello) — se esiste, ha la
 * precedenza. Vedi FirstInstallSeeder e config/privacy_policies.php per le tipologie note.
 */
class PrivacyPolicy extends Model
{
    protected $table = 'privacy_policies';

    protected $fillable = ['tipo', 'is_default', 'titolo', 'testo'];

    protected $casts = ['is_default' => 'boolean'];

    public static function perTipo(string $tipo): ?self
    {
        return static::where('tipo', $tipo)->where('is_default', false)->first()
            ?? static::where('tipo', $tipo)->where('is_default', true)->first();
    }

    public static function testoPerTipo(string $tipo): string
    {
        $policy = static::perTipo($tipo);

        return $policy ? $policy->testoRisolto() : '<p>Informativa non disponibile.</p>';
    }

    public function testoRisolto(): string
    {
        return PolicyPlaceholders::sostituisci($this->testo).$this->sezioniAutomatiche();
    }

    /**
     * Sezioni obbligatorie che dipendono dalla configurazione e non dal testo salvato: aggiunte sempre in
     * coda, anche a un override personalizzato (chi lo scrive non può dimenticarle) e a righe già
     * presenti in produzione (nessun rilancio di seeder). Oggi: Turnstile sull'informativa dei corsi.
     */
    private function sezioniAutomatiche(): string
    {
        if ($this->tipo === 'corsi-popolazione' && SicurezzaForm::current()->turnstileAttivo()) {
            return view('privacy-policy.partials.captcha')->render();
        }

        return '';
    }
}
