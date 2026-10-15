<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Copertina e galleria delle comunicazioni: file sul disco privato (`local`), serviti solo ai soci loggati. */
    public function up(): void
    {
        Schema::table('comunicazioni_soci', function (Blueprint $table) {
            $table->string('cover_image')->nullable()->after('excerpt');
        });

        Schema::create('comunicazione_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comunicazione_id')->constrained('comunicazioni_soci')->cascadeOnDelete();
            $table->string('path');                    // relativo al disco `local`
            $table->string('caption')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comunicazione_images');
        Schema::table('comunicazioni_soci', function (Blueprint $table) {
            $table->dropColumn('cover_image');
        });
    }
};
