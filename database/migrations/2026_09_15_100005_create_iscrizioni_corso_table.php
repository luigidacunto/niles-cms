<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un'iscrizione = un singolo partecipante a un corso (mai un record con più nominativi, anche quando il
     * modulo pubblico ne raccoglie più di uno in un solo invio). `dati_fatturazione_corso_id` può essere
     * condiviso da più iscrizioni dello stesso invio o dedicato a una sola; `persona_id` collega l'anagrafica
     * trasversale deduplicata; `referente_persona_id` è chi ha compilato il modulo per altri (vuoto se l'iscritto
     * si è iscritto da solo o coincide con il referente).
     *
     * - `presenza_confermata_at`: conferma di presenza data dalla persona dalla sua pagina personale.
     * - `ritirata_at` / `nota_ritiro`: iscritto «ritirato» (non frequenta più): la riga resta e si può ripristinare.
     */
    public function up(): void
    {
        Schema::create('iscrizioni_corso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('corso_id')->constrained('corsi');
            $table->foreignId('dati_fatturazione_corso_id')->constrained('dati_fatturazione_corso');
            $table->foreignId('persona_id')->constrained('persone');
            $table->foreignId('referente_persona_id')->nullable()->constrained('persone')->nullOnDelete();
            $table->string('nome');
            $table->string('cognome');
            $table->string('email');
            $table->string('telefono')->nullable();
            $table->string('codice_fiscale', 16);
            $table->string('via')->nullable();
            $table->string('comune')->nullable();
            $table->string('provincia', 2)->nullable();
            $table->string('cap', 5)->nullable();
            $table->dateTime('privacy_accettata_at');
            $table->dateTime('autodichiarazione_terzi_at')->nullable();
            $table->dateTime('presenza_confermata_at')->nullable();
            $table->dateTime('ritirata_at')->nullable();
            $table->string('nota_ritiro')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iscrizioni_corso');
    }
};
