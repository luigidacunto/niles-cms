<?php

// Risorse a cui si possono limitare i `permissions` (lettura/scrittura) di un editor. Aggiungere una voce
// quando si crea una nuova sezione del pannello: la leggono sia Admin::hasPermission() sia il form degli utenti.
// Ordine = ordine nel form: prima le sezioni con permesso unico (Admin::COMBINED_PERMISSIONS), poi quelle separate.
return [
    'posts' => 'Post',
    'pages' => 'Pagine',
    'trasparenza' => 'Trasparenza',   // documenti pubblicati nella pagina Trasparenza
    'documents' => 'Documenti',        // libreria documenti generica (link nei contenuti)
    'images' => 'Immagini',
    'files' => 'File',
    'comunicazioni_soci' => 'Comunicazioni soci', // post e allegati riservati
    'membri' => 'Membri',              // anagrafica membri comitato
    'pagamenti_corsi' => 'Pagamenti corsi', // fatture/incassi dei corsi (solo se attivi in Dati comitato)
    'corsi' => 'Corsi',                // ultima: è l'unica con lettura e scrittura separate
];
