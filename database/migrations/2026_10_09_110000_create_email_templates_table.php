<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Template delle email di sistema, per tipo (config/email_templates.php): un testo predefinito seedato
     * (`is_default=true`, sola lettura) e un'eventuale personalizzazione (`is_default=false`) che ha la
     * precedenza — stesso schema delle informative privacy. Un solo default e un solo override per tipo.
     */
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 60);
            $table->boolean('is_default')->default(true);
            $table->string('oggetto');
            $table->longText('corpo');
            $table->timestamps();

            $table->unique(['tipo', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
