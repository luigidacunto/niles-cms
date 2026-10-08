<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anagrafica dei soci del comitato (importata da Excel) e account dell'area soci (accesso con codice
     * monouso inviato per email).
     */
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('codice_fiscale', 16)->unique();
            $table->string('nome');
            $table->string('cognome');
            $table->date('data_nascita')->nullable();
            // Non sempre presente nei file importati; unica perché è la chiave dell'accesso con codice.
            $table->string('email')->nullable()->unique();
            $table->string('telefono')->nullable();          // numero principale
            $table->json('telefoni_aggiuntivi')->nullable(); // altri numeri, lista di stringhe
            // volontario | volontario_estensione | dipendente. Nome esplicito dell'indice: nei database già in
            // uso è rimasto quello della colonna originaria (`stato`), da tenere uguale ovunque.
            $table->string('ruolo', 30)->index('members_stato_index');
            $table->boolean('disabilitato')->default(false); // disabilitazione manuale temporanea; impostata anche alla rimozione
            $table->softDeletes();                           // rimosso dall'import o a mano, in attesa di cancellazione definitiva
            $table->timestamps();
            $table->timestamp('richiesta_disattivazione_at')->nullable(); // il socio chiede di disattivare l'accesso: serve conferma di un admin
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
