<?php

namespace App\Support;

use Illuminate\Support\Str;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;

/**
 * Guida del pannello: legge i file Markdown di `docs/` (gli stessi pubblicati sul repository) e li mostra nel pannello.
 *
 * Struttura attesa: un livello di cartelle numerate (`00-installazione`, `01-primi-passi`, …) con dentro i documenti numerati
 * (`01-post.md`, …); `docs/README.md` è la pagina iniziale. Il titolo di un documento è la sua prima riga `# …`, quello di una
 * cartella si ricava dal nome. I collegamenti relativi vengono riscritti: un altro documento della guida si apre nel pannello,
 * un'immagine di `docs/immagini/` passa dalla rotta delle immagini, tutto il resto (script, README del progetto) punta al repository
 * della versione installata.
 */
class Guida
{
    /** Percorso di un documento: cartella/file, senza estensione. */
    public const PERCORSO = '\d\d-[a-z0-9-]+/\d\d-[a-z0-9-]+';

    public const IMMAGINE = '[a-z0-9_\/-]+\.(png|jpe?g|webp|gif)';

    public static function radice(): string
    {
        return base_path('docs');
    }

    /** Cartelle e documenti, nell'ordine dei numeri: [['titolo' => …, 'pagine' => [['percorso' => …, 'titolo' => …]]]]. */
    public static function indice(): array
    {
        $cartelle = [];
        foreach (glob(self::radice().'/[0-9][0-9]-*', GLOB_ONLYDIR) ?: [] as $dir) {
            $pagine = [];
            foreach (glob($dir.'/[0-9][0-9]-*.md') ?: [] as $file) {
                $percorso = basename($dir).'/'.basename($file, '.md');
                $pagine[] = ['percorso' => $percorso, 'titolo' => self::titolo(file_get_contents($file), basename($file, '.md'))];
            }
            if ($pagine) {
                $cartelle[] = ['titolo' => self::daNome(basename($dir)), 'pagine' => $pagine];
            }
        }

        return $cartelle;
    }

    /**
     * Documento pronto da mostrare: ['titolo' => …, 'html' => …], null se non esiste. `null` come percorso = `docs/README.md`.
     * Con $perPdf le immagini puntano ai file sul disco (dompdf non scarica da URL).
     */
    public static function pagina(?string $percorso, bool $perPdf = false): ?array
    {
        if ($percorso !== null && ! preg_match('#^'.self::PERCORSO.'$#', $percorso)) {
            return null;
        }
        $file = self::radice().'/'.($percorso ?? 'README').'.md';
        if (! is_file($file)) {
            return null;
        }

        $markdown = file_get_contents($file);
        $cartella = $percorso === null ? '' : dirname($percorso);
        $markdown = preg_replace_callback('/(\]\()([^)\s]+)(\))/', fn ($m) => $m[1].self::link($m[2], $cartella, $perPdf).$m[3], $markdown);

        $html = Str::markdown($markdown, [
            // Ancore dei titoli come su GitHub (#posta-in-uscita), senza simbolo visibile.
            'heading_permalink' => ['id_prefix' => '', 'fragment_prefix' => '', 'symbol' => '', 'insert' => 'before', 'apply_id_to_heading' => true],
        ], [new HeadingPermalinkExtension]);

        return [
            'titolo' => self::titolo($markdown, $percorso ?? 'Guida'),
            'html' => str_replace('<table>', '<table class="table table-sm table-bordered">', $html),
        ];
    }

    /** Riscrive un collegamento relativo del documento (vedi docblock della classe); assoluti e ancore restano come sono. */
    private static function link(string $destinazione, string $cartella, bool $perPdf): string
    {
        if (preg_match('#^([a-z]+:|\#|/)#i', $destinazione)) {
            return $destinazione;
        }
        [$percorso, $ancora] = array_pad(explode('#', $destinazione, 2), 2, null);
        $ancora = $ancora !== null ? '#'.$ancora : '';

        // Percorso relativo a docs/ (può uscire dalla cartella: ../README.md, ../scripts/…).
        $parti = [];
        foreach (explode('/', ($cartella !== '' ? $cartella.'/' : '').$percorso) as $parte) {
            if ($parte === '..' && $parti && end($parti) !== '..') {
                array_pop($parti);
            } elseif ($parte === '..') {
                $parti[] = '..';
            } elseif ($parte !== '.' && $parte !== '') {
                $parti[] = $parte;
            }
        }
        $relativo = implode('/', $parti);

        if (preg_match('#^('.self::PERCORSO.')\.md$#', $relativo, $m)) {
            return route('admin.guida.show', $m[1]).$ancora;
        }
        if ($relativo === 'README.md') {
            return route('admin.guida.index').$ancora;
        }
        if (preg_match('#^immagini/('.self::IMMAGINE.')$#', $relativo, $m)) {
            return $perPdf ? str_replace('\\', '/', self::radice()).'/immagini/'.$m[1] : route('admin.guida.immagine', $m[1]);
        }

        // Fuori dalla guida: il file sul repository, alla versione installata.
        $daRadice = str_starts_with($relativo, '../') ? substr($relativo, 3) : 'docs/'.$relativo;

        return 'https://github.com/'.config('app.update_check.repository').'/blob/v'.config('app.version').'/'.$daRadice.$ancora;
    }

    private static function titolo(string $markdown, string $riserva): string
    {
        return preg_match('/^# (.+)$/m', $markdown, $m) ? trim($m[1]) : $riserva;
    }

    /** `05-comitato-e-sito` → «Comitato e sito». */
    private static function daNome(string $nome): string
    {
        return Str::ucfirst(str_replace('-', ' ', preg_replace('/^\d\d-/', '', $nome)));
    }
}
