<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Storico immutabile dei consensi della popolazione dei corsi (prova del consenso): una riga per ogni evento
     * di una finalità (promemoria sugli attestati, newsletter). Lo stato attuale è sulla persona (`persone`).
     */
    public function up(): void
    {
        Schema::create('consensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained('persone')->cascadeOnDelete();
            $table->string('finalita', 20);   // promemoria | newsletter
            $table->string('evento', 12);     // richiesto | confermato | revocato
            $table->string('origine', 20);    // form_pubblico | admin | link_personale | migrazione
            $table->foreignId('iscrizione_corso_id')->nullable()->constrained('iscrizioni_corso')->nullOnDelete();
            $table->text('testo')->nullable(); // testo esatto mostrato alla persona in quel momento
            $table->timestamp('created_at')->useCurrent();
            $table->index(['persona_id', 'finalita']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consensi');
    }
};
