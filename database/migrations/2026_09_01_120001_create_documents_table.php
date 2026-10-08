<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Documenti caricati. Un documento senza categoria è per convenzione un documento della libreria generica;
     * nella Trasparenza la categoria è obbligatoria (validazione nel controller). L'area non si elimina finché ha
     * documenti (controllo in DocumentCategoryController::destroy): niente cancellazione a cascata di file
     * pubblici. L'ordine pubblico è sempre per data di pubblicazione decrescente.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            // «Tipologia»: etichetta libera stile tag, autocompletata da SELECT DISTINCT nel form.
            $table->string('type')->nullable()->index();
            $table->string('file_path');
            $table->string('original_filename')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->date('published_at')->nullable();
            $table->boolean('published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
