<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pagine del sito, ad albero (`parent_id`).
     *
     * - `in_menu`: la pagina compare nel menu principale.
     * - `system`: pagina di sistema, prevista di base per un sito di comitato CRI. Dal pannello non si elimina né
     *   si sposta o ripersonalizza (genitore, slug, ordine, menu, template); resta modificabile il testo
     *   introduttivo e si disattiva mettendola in bozza (`published = false`).
     * - `category_id`: «pagina sezione», una pagina di sistema legata a una categoria di notizie. Mostra sotto il
     *   testo introduttivo le ultime notizie della categoria e un pulsante verso l'archivio (`/{slug}/archivio`).
     *   Il legame è fissato dal seeder di prima installazione, non modificabile dal pannello.
     * - `template`: vista alternativa (es. `corsi`), vedi il gancio di template delle pagine.
     * - `embed_html`: blocco HTML grezzo, reso senza sanitizzazione sotto il corpo. Serve per gli embed con
     *   `<script>` o `<form>` delle piattaforme di donazione, che il sanitizzatore del `body` blocca per sicurezza.
     *   Modificabile solo da un admin.
     */
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('in_menu')->default(false);
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('excerpt', 1000)->nullable();
            $table->longText('body')->nullable();
            $table->longText('embed_html')->nullable();
            $table->string('template')->nullable();
            $table->boolean('published')->default(false);
            $table->boolean('system')->default(false);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
