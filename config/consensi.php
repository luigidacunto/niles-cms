<?php

// Finalità di consenso della popolazione dei corsi (vedi App\Models\Persona::registraConsenso). Il testo è
// quello mostrato accanto alla spunta e salvato tal quale nel registro `consensi` come prova: cambiarlo
// vuol dire un testo nuovo per i consensi futuri, mai riscrivere quelli già dati.
return [
    'promemoria' => [
        'label' => 'Promemoria scadenza attestati',
        'testo' => 'Acconsento a ricevere dal Comitato promemoria sulla scadenza del mio attestato di formazione.',
    ],
    'newsletter' => [
        'label' => 'Newsletter e inviti a eventi',
        'testo' => 'Acconsento a ricevere dal Comitato la newsletter e inviti a eventi e corsi di formazione futuri. Non invieremo pubblicità di terzi.',
    ],
];
