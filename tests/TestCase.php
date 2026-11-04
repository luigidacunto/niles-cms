<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Nessun test deve raggiungere servizi esterni (Cloudflare, GitHub...): una richiesta HTTP non simulata con
        // Http::fake() fa fallire il test invece di partire davvero verso la rete.
        Http::preventStrayRequests();
    }

    /**
     * Nome e cognome coerenti con un codice fiscale di prova (le 3+3 lettere iniziali come parole: «RSS» → «Rss»), per i test che
     * inviano il modulo pubblico, dove il codice fiscale deve tornare con i dati anagrafici (vedi CodiceFiscaleCoerente).
     *
     * @return array{0: string, 1: string} [nome, cognome]
     */
    protected static function nomiDaCf(string $cf): array
    {
        return [ucfirst(strtolower(substr($cf, 3, 3))), ucfirst(strtolower(substr($cf, 0, 3)))];
    }

    /** Testi predefiniti delle email di sistema, come li crea il seeder di prima installazione. */
    protected function creaTemplateEmail(): void
    {
        foreach (config('email_templates') as $tipo => $definizione) {
            EmailTemplate::create(['tipo' => $tipo, 'is_default' => true] + $definizione['predefinito']);
        }
    }
}
