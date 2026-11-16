<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Presenza effettiva al corso, segnata dall'operatore dall'elenco iscritti (base per il rilascio degli attestati).
 * Distinta da `presenza_confermata_at`, che resta com'è: è il clic della persona sul link dell'email, non una presenza.
 * `null` = non segnato; il valore è l'istante in cui l'operatore l'ha segnata (togliendo la presenza torna `null`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('iscrizioni_corso', function (Blueprint $table) {
            $table->dateTime('presente_at')->nullable()->after('presenza_confermata_at');
        });
    }

    public function down(): void
    {
        Schema::table('iscrizioni_corso', function (Blueprint $table) {
            $table->dropColumn('presente_at');
        });
    }
};
