<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Registro dei seeder di aggiornamento già eseguiti (vedi App\Support\SeederRegistry), come `migrations` per le migrazioni. */
    public function up(): void
    {
        Schema::create('seeders_eseguiti', function (Blueprint $table) {
            $table->id();
            $table->string('seeder')->unique();     // nome del file senza estensione (data inclusa)
            $table->unsignedInteger('batch');       // 0 = già coperto dalla prima installazione, non eseguito
            $table->timestamp('eseguito_il');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seeders_eseguiti');
    }
};
