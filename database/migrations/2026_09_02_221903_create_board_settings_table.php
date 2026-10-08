<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riga singola (id=1) con le impostazioni della sezione Struttura Organizzativa.
     * Per ora solo l'aggancio all'organigramma: un `Document` della libreria (categoria
     * riservata `organigramma`). "Togliere dalla pagina" = mettere a null questo campo;
     * il Document resta in libreria come storico.
     */
    public function up(): void
    {
        Schema::create('board_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organigramma_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_settings');
    }
};
