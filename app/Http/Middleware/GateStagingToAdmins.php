<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Se `config('staging.gate_public')` è attivo, il sito pubblico è invisibile a chiunque non sia già
 * loggato su `/admin` nello stesso browser: qualunque altro URL mostra una pagina di cortesia generica
 * invece del contenuto reale (nessuna eccezione per pagina/rotta). Le rotte `/admin/*` restano sempre
 * raggiungibili, altrimenti nessuno potrebbe più loggarsi per sbloccare il resto.
 *
 * ⚠️ Mai attivo in production anche se il flag fosse lasciato per errore in un .env di produzione —
 * doppia sicurezza, questo meccanismo è pensato solo per lo staging dedicato.
 */
class GateStagingToAdmins
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('staging.gate_public') || app()->isProduction()) {
            return $next($request);
        }

        if ($request->is('admin') || $request->is('admin/*')) {
            return $next($request);
        }

        if (auth('admin')->check()) {
            return $next($request);
        }

        return response()->view('staging-landing');
    }
}
