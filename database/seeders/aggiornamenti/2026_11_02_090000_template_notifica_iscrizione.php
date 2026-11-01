<?php

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

// Testo predefinito dell'avviso alla segreteria per le nuove iscrizioni (config/email_templates.php → notifica-iscrizione).
// Solo creazione: non tocca un eventuale testo personalizzato. Sulle nuove installazioni lo crea già FirstInstallSeeder.
return new class extends Seeder
{
    public function run(): void
    {
        EmailTemplate::firstOrCreate(
            ['tipo' => 'notifica-iscrizione', 'is_default' => true],
            EmailTemplate::testoPredefinito('notifica-iscrizione')
        );
    }
};
