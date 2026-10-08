<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allegati: collega un `Document` esistente (Libreria) a un post o una pagina — non un file
     * caricato apposta (niente storage duplicato, il documento resta gestito/riusabile da
     * `/admin/documenti`). Polimorfica: stessa tabella per `Post` e `Page` (vedi `Post::attachments()`
     * / `Page::attachments()`). Reso in fondo alla pagina pubblica con un'icona automatica per
     * estensione.
     */
    public function up(): void
    {
        Schema::create('attachable_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('attachable_id');
            $table->string('attachable_type');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['attachable_type', 'attachable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachable_documents');
    }
};
