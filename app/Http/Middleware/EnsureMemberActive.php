<?php

namespace App\Http\Middleware;

use App\Support\AreaSoci;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Un socio disabilitato mentre è già loggato perde subito la sessione (altrimenti il flag varrebbe solo
 * al login successivo). Rimosso (soft-delete) è già escluso dal provider. Costo: zero per chi non è loggato.
 */
class EnsureMemberActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('member');

        if ($guard->check() && ($guard->user()->disabilitato || ! AreaSoci::attiva())) {
            $guard->logout();
        }

        return $next($request);
    }
}
