<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membri della struttura organizzativa del comitato, resi sulla pagina di sistema
     * `/struttura-organizzativa`.
     * Due slot fissi (`president`, `vice_president`) + N blocchi liberi (`member`) con
     * ruolo a testo libero (consiglieri, consigliere giovani, referenti, ...).
     */
    public function up(): void
    {
        Schema::create('board_members', function (Blueprint $table) {
            $table->id();
            $table->string('slot')->default('member'); // president | vice_president | member
            $table->string('role_label')->nullable();  // usato solo per slot=member
            $table->string('name');
            $table->text('bio')->nullable();
            $table->string('photo_path')->nullable();
            $table->unsignedInteger('order')->default(0); // ordina i blocchi liberi
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_members');
    }
};
