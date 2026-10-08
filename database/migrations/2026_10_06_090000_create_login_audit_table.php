<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registro degli accessi (admin e soci). `label` è un'istantanea del nome: il registro resta leggibile anche
     * se l'utente viene poi rinominato o rimosso. Conservato 12 mesi (pulizia in LoginAudit::record()).
     */
    public function up(): void
    {
        Schema::create('login_audit', function (Blueprint $table) {
            $table->id();
            $table->string('guard', 10);               // admin | member
            $table->string('event', 10);               // success | failed
            $table->unsignedBigInteger('subject_id')->nullable(); // admin_id / member_id, null se l'email non corrisponde a nessuno
            $table->string('label')->nullable();       // nome al momento dell'evento, oppure l'email digitata (accesso fallito)
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_audit');
    }
};
