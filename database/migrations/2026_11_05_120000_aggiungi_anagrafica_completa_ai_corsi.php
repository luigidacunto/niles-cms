<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anagrafica completa per il rilascio dell'attestato: la tipologia può richiedere data e luogo di nascita agli iscritti.
 * I dati stanno sull'iscrizione (come la residenza). `nascita_estero` è null quando il dato non è stato raccolto
 * (iscrizioni esistenti o corsi che non lo richiedono); per chi è nato in Italia valgono comune e provincia, per chi è nato
 * all'estero `nascita_stato` (nome dello Stato, testo: vedi App\Models\Stato). La tabella `stati` è riutilizzabile altrove.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stati', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100)->unique();
            $table->string('codice_iso', 2)->nullable()->unique();
            $table->boolean('attivo')->default(true);
            $table->timestamps();
        });

        Schema::table('tipologie_corso', function (Blueprint $table) {
            $table->boolean('anagrafica_completa')->default(false);
        });

        Schema::table('iscrizioni_corso', function (Blueprint $table) {
            $table->date('data_nascita')->nullable();
            $table->boolean('nascita_estero')->nullable();
            $table->string('nascita_comune')->nullable();
            $table->string('nascita_provincia', 2)->nullable();
            $table->string('nascita_stato', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('iscrizioni_corso', function (Blueprint $table) {
            $table->dropColumn(['data_nascita', 'nascita_estero', 'nascita_comune', 'nascita_provincia', 'nascita_stato']);
        });

        Schema::table('tipologie_corso', function (Blueprint $table) {
            $table->dropColumn('anagrafica_completa');
        });

        Schema::dropIfExists('stati');
    }
};
