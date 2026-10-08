<?php

// Limiti di richieste per indirizzo IP sul modulo pubblico di iscrizione ai corsi (rate limiting). Questi sono
// i valori predefiniti: dal pannello (Sicurezza moduli) si possono sovrascrivere, e lasciando un campo vuoto si
// torna qui. Due soglie insieme — una finestra corta ferma i bot a raffica, una lunga quelli lenti.
// Vedi App\Models\SicurezzaForm::limite() e AppServiceProvider::boot().
return [
    'limiti' => [
        'invii_minuto' => 5,      // invii del modulo di iscrizione (anche quelli respinti dalla validazione)
        'invii_ora' => 40,
        'visite_minuto' => 30,    // aperture della pagina di iscrizione di un corso
        'visite_ora' => 300,
    ],
];
