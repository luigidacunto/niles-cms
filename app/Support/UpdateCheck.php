<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Confronta la versione installata con l'ultima release pubblicata sul repository (API GitHub `releases/latest`).
 *
 * Mai bloccante: timeout breve, qualunque errore (rete, repository inesistente, nessuna release, risposta
 * inattesa) dà lo stato `non_disponibile` (la dashboard mostra una pillola neutra). Con il controllo
 * disattivato restituisce `null`. L'esito, anche negativo, resta in cache 24 ore: nel frattempo non si
 * interroga GitHub, a prescindere dal risultato (limite di 60 richieste/ora per IP senza credenziali).
 */
class UpdateCheck
{
    private const CACHE_KEY = 'niles.update_check.latest';

    /** Intervallo minimo tra due interrogazioni di GitHub. */
    private const CACHE_HOURS = 24;

    /** @return array{stato: 'aggiornato', versione: string, url: string}|array{stato: 'disponibile', livello: 'major'|'minor'|'patch', versione: string, url: string}|array{stato: 'non_disponibile'}|null */
    public static function status(): ?array
    {
        if (! config('app.update_check.enabled')) {
            return null;
        }

        $latest = self::latest();
        if ($latest === null) {
            return ['stato' => 'non_disponibile'];
        }

        $installata = (string) config('app.version');
        if (! version_compare($latest['versione'], $installata, '>')) {
            return ['stato' => 'aggiornato'] + $latest;
        }

        return ['stato' => 'disponibile', 'livello' => self::livello($installata, $latest['versione'])] + $latest;
    }

    /** Quanto è distante la versione disponibile: `major`, `minor` o `patch` (in base alla prima parte che cambia). */
    private static function livello(string $installata, string $disponibile): string
    {
        [$a, $b] = [array_map('intval', explode('.', $installata)), array_map('intval', explode('.', $disponibile))];

        return ($b[0] ?? 0) > ($a[0] ?? 0) ? 'major' : (($b[1] ?? 0) > ($a[1] ?? 0) ? 'minor' : 'patch');
    }

    /** @return array{versione: string, url: string}|null */
    private static function latest(): ?array
    {
        $cached = Cache::get(self::CACHE_KEY);
        if ($cached !== null) {
            return $cached ?: null;
        }

        $latest = self::fetch();
        Cache::put(self::CACHE_KEY, $latest ?? false, now()->addHours(self::CACHE_HOURS));

        return $latest;
    }

    private static function fetch(): ?array
    {
        try {
            $response = Http::connectTimeout(2)->timeout(2)
                ->withHeaders(['Accept' => 'application/vnd.github+json', 'User-Agent' => 'NILES-update-check'])
                ->get('https://api.github.com/repos/'.config('app.update_check.repository').'/releases/latest');

            if (! $response->successful()) {
                return null;
            }

            $versione = ltrim((string) $response->json('tag_name'), 'vV');
            $url = (string) $response->json('html_url');

            if (! preg_match('/^\d+\.\d+\.\d+/', $versione) || ! str_starts_with($url, 'https://github.com/')) {
                return null;
            }

            return ['versione' => $versione, 'url' => $url];
        } catch (Throwable) {
            return null;
        }
    }
}
