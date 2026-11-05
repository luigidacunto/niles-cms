<?php

use App\Models\PrivacyPolicy;
use Illuminate\Database\Seeder;

// Informativa privacy dei corsi: tra i dati trattati compaiono data e luogo di nascita (tipologie con anagrafica completa).
// Aggiorna solo il testo PREDEFINITO di sistema (riga `is_default`), con una sostituzione puntuale: se la frase non c'è
// (testo già aggiornato o modificato) non fa nulla. Le versioni personalizzate dal comitato (altre righe) non si toccano.
// Sulle nuove installazioni il testo nuovo lo crea già FirstInstallSeeder.
return new class extends Seeder
{
    private const VECCHIO = 'fiscale, residenza, email, telefono).</li>';

    private const NUOVO = 'fiscale, residenza, email, telefono) e, per i corsi che rilasciano un attestato che lo richiede, '
        .'<strong>data e luogo di nascita</strong>, necessari per il rilascio dell\'attestato.</li>';

    public function run(): void
    {
        $default = PrivacyPolicy::where('tipo', 'corsi-popolazione')->where('is_default', true)->first();

        if ($default && str_contains($default->testo, self::VECCHIO) && ! str_contains($default->testo, 'data e luogo di nascita')) {
            $default->update(['testo' => str_replace(self::VECCHIO, self::NUOVO, $default->testo)]);
        }
    }
};
