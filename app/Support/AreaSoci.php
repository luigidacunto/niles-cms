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

    /** Categoria riservata di sistema «Senza categoria» (scope `soci`): file su disco privato, visibili a tutti i soci. */
    public static function categoriaDocumenti(): DocumentCategory
    {
        return DocumentCategory::firstOrCreate(
            ['slug' => DocumentCategory::SLUG_GENERALE],
            ['name' => 'Riservati ai soci', 'scope' => DocumentCategory::SCOPE_SOCI, 'selectable' => false, 'order' => 900,
                'description' => 'Allegati dell\'area soci: non pubblici.'],
        );
    }
}
