<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riga singola con gli interruttori di protezione anti-bot dei moduli pubblici (oggi l'iscrizione ai corsi).
     *
     * - Chiavi Cloudflare Turnstile, per installazione: la chiave segreta è cifrata dal cast del modello, quindi
     *   `text` (il testo cifrato è molto più lungo della chiave).
     * - Soglie del rate limiting personalizzabili dal pannello; null = valore predefinito di config/sicurezza.php.
     */
    public function up(): void
    {
        Schema::create('sicurezza_form', function (Blueprint $table) {
            $table->id();
            $table->boolean('rate_limiting_attivo')->default(true);
            $table->boolean('captcha_attivo')->default(false);
            $table->string('turnstile_site_key')->nullable();
            $table->text('turnstile_secret_key')->nullable();
            $table->timestamps();
            $table->unsignedSmallInteger('limite_invii_minuto')->nullable();
            $table->unsignedSmallInteger('limite_invii_ora')->nullable();
            $table->unsignedSmallInteger('limite_visite_minuto')->nullable();
            $table->unsignedSmallInteger('limite_visite_ora')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sicurezza_form');
    }
};
