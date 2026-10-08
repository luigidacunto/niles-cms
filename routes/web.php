<?php

use App\Http\Controllers\CorsoIscrizioneController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PreferenzeController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StrutturaOrganizzativaController;
use App\Http\Controllers\TrasparenzaController;
use Illuminate\Support\Facades\Route;

// Va richiesta qui, prima delle route pubbliche dinamiche sotto: Laravel prova le route nell'ordine di
// registrazione e si "impegna" sulla prima che combacia per forma di URL (es. /{category}/{post} su
// /admin/login) — se poi il binding del modello fallisce risponde 404 subito, senza mai provare le route
// admin. Devono quindi essere note al router PRIMA di /{category:slug}/{post:slug} e /{page:slug}.
require __DIR__.'/admin.php';
require __DIR__.'/soci.php'; // stesso motivo: /soci/accedi non deve finire in /{category}/{post}

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/news/altre', [HomeController::class, 'moreNews'])->name('home.news.more');

// robots.txt dinamico (sostituisce il file statico): in production apre tutto tranne le aree non pubbliche
// (pannello, area soci, endpoint tecnici/pagine di conferma) e punta al sitemap; in staging/local blocca
// ogni crawler. Si accompagna al meta `robots` nel layout e a NoIndexNonProduction. Il trailing slash
// evita di bloccare per prefisso una pagina pubblica il cui slug inizia per "admin"/"soci".
Route::get('/robots.txt', function () {
    $body = app()->isProduction()
        ? "User-agent: *\nDisallow: /admin/\nDisallow: /soci/\nDisallow: /news/altre\nDisallow: /preferenze/\nDisallow: /corsi/*/iscrizione/confermata\n\nSitemap: ".route('sitemap')."\n"
        : "User-agent: *\nDisallow: /\n";

    return response($body)->header('Content-Type', 'text/plain');
})->name('robots');

// sitemap.xml dinamico: pagine e post pubblicati, letto dal DB ad ogni richiesta (nessuna cache, nessun
// comando da lanciare al deploy — un post pubblicato dal pannello compare alla richiesta successiva).
//
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Literal, vanno registrate prima di /{category:slug}/{post:slug} sotto per lo stesso motivo di admin.php:
// altrimenti "archivi"/"archivio-notizie" (o "archivio-comunicati-stampa") verrebbero letti come
// categoria/post e darebbero 404.
Route::get('/archivi/archivio-notizie', [PostController::class, 'archive'])->name('posts.archive');
Route::get('/archivi/archivio-comunicati-stampa', [PostController::class, 'archiveComunicati'])->name('posts.archive.comunicati');

// Elenco documenti (Trasparenza): route dedicata come gli archivi qui sopra, non il meccanismo
// pages.template. Letterale, quindi prima delle route dinamiche sotto.
Route::get('/trasparenza', [TrasparenzaController::class, 'index'])->name('trasparenza');
// Serve il singolo documento (Trasparenza o libreria) con nome leggibile dal titolo.
Route::get('/documenti/{document}', [DocumentController::class, 'show'])->name('documenti.show');

// Struttura Organizzativa: pagina di sistema resa dal costrutto board_members (route dedicata, non
// pages.template). Letterale, prima delle route dinamiche.
Route::get('/struttura-organizzativa', [StrutturaOrganizzativaController::class, 'show'])->name('struttura-organizzativa');

// Informative privacy per tipologia (default+override, vedi PrivacyPolicy) — generica e riusabile,
// non solo per i corsi. Letterale, prima delle route dinamiche.
Route::get('/privacy/{tipo}', [PrivacyPolicyController::class, 'show'])->name('privacy-policy.show');

// Form pubblico di iscrizione a un corso: solo link diretto (nessuna pagina di elenco), condiviso nei
// post. Throttle per IP: gli slug sono prevedibili (SIGLA-ANNO-NNN), senza
// un elenco pubblico l'unica protezione è che non si trovano per caso — il rate limiting rallenta un
// tentativo sistematico di indovinarli/spammare il form (honeypot lato form per i bot più semplici).
// Limiti nominati (non un numero fisso qui): attivabili/disattivabili da Pannello → Sicurezza moduli
// pubblici senza toccare le route, vedi i RateLimiter::for(...) in AppServiceProvider::boot().
Route::get('/corsi/{corso:slug}/iscrizione', [CorsoIscrizioneController::class, 'show'])
    ->middleware('throttle:corsi-iscrizione-show')->name('corsi.iscrizione.show');
Route::post('/corsi/{corso:slug}/iscrizione', [CorsoIscrizioneController::class, 'store'])
    ->middleware('throttle:corsi-iscrizione-store')->name('corsi.iscrizione.store');
// Pagina personale con token (consensi, richiesta di cancellazione): mai indicizzabile (middleware
// PaginaPersonale + Disallow in robots.txt + niente analytics), throttle per IP contro i tentativi di indovinare
// il token, che comunque è di 64 caratteri casuali: un formato diverso è 404 senza toccare il DB.
Route::middleware([\App\Http\Middleware\PaginaPersonale::class, 'throttle:preferenze'])
    ->prefix('preferenze/{token}')->name('preferenze.')->group(function () {
        Route::get('/', [PreferenzeController::class, 'show'])->where('token', '[A-Za-z0-9]{64}')->name('show');
        Route::post('/consensi', [PreferenzeController::class, 'consensi'])->where('token', '[A-Za-z0-9]{64}')->name('consensi');
        Route::post('/cancellazione', [PreferenzeController::class, 'cancellazione'])->where('token', '[A-Za-z0-9]{64}')->name('cancellazione');
    });

Route::get('/corsi/{corso:slug}/iscrizione/confermata', [CorsoIscrizioneController::class, 'confermata'])->name('corsi.iscrizione.confermata');

// Archivio notizie di una "pagina sezione" (cosa-facciamo/*): 2° segmento letterale "archivio", quindi
// va prima di /{category}/{post} (che matcherebbe qualsiasi coppia) ma non collide con essa. Un post con
// slug esattamente "archivio" verrebbe oscurato — caso accettato, improbabile.
Route::get('/{page:slug}/archivio', [PageController::class, 'archive'])->name('pages.archive.category');

// /{categoria}/{post}: la categoria in URL è solo cosmetica/SEO (posts.slug è già unico da solo a
// livello DB) — PostController::show() verifica che il post appartenga davvero a quella categoria,
// altrimenti 404, così non esistono due URL validi per lo stesso post.
Route::get('/{category:slug}/{post:slug}', [PostController::class, 'show'])->name('posts.show');

// Catch-all per le pagine statiche: va tenuta come ultima rotta, altrimenti intercetterebbe anche i path
// sopra (es. "news/altre" verrebbe letto come slug "news/altre" invece di andare a HomeController).
Route::get('/{page:slug}', [PageController::class, 'show'])->name('pages.show');
