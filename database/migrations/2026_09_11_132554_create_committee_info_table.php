<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dati del comitato: riga singola, modificata da «Dati del comitato» nel pannello.
     *
     * - Loghi e favicon: percorsi dei file caricati dal pannello, tutti facoltativi. Senza file il sito usa
     *   la grafica generica distribuita con il codice; loghi istituzionali e favicon non sono inclusi nel codice.
     * - `goatcounter_enabled` / `ga_enabled`: interruttori rapidi delle statistiche, con effetto solo se lo
     *   strumento è configurato nel `.env`.
     * - `metodi_pagamento`: codici dei metodi di pagamento accettati dai corsi (vedi config/pagamenti.php).
     * - `area_soci_attiva`: interruttore generale dell'area soci. Spento = tutto nascosto (pannello e sito),
     *   nulla viene cancellato.
     * - `iban`, `intestatario_conto`, `banca`: coordinate per i bonifici dei corsi (email di iscrizione e pagina
     *   personale).
     */
    public function up(): void
    {
        Schema::create('committee_info', function (Blueprint $table) {
            $table->id();
            $table->string('denominazione')->nullable();
            $table->string('piva')->nullable();
            $table->string('codice_fiscale')->nullable();
            $table->string('codice_fatturazione_elettronica')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->string('pec')->nullable();
            $table->string('indirizzo')->nullable();
            $table->timestamps();
            $table->string('logo_orizzontale')->nullable();
            $table->string('logo_verticale')->nullable();
            $table->boolean('goatcounter_enabled')->default(true);
            $table->boolean('ga_enabled')->default(true);
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('x_url')->nullable();
            $table->json('metodi_pagamento')->nullable();
            $table->boolean('area_soci_attiva')->default(false);
            $table->string('iban', 34)->nullable();
            $table->string('intestatario_conto')->nullable();
            $table->string('banca')->nullable();
            $table->string('logo_ifrc')->nullable();
            $table->string('logo_un_italia')->nullable();
            $table->string('favicon')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('committee_info');
    }
};
