<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    | Nome del sito pubblico (nome del comitato), usato nel tag <title> delle pagine pubbliche
    | (`config('app.public_name')`). Distinto da `name` ('NILES', nome interno del progetto/pannello).
    | Si imposta per installazione via APP_PUBLIC_NAME nel .env — nessun nome di comitato è cablato qui,
    | così il progetto resta riusabile da altri comitati. Fallback su APP_NAME se non impostato.
    |
    */
    'public_name' => env('APP_PUBLIC_NAME') ?: env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Version
    |--------------------------------------------------------------------------
    |
    | NILES project version (semantic versioning: patch for fixes, minor for new features).
    | Bumped only at commit time.
    |
    */

    'version' => '1.0.0',

    /*
    | Controllo aggiornamenti: la dashboard del pannello (solo amministratori) confronta la versione
    | installata con l'ultima release pubblicata sul repository (API GitHub, risultato in cache).
    | Se non risponde o non ci sono release non mostra nulla. Si disattiva con NILES_UPDATE_CHECK=false.
    */
    'update_check' => [
        'enabled' => (bool) env('NILES_UPDATE_CHECK', true),
        'repository' => 'luigidacunto/niles-cms',
    ],

    /*
    | Primo amministratore creato da FirstInstallSeeder (solo se non esiste alcun admin).
    | Senza INITIAL_ADMIN_PASSWORD viene generata una password casuale, mostrata una sola volta dal comando.
    */
    'initial_admin' => [
        'email' => env('INITIAL_ADMIN_EMAIL', 'administrator@example.it'),
        'password' => env('INITIAL_ADMIN_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => 'UTC',

    /*
    | Fuso orario "da muro" del comitato. ⚠️ Date e orari dei corsi (data_inizio/data_fine) sono salvati come ora
    | locale così com'è scritta (l'app gira in UTC): per qualunque uso esterno che richieda un istante preciso
    | (JSON-LD, calendario .ics) vanno interpretati in questo fuso — vedi Corso::inizioLocale().
    */
    'committee_timezone' => env('COMMITTEE_TIMEZONE', 'Europe/Rome'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache", "array"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
