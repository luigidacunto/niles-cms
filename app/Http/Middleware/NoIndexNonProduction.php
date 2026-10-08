<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fuori dall'ambiente `production` (staging, local, qualsiasi valore ≠ production) aggiunge
 * `X-Robots-Tag: noindex` a ogni risposta, così un'anteprima pubblica non finisce nei motori di
 * ricerca. Vale anche per file non-HTML (PDF, immagini). Si accompagna al meta `robots` nel layout
 * pubblico e al `robots.txt` dinamico (routes/web.php).
 */
class NoIndexNonProduction
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! app()->isProduction()) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        }

        return $response;
    }
}
