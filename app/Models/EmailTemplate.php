<?php

namespace App\Models;

use App\Support\PolicyPlaceholders;
use Illuminate\Database\Eloquent\Model;

/**
 * Template di email per tipo (config/email_templates.php): default seedato + override opzionale, come
 * PrivacyPolicy. I segnaposto `{...}` si sostituiscono al momento dell'invio, mai salvati già risolti.
 */
class EmailTemplate extends Model
{
    protected $fillable = ['tipo', 'is_default', 'oggetto', 'corpo'];

    protected $casts = ['is_default' => 'boolean'];

    public static function perTipo(string $tipo): ?self
    {
        return static::where('tipo', $tipo)->where('is_default', false)->first()
            ?? static::where('tipo', $tipo)->where('is_default', true)->first();
    }

    /** Testo di partenza «di fabbrica» (`config/email_templates.php`), indipendente dal database. */
    public static function testoPredefinito(string $tipo): ?array
    {
        return config("email_templates.{$tipo}.predefinito");
    }

    /**
     * Oggetto e corpo pronti per l'invio. $valori = ['nome' => 'Mario', ...] (senza graffe).
     * ⚠️ Nel corpo (HTML) i valori sono escapati: nomi e cognomi arrivano dal pubblico. L'oggetto è testo
     * semplice: valori ripuliti da tag e da a-capo (niente header injection).
     *
     * $grezzi = segnaposto con HTML già pronto e fidato (generato dal codice, mai dal pubblico): inseriti nel corpo
     * senza escape, ignorati nell'oggetto.
     *
     * @return array{oggetto:string, corpo:string}
     */
    public static function render(string $tipo, array $valori, array $grezzi = []): array
    {
        $template = static::perTipo($tipo)
            ?? throw new \RuntimeException("Template email '{$tipo}' non trovato: manca il testo predefinito nel database.");

        // strtr = un solo passaggio: un valore che contiene "{...}" (es. un nome inserito dal pubblico) non può
        // innescare la sostituzione di un altro segnaposto.
        // I segnaposto del tipo non forniti da chi invia (es. {dati_pagamento} senza bonifico) diventano vuoti.
        $perCorpo = $perOggetto = array_fill_keys(array_keys(config("email_templates.{$tipo}.segnaposto", [])), '');
        foreach ($valori as $chiave => $valore) {
            $perCorpo['{'.$chiave.'}'] = e((string) $valore);
            $perOggetto['{'.$chiave.'}'] = trim(preg_replace('/\s+/', ' ', strip_tags((string) $valore)));
        }
        foreach ($grezzi as $chiave => $html) {
            $perCorpo['{'.$chiave.'}'] = $html;
            $perOggetto['{'.$chiave.'}'] = '';
        }

        return [
            'oggetto' => strtr(PolicyPlaceholders::sostituisci($template->oggetto), $perOggetto),
            'corpo' => strtr(PolicyPlaceholders::sostituisci($template->corpo), $perCorpo),
        ];
    }

    /** HTML di esempio dei segnaposto "grezzi", per l'anteprima nel pannello. */
    public static function grezziEsempio(string $tipo): array
    {
        return match ($tipo) {
            'iscrizione-corso' => ['dati_pagamento' => \App\Support\DatiPagamento::bloccoEmail([
                'importo' => '35,00 €', 'intestatario' => 'Comitato di esempio', 'iban' => 'IT60 X054 2811 1010 0000 0123 456',
                'banca' => null, 'causale' => 'Corso BLSD-2026-001 - Rossi Mario',
            ])],
            'riepilogo-referente' => [
                'elenco_iscritti' => '<table cellpadding="5" style="border-collapse:collapse;border:1px solid #ccc;font-size:13px"><tr style="background:#f1f1f1;text-align:left"><th>Cognome e nome</th><th>Codice fiscale</th><th>Email</th><th>Telefono</th></tr>'
                    .'<tr><td>Rossi Mario</td><td>RSSMRA80A01H501U</td><td>mario@example.test</td><td>333 0000000</td></tr><tr><td>Verdi Anna</td><td>VRDNNA85M41F205Z</td><td>anna@example.test</td><td>333 1111111</td></tr></table>',
                'dati_fatturazione' => '<p style="margin:16px 0 4px"><strong>Dati di fatturazione inseriti (pagamento unico)</strong></p><p style="margin:0">ACME srl — P.IVA 12345678901 — Metodo di pagamento: Bonifico</p>',
                'dati_pagamento' => \App\Support\DatiPagamento::bloccoEmail([
                    'importo' => '70,00 €', 'dettaglio' => '35,00 € × 2 persone', 'intestatario' => 'Comitato di esempio',
                    'iban' => 'IT60 X054 2811 1010 0000 0123 456', 'banca' => null, 'causale' => 'Corso BLSD-2026-001 - ACME srl',
                ]),
            ],
            default => [],
        };
    }

    /** Valori di esempio per l'anteprima nel pannello. */
    public static function valoriEsempio(string $tipo): array
    {
        return match ($tipo) {
            'iscrizione-corso' => [
                'nome' => 'Mario', 'cognome' => 'Rossi', 'corso' => 'Rianimazione cardiopolmonare e defibrillazione',
                'protocollo' => 'BLSD-2026-001', 'periodo' => '10/10/2026, 09:00–13:00', 'luogo' => 'Sede del Comitato',
                'costo' => '35,00 €', 'link_preferenze' => url('/preferenze/ESEMPIO'),
            ],
            'riepilogo-referente' => [
                'nome' => 'Hilda', 'corso' => 'Rianimazione cardiopolmonare e defibrillazione', 'protocollo' => 'BLSD-2026-001',
                'periodo' => '10/10/2026, 09:00–13:00', 'luogo' => 'Sede del Comitato', 'costo' => '35,00 €', 'numero_iscritti' => '2',
            ],
            default => [],
        };
    }
}
