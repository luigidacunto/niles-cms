<?php

// Risorse a cui si possono limitare i `permissions` (lettura/scrittura) di un editor. Aggiungere una voce
// quando si crea una nuova sezione del pannello: la leggono sia Admin::can() sia il form degli utenti.
return [
    'posts' => 'Post',
    'pages' => 'Pagine',
    'trasparenza' => 'Trasparenza',   // documenti pubblicati nella pagina Trasparenza
    'documents' => 'Documenti',        // libreria documenti generica (link nei contenuti)
    'images' => 'Immagini',
    'files' => 'File',
    'corsi' => 'Corsi',
    'comunicazioni_soci' => 'Comunicazioni soci', // post e allegati riservati; permesso unico (Admin::COMBINED_PERMISSIONS)
    'membri' => 'Membri',              // anagrafica membri comitato; permesso unico (vedi Admin::COMBINED_PERMISSIONS)
];
