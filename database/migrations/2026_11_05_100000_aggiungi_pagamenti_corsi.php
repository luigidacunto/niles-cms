<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gestione facoltativa dei pagamenti dei corsi (la fattura la emette l'amministrazione sul proprio sistema,
 * qui si registrano solo numero, data e incasso):
 *
 * - `committee_info.pagamenti_corsi_attivi`: interruttore generale (spento = nessuna voce di pannello).
 * - `dati_fatturazione_corso`: un blocco di fatturazione = una fattura, quindi i dati stanno qui (1:1).
 *   Fattura e ricevuta sono due documenti separati e coesistono; «emessa» = numero e data presenti.
 *   L'incasso registra l'importo reale (può differire dal totale: allora `incasso_nota` spiega perché).
 * - `iscrizioni_corso.dati_fatturazione_corso_id` diventa facoltativo: i corsi gratuiti non raccolgono più
 *   dati di fatturazione. Le righe esistenti restano invariate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('committee_info', function (Blueprint $table) {
            $table->boolean('pagamenti_corsi_attivi')->default(false);
        });

        Schema::table('dati_fatturazione_corso', function (Blueprint $table) {
            $table->string('fattura_numero', 50)->nullable();
            $table->date('fattura_data')->nullable();
            $table->string('ricevuta_numero', 50)->nullable();
            $table->date('ricevuta_data')->nullable();
            $table->decimal('incassato_importo', 8, 2)->nullable();
            $table->dateTime('incassato_at')->nullable();
            $table->text('incasso_nota')->nullable();
            $table->foreignId('pagamento_registrato_da')->nullable()->constrained('admins')->nullOnDelete();
            $table->dateTime('pagamento_registrato_at')->nullable();
        });

        Schema::table('iscrizioni_corso', function (Blueprint $table) {
            $table->foreignId('dati_fatturazione_corso_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Le iscrizioni senza fatturazione non si possono riportare a NOT NULL: nessuna azione sulla colonna.
        Schema::table('dati_fatturazione_corso', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pagamento_registrato_da');
            $table->dropColumn(['fattura_numero', 'fattura_data', 'ricevuta_numero', 'ricevuta_data',
                'incassato_importo', 'incassato_at', 'incasso_nota', 'pagamento_registrato_at']);
        });

        Schema::table('committee_info', function (Blueprint $table) {
            $table->dropColumn('pagamenti_corsi_attivi');
        });
    }
};
