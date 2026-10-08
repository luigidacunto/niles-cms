<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Galleria fotografica di un post: album per singolo post, non un'entità riutilizzabile. Le foto sono rese
     * in fondo alla pagina del post come griglia. `cascadeOnDelete`: una foto non esiste fuori dal suo post (a
     * differenza di `documents`, che è `restrictOnDelete`).
     */
    public function up(): void
    {
        Schema::create('post_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('path');                 // relativo al disco "public", es. post-images/foo.jpg
            $table->string('caption')->nullable();  // didascalia sotto la foto
            $table->string('alt')->nullable();      // testo alternativo; la vista ripiega su didascalia e titolo del post
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_images');
    }
};
