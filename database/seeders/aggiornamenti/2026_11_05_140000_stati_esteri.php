<?php

use App\Models\Stato;
use Illuminate\Database\Seeder;

// Elenco degli Stati esteri (config/stati.php) per il luogo di nascita dei corsi con anagrafica completa.
// Solo creazione, per codice ISO: non rinomina né riattiva gli Stati già presenti. Sulle nuove installazioni lo crea FirstInstallSeeder.
return new class extends Seeder
{
    public function run(): void
    {
        Stato::popolaPredefiniti();
    }
};
