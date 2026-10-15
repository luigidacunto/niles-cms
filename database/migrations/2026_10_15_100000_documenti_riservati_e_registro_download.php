<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Documenti riservati ai soci: sottocategoria (solo organizzazione), categorie assegnate ai singoli soci
     * (le categorie `soci` decidono chi vede cosa) e registro delle anteprime/download.
     * Solo aggiunte: nessun dato esistente viene modificato.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('sottocategoria', 100)->nullable()->after('type');
        });

        Schema::create('member_document_category', function (Blueprint $table) {
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_category_id')->constrained()->cascadeOnDelete();
            $table->primary(['member_id', 'document_category_id']);
        });

        // `document_title` / `member_label` sono istantanee: il registro resta leggibile anche se documento o
        // socio vengono poi rinominati o rimossi. Conservato 12 mesi (pulizia in DocumentoAccesso::record()).
        Schema::create('documento_accessi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id')->nullable()->index();
            $table->string('document_title');
            $table->unsignedBigInteger('member_id')->nullable()->index();
            $table->string('member_label');
            $table->string('event', 10);               // anteprima | download
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documento_accessi');
        Schema::dropIfExists('member_document_category');
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('sottocategoria');
        });
    }
};
