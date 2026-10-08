<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Comunicazioni interne ai soci: sezione dedicata, NON una categoria dei post pubblici. I post alimentano
     * home, archivi, sitemap e SEO: tenerli separati esclude per costruzione la fuga di contenuti riservati.
     */
    public function up(): void
    {
        Schema::create('comunicazioni_soci', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->boolean('published')->default(true);
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('last_edited_at')->nullable();
            $table->string('author_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comunicazioni_soci');
    }
};
