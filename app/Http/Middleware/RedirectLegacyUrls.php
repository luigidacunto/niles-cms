<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 301 verso i nuovi path per gli URL del vecchio sito del comitato, sostituto di un redirect
 * lato Apache: stesso dominio, la richiesta passa comunque per Laravel. Dati per-installazione
 * in `legacy-redirects.php` (non versionato, vedi `legacy-redirects.example.php` e
 * `config/legacy_redirects.php`) — se il file non esiste il middleware è un no-op immediato.
 */
class RedirectLegacyUrls
{
    public function handle(Request $request, Closure $next): Response
    {
        $config = config('legacy_redirects');
        $path = '/'.trim($request->path(), '/');

        if (isset($config['exact'][$path])) {
            return redirect($config['exact'][$path], 301);
        }

        if (($config['post_pattern'] ?? false)
            && preg_match('~^/post/([a-z0-9-]+)/([a-z0-9-]+)$~', $path, $m)) {
            $category = $config['category_map'][$m[1]] ?? $m[1];

            return redirect("/{$category}/{$m[2]}", 301);
        }

        return $next($request);
    }
}
