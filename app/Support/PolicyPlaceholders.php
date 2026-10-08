<?php

namespace App\Support;

use App\Models\CommitteeInfo;

/** Segnaposto disponibili nel testo delle informative privacy (App\Models\PrivacyPolicy). */
class PolicyPlaceholders
{
    public static function sostituisci(string $testo): string
    {
        $info = CommitteeInfo::current();

        return str_replace(
            ['{nome_comitato}', '{email_comitato}', '{telefono_comitato}', '{indirizzo_comitato}', '{piva_comitato}', '{codice_fiscale_comitato}', '{pec_comitato}'],
            [
                $info->denominazione ?: config('app.public_name'),
                $info->email,
                $info->telefono,
                $info->indirizzo,
                $info->piva,
                $info->codice_fiscale,
                $info->pec,
            ],
            $testo
        );
    }
}
