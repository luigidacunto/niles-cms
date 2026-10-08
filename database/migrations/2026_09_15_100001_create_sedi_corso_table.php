<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catalogo delle sedi selezionabili per un Corso (tipicamente 1-2 per comitato, sempre le stesse) —
     * gestito da admin, seedato con un set base. Evita di riscrivere l'indirizzo ad ogni corso creato.
     */
    public function up(): void
    {
        Schema::create('sedi_corso', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('via')->nullable();
            $table->string('comune')->nullable();
            $table->string('provincia', 2)->nullable();
            $table->string('cap', 5)->nullable();
            $table->boolean('attivo')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sedi_corso');
    }
};
