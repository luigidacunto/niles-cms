<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Posta in uscita: indirizzo «Rispondi a» generale e notifica delle iscrizioni ai corsi alla segreteria (vedi App\Support\PostaInUscita). */
    public function up(): void
    {
        Schema::table('committee_info', function (Blueprint $table) {
            $table->string('mail_rispondi_a')->nullable();                        // Reply-To di tutte le email in uscita
            $table->boolean('mail_notifica_iscrizioni')->default(false);          // avviso alla segreteria a ogni iscrizione pubblica
            $table->text('mail_notifica_iscrizioni_a')->nullable();               // indirizzi, uno per riga
        });
    }

    public function down(): void
    {
        Schema::table('committee_info', function (Blueprint $table) {
            $table->dropColumn(['mail_rispondi_a', 'mail_notifica_iscrizioni', 'mail_notifica_iscrizioni_a']);
        });
    }
};
