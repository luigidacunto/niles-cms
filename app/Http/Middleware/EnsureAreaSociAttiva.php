<?php

namespace App\Http\Middleware;

use App\Support\AreaSoci;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Area soci spenta (Dati comitato) → le sue pagine, pubbliche e di pannello, non esistono (404). */
class EnsureAreaSociAttiva
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(AreaSoci::attiva(), 404);

        return $next($request);
    }
}
