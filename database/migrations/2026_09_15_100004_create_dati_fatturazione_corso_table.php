<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dati di fatturazione e pagamento raccolti nel modulo pubblico di iscrizione. Entità separata
     * dall'iscrizione apposta: un record può essere condiviso da più iscrizioni (pagamento unico per il gruppo)
     * oppure averne uno per ciascuna (fatturazione separata). Nessun gateway di pagamento: solo dati per la
     * fatturazione o la ricevuta manuale. `codice_destinatario` (SDI) e `pec` servono alla fattura elettronica;
     * senza, la fattura va nel cassetto fiscale del cliente.
     */
    public function up(): void
    {
        Schema::create('dati_fatturazione_corso', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['privato', 'azienda']);
            $table->string('nome')->nullable();
            $table->string('cognome')->nullable();
            $table->string('ragione_sociale')->nullable(); // o nome del libero professionista, se tipo = azienda
            $table->string('partita_iva', 11)->nullable();
            $table->string('codice_fiscale', 16)->nullable(); // privato: CF personale; azienda: eventuale CF dell'associazione
            $table->string('via')->nullable();
            $table->string('comune')->nullable();
            $table->string('provincia', 2)->nullable();
            $table->string('cap', 5)->nullable();
            $table->string('codice_destinatario', 7)->nullable();
            $table->string('pec')->nullable();
            $table->string('email')->nullable();
            $table->string('telefono')->nullable();
            $table->string('metodo_pagamento')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dati_fatturazione_corso');
    }
};
