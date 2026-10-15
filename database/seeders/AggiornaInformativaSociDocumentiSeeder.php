<?php

namespace Database\Seeders;

use App\Models\PrivacyPolicy;
use Illuminate\Database\Seeder;

/**
 * Aggiornamento per i siti già in uso: porta il testo di default dell'informativa `soci` alla versione che cita il
 * registro dei documenti riservati (anteprime/download, 12 mesi). Lancio: `php artisan db:seed
 * --class=AggiornaInformativaSociDocumentiSeeder --force`. Idempotente. Tocca solo la riga di default, mai un testo
 * personalizzato (override): se esiste, avvisa che va aggiornato a mano dal pannello.
 * Il testo è lo stesso del seeder di prima installazione (database/seeders/contenuti/informativa-soci.html).
 */
class AggiornaInformativaSociDocumentiSeeder extends Seeder
{
    public function run(): void
    {
        $default = PrivacyPolicy::where('tipo', 'soci')->where('is_default', true)->first();

        if ($default) {
            $default->update(['testo' => trim(file_get_contents(__DIR__.'/contenuti/informativa-soci.html'))]);
        }

        if (PrivacyPolicy::where('tipo', 'soci')->where('is_default', false)->exists()) {
            $this->command?->warn('Esiste un\'informativa «soci» personalizzata: non è stata toccata. Aggiungere a mano la sezione sul registro dei documenti riservati.');
        }
    }
}
