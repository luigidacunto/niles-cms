<?php

use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Anteprima pubblica non-production → X-Robots-Tag: noindex su ogni risposta.
        $middleware->append(\App\Http\Middleware\NoIndexNonProduction::class);

        // 301 verso i nuovi path per gli URL del vecchio sito — no-op se legacy-redirects.php non esiste.
        $middleware->prepend(\App\Http\Middleware\RedirectLegacyUrls::class);

        // Staging con dati condivisi con la produzione: sito pubblico invisibile a chi non è già
        // loggato su /admin — no-op salvo STAGING_GATE_PUBLIC=true. Deve girare DOPO l'avvio sessione
        // (per leggere lo stato di login) ma PRIMA di SubstituteBindings, altrimenti un binding di
        // rotta non risolto (es. slug inesistente) darebbe 404 prima ancora che questo controllo scatti
        // — quindi tolto dalla sua posizione di default nel gruppo 'web' e riaggiunto subito dopo.
        $middleware->removeFromGroup('web', \Illuminate\Routing\Middleware\SubstituteBindings::class);
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\GateStagingToAdmins::class,
            \App\Http\Middleware\EnsureMemberActive::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            // Demo: dopo i binding così i 404 su risorse inesistenti restano 404. No-op salvo NILES_DEMO=true.
            \App\Http\Middleware\BlockWritesInDemo::class,
        ]);

        // Due login: /soci/* (area soci) e tutto il resto (admin).
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('soci', 'soci/*') ? route('soci.login') : route('admin.login'));

        // Il redirect di default per un utente già autenticato che ripiomba su /admin/login cerca una
        // route 'dashboard' o 'home' (RedirectIfAuthenticated::defaultRedirectUri) — la nostra si chiama
        // 'admin.dashboard' (prefisso 'admin.'), quindi cadrebbe sulla home pubblica invece che sul
        // pannello. Da rivedere se in futuro nasce un login pubblico/soci con una sua destinazione.
        RedirectIfAuthenticated::redirectUsing(fn (Request $request) => $request->is('soci', 'soci/*') ? route('home') : route('admin.dashboard'));

        $middleware->alias([
            'admin.role' => \App\Http\Middleware\EnsureAdminRole::class,
            'area.soci' => \App\Http\Middleware\EnsureAreaSociAttiva::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
