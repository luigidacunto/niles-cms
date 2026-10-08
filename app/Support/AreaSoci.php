<?php

namespace App\Support;

use App\Models\CommitteeInfo;
use App\Models\DocumentCategory;

/**
 * Punto unico per sapere se l'area soci è attiva (interruttore in Dati comitato) e per la categoria
 * documenti riservata che ospita gli allegati. Spenta = nascosta ovunque, mai cancellato nulla.
 */
class AreaSoci
{
    public static function attiva(): bool
    {
        return (bool) CommitteeInfo::current()->area_soci_attiva;
    }

    /** Categoria documenti riservata (scope `soci`): file su disco privato, scaricabili solo da soci/admin. */
    public static function categoriaDocumenti(): DocumentCategory
    {
        return DocumentCategory::firstOrCreate(
            ['slug' => 'riservati-soci'],
            ['name' => 'Riservati ai soci', 'scope' => DocumentCategory::SCOPE_SOCI, 'selectable' => false, 'order' => 900,
                'description' => 'Allegati dell\'area soci: non pubblici.'],
        );
    }
}
