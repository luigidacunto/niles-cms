<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Statistiche cookieless (sempre attive)
    |--------------------------------------------------------------------------
    |
    | Tracker senza cookie né dati personali (aggregati): per il GDPR non
    | richiede consenso, quindi viene caricato sempre (solo in produzione).
    | Pensato per GoatCounter (hosted oppure self-hosted): lo <script> emesso
    | usa l'attributo `data-goatcounter`, quindi altri tool (Umami, Plausible…)
    | richiederebbero un adattamento della vista. Se `src` è vuoto non viene
    | emesso nulla.
    |
    | GoatCounter va installato come servizio a parte (un binario Go + SQLite
    | sulla VM) — non fa parte di questo repo.
    |
    */
    'cookieless' => [
        'src' => env('ANALYTICS_COOKIELESS_SRC'),           // es. //gc.zgo.at/count.js
        'endpoint' => env('ANALYTICS_COOKIELESS_ENDPOINT'), // es. https://niles.example.org/count
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Analytics 4 (opt-in)
    |--------------------------------------------------------------------------
    |
    | Layer opzionale: caricato SOLO dopo che il visitatore accetta la
    | categoria "Statistiche" nel banner cookie. Se `id` è vuoto il banner
    | non ha nulla da sbloccare e in produzione non compare affatto.
    |
    | ⚠️ GA4 comporta il trasferimento di dati verso gli USA: il Garante lo
    | ha ritenuto problematico (provv. 2022 su Universal Analytics). Attivarlo
    | è una scelta del singolo comitato, che se ne assume la responsabilità.
    |
    */
    'ga' => [
        'id' => env('GA4_ID'), // es. G-XXXXXXXXXX
    ],

];
