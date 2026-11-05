<?php

namespace App\Support;

use App\Models\CommitteeInfo;

/** Gestione facoltativa dei pagamenti dei corsi (interruttore in Dati comitato). Spenta = nessuna voce di pannello. */
class PagamentiCorsi
{
    public static function attivi(): bool
    {
        return (bool) CommitteeInfo::current()->pagamenti_corsi_attivi;
    }
}
