<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Link personalizzabili della terza colonna del footer pubblico (vedi App\Models\FooterLink). */
    public function up(): void
    {
        Schema::create('footer_links', function (Blueprint $table) {
            $table->id();
            $table->string('label', 100);
            $table->string('url', 2000);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('footer_links');
    }
};
