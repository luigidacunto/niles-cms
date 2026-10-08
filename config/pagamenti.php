<?php

// Catalogo dei metodi di pagamento dei corsi (nessun pagamento online diretto: il comitato incassa e fattura).
// Dal pannello (Dati del comitato) si attivano/disattivano con una spunta; `default` = attivo finché non si configura
// nulla. La `label` è il testo mostrato nei form e salvato in `dati_fatturazione_corso.metodo_pagamento`: i valori già
// salvati nelle iscrizioni non cambiano mai. Per aggiungere un metodo: una riga qui. ⚠️ Il codice `bonifico` è
// speciale: per chi lo sceglie email e pagina personale mostrano le coordinate bancarie (App\Support\DatiPagamento).
return [
    'metodi' => [
        'bonifico' => ['label' => 'Bonifico', 'default' => true],
        'contanti' => ['label' => 'Contanti', 'default' => true],
        'pos' => ['label' => 'POS', 'default' => false],
        'assegno' => ['label' => 'Assegno', 'default' => false],
        'bollettino' => ['label' => 'Bollettino postale', 'default' => false],
    ],
];
