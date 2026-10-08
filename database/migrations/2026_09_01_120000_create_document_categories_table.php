<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aree documenti. `scope`:
     *  - `trasparenza`: mostrate su /trasparenza, gestite dalla sezione Trasparenza del pannello;
     *  - `library`: libreria documenti generica (link nei post e nelle pagine, storico dei file);
     *  - `soci`: documenti riservati ai soci.
     * `selectable = false` indica una categoria riservata: esiste ma non è scegliibile in un caricamento
     * manuale, ci finiscono solo i file caricati dai rispettivi costrutti (es. l'organigramma).
     */
    public function up(): void
    {
        Schema::create('document_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('scope')->default('library');
            $table->boolean('selectable')->default(true);
            $table->string('description')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_categories');
    }
};
