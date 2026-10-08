<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catalogo delle tipologie di corso attivabili per la popolazione (es. BLSD, Primo Soccorso Aziendale),
     * gestito dagli admin e popolato con un set base dal seeder di prima installazione. Un corso (istanza
     * concreta creata da un editor) sceglie una tipologia da qui.
     *
     * - `sigla`: prefisso del protocollo dei corsi (es. BLSD).
     * - `costo_predefinito`: costo suggerito per un nuovo corso, solo per precompilare il campo (modificabile).
     * - `validita_tipo` / `validita_valore`: validità dell'attestato. `fine_anno` = scade il 31/12 dopo N anni,
     *   `mesi` = scade a data precisa, N mesi dopo il corso; null = non scade.
     * - `immagine`: immagine predefinita della tipologia, usata da tutti i suoi corsi (elenco, pagina di
     *   iscrizione, anteprima social).
     */
    public function up(): void
    {
        Schema::create('tipologie_corso', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('sigla')->unique();
            $table->boolean('rilascia_attestato')->default(false);
            $table->decimal('costo_predefinito', 8, 2)->default(0);
            $table->string('validita_tipo', 10)->nullable();
            $table->unsignedSmallInteger('validita_valore')->nullable();
            $table->string('immagine')->nullable();
            $table->boolean('attivo')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipologie_corso');
    }
};
