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

    /** Testi predefiniti delle email di sistema, come li crea il seeder di prima installazione. */
    protected function creaTemplateEmail(): void
    {
        foreach (config('email_templates') as $tipo => $definizione) {
            EmailTemplate::create(['tipo' => $tipo, 'is_default' => true] + $definizione['predefinito']);
        }
    }
}
