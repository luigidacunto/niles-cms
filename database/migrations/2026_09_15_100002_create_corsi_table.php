<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Istanza concreta di un corso aperto alla popolazione, creata da un editor con il permesso `corsi`
     * scegliendo una tipologia dal catalogo. Niente titolo: il nome è quello della tipologia e il `protocollo`
     * (SIGLA-ANNO-NNN, generato alla creazione, vedi Corso::generaProtocollo) lo identifica, anche nell'URL (`slug`).
     *
     * - `usa_indirizzo_comitato`: il corso usa l'indirizzo del comitato invece di una sede del catalogo.
     * - `posti_max`: solo informativo. `iscrizioni_chiusura_at` invece chiude davvero il modulo pubblico.
     * - Annullamento: un corso con iscritti si annulla invece di eliminarlo, senza perdere lo storico
     *   (`annullato_at`, `motivo_annullamento` mostrato anche nella pagina pubblica, `annullato_da`).
     *   Reversibile solo finché non è passata `data_fine` (Corso::puoRiattivare()).
     * - `chiuso`: interruttore manuale a uso interno (filtro nell'elenco admin), senza effetti sulla pagina
     *   pubblica, che dipende solo da annullamento, date e `iscrizioni_chiusura_at` (Corso::statoPubblico()).
     */
    public function up(): void
    {
        Schema::create('corsi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipologia_corso_id')->constrained('tipologie_corso');
            $table->foreignId('sede_corso_id')->nullable()->constrained('sedi_corso')->nullOnDelete();
            $table->dateTime('data_inizio');
            $table->dateTime('data_fine');
            $table->boolean('usa_indirizzo_comitato')->default(false);
            $table->foreignId('admin_id')->constrained('admins'); // creato da
            $table->string('slug')->unique();
            $table->string('protocollo')->unique();
            $table->decimal('costo', 8, 2)->default(0);
            $table->unsignedInteger('posti_max')->nullable();
            $table->dateTime('iscrizioni_chiusura_at')->nullable();
            $table->text('descrizione')->nullable();
            $table->boolean('pubblicato')->default(true);
            $table->dateTime('annullato_at')->nullable();
            $table->string('motivo_annullamento')->nullable();
            $table->foreignId('annullato_da')->nullable()->constrained('admins')->nullOnDelete();
            $table->boolean('chiuso')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corsi');
    }
};
