<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            // Ogni post appartiene a una categoria, che non si elimina finché ne ha (restrictOnDelete).
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('excerpt', 1000)->nullable();
            $table->longText('body')->nullable();
            $table->string('cover_image', 2000)->nullable();
            $table->boolean('published')->default(false);
            $table->timestamp('published_at')->nullable();
            // Data dell'ultima modifica editoriale: distinta da updated_at, che registra solo l'ultima scrittura
            // della riga (es. un import) e non quando una persona ha cambiato il contenuto. Alimenta i segnali
            // di freschezza (dateModified, lastmod della sitemap) e si mostra a chi lavora nel pannello.
            $table->timestamp('last_edited_at')->nullable();
            $table->string('author_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
