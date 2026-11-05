<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * IVA del corso, solo informativa per la gestione dei pagamenti: `costo` resta il prezzo finale (comprensivo di IVA
 * se prevista), il modulo pubblico non cambia. La tipologia porta il valore predefinito, il corso lo riprende e
 * può personalizzarlo. Corsi e tipologie esistenti restano «senza IVA».
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['tipologie_corso', 'corsi'] as $tabella) {
            Schema::table($tabella, function (Blueprint $table) {
                $table->boolean('iva_prevista')->default(false);
                $table->decimal('aliquota_iva', 5, 2)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['tipologie_corso', 'corsi'] as $tabella) {
            Schema::table($tabella, function (Blueprint $table) {
                $table->dropColumn(['iva_prevista', 'aliquota_iva']);
            });
        }
    }
};
