<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Informative privacy per tipologia (es. 'corsi-popolazione'), con un testo predefinito (seedato,
     * `is_default=true`) sempre presente come fallback e un eventuale override personalizzato
     * (`is_default=false`) creabile da pannello — se presente ha la precedenza. `testo` contiene
     * segnaposto letterali (`{nome_comitato}` ecc., vedi App\Support\PolicyPlaceholders), sostituiti
     * solo in lettura, mai al salvataggio. Vedi App\Models\PrivacyPolicy.
     */
    public function up(): void
    {
        Schema::create('privacy_policies', function (Blueprint $table) {
            $table->id();
            $table->string('tipo');
            $table->boolean('is_default');
            $table->string('titolo');
            $table->longText('testo');
            $table->timestamps();

            // Al massimo una riga default e una override per tipologia — vincolo a livello DB.
            $table->unique(['tipo', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_policies');
    }
};
