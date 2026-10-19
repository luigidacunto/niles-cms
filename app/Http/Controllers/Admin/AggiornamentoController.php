<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\DbDump;
use App\Support\UpdateCheck;
use App\Support\WebUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Pagina «Aggiornamento» (solo amministratori, solo se `NILES_WEB_UPDATE=true`, mai in demo). La pagina guida la fase A
 * (download, estrazione, copia del database) con chiamate a passi brevi a `passo()` e poi porta il browser al programma di
 * aggiornamento (fase B): vedi App\Support\WebUpdate e scripts/niles-installer.php.
 */
class AggiornamentoController extends Controller
{
    private function attivo(): void
    {
        abort_unless(WebUpdate::attivo(), 404);
    }

    public function index(): \Illuminate\Contracts\View\View
    {
        $this->attivo();

        $stato = UpdateCheck::status();

        return view('admin.aggiornamento.index', [
            'versione' => config('app.version'),
            'stato' => $stato,
            'disponibile' => WebUpdate::versioneDisponibile(),
            'requisiti' => WebUpdate::requisiti(),
            'copie' => DbDump::elenco(),
        ]);
    }

    /** Forza un nuovo controllo della versione disponibile (altrimenti l'esito resta in cache 24 ore). */
    public function controlla(): \Illuminate\Http\RedirectResponse
    {
        $this->attivo();
        UpdateCheck::dimentica();

        return redirect()->route('admin.aggiornamento.index')->with('status', 'Controllo eseguito.');
    }

    /** Un passo della preparazione: scarica | estrai | dump-inizia | dump | applica. Risponde sempre in JSON. */
    public function passo(Request $request): JsonResponse
    {
        $this->attivo();
        @set_time_limit(0);

        try {
            return response()->json(['ok' => true] + match ($request->input('fase')) {
                'scarica' => (function () {
                    WebUpdate::scarica();

                    return [];
                })(),
                'estrai' => WebUpdate::estrai(),
                'dump-inizia' => ['tabelle' => count(DbDump::inizia()['tabelle'])],
                'dump' => (function () {
                    $s = DbDump::passo();
                    $s['fatto'] && WebUpdate::segnaBackup($s['file']);

                    return ['fatto' => $s['fatto'], 't' => $s['t'], 'n' => count($s['tabelle']), 'tabella' => $s['tabelle'][min($s['t'], count($s['tabelle']) - 1)] ?? ''];
                })(),
                'applica' => ['url' => WebUpdate::applica()],
                default => throw new \InvalidArgumentException('Passo non valido.'),
            });
        } catch (Throwable $e) {
            report($e);

            return response()->json(['ok' => false, 'err' => $e->getMessage()], 422);
        }
    }

    /** Scarica una copia del database (solo amministratori). */
    public function copia(string $nome): BinaryFileResponse|Response
    {
        $this->attivo();
        $percorso = DbDump::percorso($nome);
        abort_if($percorso === null, 404);

        return response()->download($percorso, $nome, ['Content-Type' => 'application/sql']);
    }
}
