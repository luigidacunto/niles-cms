<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Posta in uscita configurabile dal pannello (vedi App\Support\PostaInUscita): tutte colonne facoltative. */
    public function up(): void
    {
        Schema::table('committee_info', function (Blueprint $table) {
            $table->string('mail_mailer', 10)->nullable();           // smtp | log
            $table->string('mail_host')->nullable();
            $table->unsignedSmallInteger('mail_port')->nullable();
            $table->string('mail_encryption', 10)->nullable();       // ssl | tls | none
            $table->string('mail_username')->nullable();
            $table->text('mail_password')->nullable();               // cifrata (cast `encrypted`)
            $table->string('mail_from_address')->nullable();
            $table->string('mail_from_name')->nullable();
            $table->timestamp('mail_prova_at')->nullable();
            $table->text('mail_prova_esito')->nullable();            // "ok" oppure il messaggio d'errore
            $table->json('mail_prova_parametri')->nullable();        // server, porta, cifratura, utente, mittente: mai la password
            $table->string('mail_modificata_da')->nullable();        // nome dell'amministratore
            $table->timestamp('mail_modificata_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('committee_info', function (Blueprint $table) {
            $table->dropColumn([
                'mail_mailer', 'mail_host', 'mail_port', 'mail_encryption', 'mail_username', 'mail_password',
                'mail_from_address', 'mail_from_name', 'mail_prova_at', 'mail_prova_esito', 'mail_prova_parametri',
                'mail_modificata_da', 'mail_modificata_at',
            ]);
        });
    }
};
