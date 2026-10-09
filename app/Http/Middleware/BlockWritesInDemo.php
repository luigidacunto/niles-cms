<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Con `config('app.demo')` attivo (NILES_DEMO=true) il sito si può provare ma non si salva nulla: ogni
 * richiesta di scrittura (POST/PUT/PATCH/DELETE) viene rifiutata con un avviso e si torna alla pagina
 * precedente conservando quanto digitato. Vale per il pannello e per i moduli pubblici (iscrizioni, preferenze
 * privacy, area soci), così sulla demo non possono comparire contenuti né dati personali.
 *
 * Il blocco è «tutto vietato salvo l'elenco» (ALLOWED): una rotta nuova resta bloccata finché non viene
 * ammessa di proposito. Il login è ammesso (con codice via email o con password), come l'uscita e il tema scuro.
 *
 * ⚠️ Gira prima dei controlli dei controller: un editor che tenta una scrittura vietata dal suo ruolo vede
 * l'avviso della demo invece del 403.
 */
class BlockWritesInDemo
{
    private const ALLOWED = [
        'admin.login.*',
        'admin.logout',
        'adminlte.darkmode.toggle',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.demo') || $request->isMethodSafe() || $request->routeIs(...self::ALLOWED)) {
            return $next($request);
        }

        $messaggio = 'Versione dimostrativa: le modifiche non vengono salvate.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $messaggio], 403);
        }

        $indietro = back()->withInput();

        return $request->is('admin', 'admin/*')
            ? $indietro->with('status', $messaggio)->with('demo_bloccato', true) // i moduli non mostrano 'status': la barra in alto sì
            : $indietro->withErrors(['demo' => $messaggio]);
    }
}
