<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Riga singola (id=1) con gli interruttori di protezione anti-bot dei moduli pubblici: rate limiting
 * (letto da SicurezzaForm::current() dentro i named rate limiter in AppServiceProvider::boot()) e
 * CAPTCHA Cloudflare Turnstile (opzionale, chiavi per installazione). Editabile solo da admin, vedi
 * Admin\SicurezzaFormController.
 */
class SicurezzaForm extends Model
{
    protected $table = 'sicurezza_form';

    /** Chiavi delle soglie di rate limiting: colonna `limite_{chiave}`, predefinito in config/sicurezza.php. */
    public const LIMITI = ['invii_minuto', 'invii_ora', 'visite_minuto', 'visite_ora'];

    protected $fillable = [
        'rate_limiting_attivo', 'captcha_attivo', 'turnstile_site_key', 'turnstile_secret_key',
        'limite_invii_minuto', 'limite_invii_ora', 'limite_visite_minuto', 'limite_visite_ora',
    ];

    protected $casts = [
        'rate_limiting_attivo' => 'boolean',
        'captcha_attivo' => 'boolean',
        // ⚠️ Cifrata con APP_KEY: ambienti che condividono lo stesso DB devono condividere anche APP_KEY,
        // altrimenti la secret risulta illeggibile (DecryptException) da uno dei due.
        'turnstile_secret_key' => 'encrypted',
    ];

    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], ['rate_limiting_attivo' => true, 'captcha_attivo' => false]);
    }

    /** Soglia effettiva di una chiave di LIMITI: il valore impostato dal pannello, altrimenti il predefinito. */
    public function limite(string $chiave): int
    {
        return (int) ($this->{"limite_{$chiave}"} ?: config("sicurezza.limiti.{$chiave}"));
    }

    /** Il CAPTCHA entra in gioco solo se acceso E con entrambe le chiavi: acceso a metà = come spento. */
    public function turnstileAttivo(): bool
    {
        return $this->captcha_attivo && filled($this->turnstile_site_key) && filled($this->turnstile_secret_key);
    }
}
