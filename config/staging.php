<?php

/*
|--------------------------------------------------------------------------
| Staging pubblico visibile solo agli admin
|--------------------------------------------------------------------------
|
| Per un ambiente di staging che condivide dati con la produzione (usato per test/upgrade prima di
| passarli in produzione, non solo un ambiente di sviluppo): il sito pubblico non deve essere visibile
| a chiunque, solo a chi sta effettivamente testando (già loggato su /admin nello stesso browser).
|
| Slegato da APP_ENV apposta: in locale/altri ambienti non-production si continua a vedere il sito
| pubblico senza login, come sempre — questo flag va attivato SOLO sull'istanza di staging dedicata.
| Vedi App\Http\Middleware\GateStagingToAdmins.
|
*/

return [
    'gate_public' => (bool) env('STAGING_GATE_PUBLIC', false),
];
