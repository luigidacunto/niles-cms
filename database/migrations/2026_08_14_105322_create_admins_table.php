<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Utenti del pannello.
     *
     * - `role`: `admin` = accesso completo, `editor` = limitato da `permissions` e dalle categorie assegnate
     *   (`admin_category`, o tutte con `all_categories`). `permissions` e `all_categories` hanno senso solo per
     *   gli editor: l'admin li ignora (vedi Admin::can() e Admin::canManageCategory()).
     * - `password_login_enabled`: spento di default. Si entra con un codice monouso inviato per email; l'accesso
     *   con password va concesso account per account.
     * - `protected`: utente non eliminabile e che non può perdere il ruolo admin dal pannello (il primo admin).
     */
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('role')->default('editor');
            $table->json('permissions')->nullable();
            $table->boolean('all_categories')->default(false);
            $table->boolean('password_login_enabled')->default(false);
            $table->boolean('active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->boolean('protected')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
