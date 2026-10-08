<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anagrafica trasversale delle persone iscritte ad almeno un corso, deduplicata per codice fiscale e
     * alimentata da ogni iscrizione (Persona::aggiornaDaIscrizione). Solo contatti, niente residenza.
     *
     * - `token`: identificativo casuale non indovinabile per i link personali (pagina personale, conferma dei
     *   consensi, disiscrizione) senza login.
     * - Consensi: due finalità distinte (promemoria sulla scadenza degli attestati, newsletter), ciascuna con lo
     *   stato attuale sulla persona (`*_stato`, `*_stato_at`) per le query rapide; lo storico immutabile, prova
     *   del consenso, è nella tabella `consensi`.
     * - `richiesta_cancellazione_at`: la persona ha chiesto dalla sua pagina la cancellazione dei dati (la esegue
     *   poi un admin). `anonimizzata_at`: data in cui i dati sono stati anonimizzati dal pannello.
     */
    public function up(): void
    {
        Schema::create('persone', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('cognome');
            $table->string('codice_fiscale', 16)->unique();
            $table->string('token', 64)->nullable()->unique();
            $table->string('email')->nullable();
            $table->string('cellulare')->nullable();
            $table->timestamps();
            $table->string('promemoria_stato', 12)->nullable();
            $table->dateTime('promemoria_stato_at')->nullable();
            $table->string('newsletter_stato', 12)->nullable();
            $table->dateTime('newsletter_stato_at')->nullable();
            $table->dateTime('richiesta_cancellazione_at')->nullable();
            $table->dateTime('anonimizzata_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persone');
    }
};
