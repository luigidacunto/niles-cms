<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Riga singola (id=1) con i dati anagrafici/di contatto del comitato (denominazione, P.IVA, C.F.,
 * codice fatturazione elettronica, telefono, email, PEC, indirizzo) + i due loghi (orizzontale/
 * verticale, mostrati alternativamente per breakpoint) + i link social (facebook/instagram/youtube/x,
 * tutti facoltativi: l'icona nel footer compare solo se il rispettivo campo è valorizzato) — usati nel
 * nav e nel footer pubblici.
 * Usare `CommitteeInfo::current()` per ottenerla (la crea vuota se manca). Editabile solo da admin,
 * vedi `Admin\CommitteeInfoController`.
 */
class CommitteeInfo extends Model
{
    protected $table = 'committee_info';

    protected $fillable = [
        'denominazione', 'piva', 'codice_fiscale', 'codice_fatturazione_elettronica',
        'telefono', 'email', 'pec', 'indirizzo', 'logo_orizzontale', 'logo_verticale',
        'logo_ifrc', 'logo_un_italia', 'favicon', 'goatcounter_enabled', 'ga_enabled', 'area_soci_attiva',
        'facebook_url', 'instagram_url', 'youtube_url', 'x_url', 'metodi_pagamento',
        'iban', 'intestatario_conto', 'banca',
    ];

    protected $casts = [
        'goatcounter_enabled' => 'boolean',
        'ga_enabled' => 'boolean',
        'area_soci_attiva' => 'boolean',
    ];

    public static function current(): self
    {
        // ⚠️ Valori passati esplicitamente (non solo il default a livello DB): firstOrCreate() non
        // ricarica il modello dopo l'insert, quindi un default di schema applicato dal DB resterebbe
        // "invisibile" (null) sull'istanza in memoria appena creata finché non viene ri-letta da zero.
        return static::firstOrCreate(['id' => 1], ['goatcounter_enabled' => true, 'ga_enabled' => true, 'area_soci_attiva' => false]);
    }

    /**
     * Metodi di pagamento attivi come ETICHETTE (valore salvato tal quale in `dati_fatturazione_corso.metodo_pagamento`):
     * unico punto di lettura per form pubblico, form admin e validazione. Si salva l'elenco dei CODICI del catalogo
     * (config/pagamenti.php). ⚠️ Mai vuota: senza configurazione valgono i metodi `default` del catalogo. Tollerante con
     * il vecchio formato (elenco di etichette libere): ogni voce che corrisponde a un metodo del catalogo (per codice
     * o per etichetta, senza distinzione di maiuscole) viene riconosciuta, le altre voci non sono più offerte (le
     * iscrizioni già registrate le conservano).
     */
    protected function metodiPagamento(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => array_values(array_map(
                fn ($codice) => config("pagamenti.metodi.{$codice}.label"),
                $this->metodiPagamentoCodici($value)
            )),
            set: fn ($value) => json_encode(array_values($value ?? [])),
        );
    }

    /** Codici dei metodi attivi (per le spunte del pannello). */
    public function metodiPagamentoCodici(?string $grezzo = null): array
    {
        $catalogo = config('pagamenti.metodi');
        $salvati = json_decode($grezzo ?? $this->attributes['metodi_pagamento'] ?? 'null', true) ?: [];

        $codici = [];
        foreach ($salvati as $voce) {
            foreach ($catalogo as $codice => $def) {
                if (mb_strtolower(trim((string) $voce)) === $codice || mb_strtolower(trim((string) $voce)) === mb_strtolower($def['label'])) {
                    $codici[] = $codice;
                }
            }
        }
        $codici = array_values(array_unique($codici));

        return $codici ?: array_keys(array_filter($catalogo, fn ($def) => $def['default']));
    }

    protected function logoOrizzontaleUrl(): Attribute
    {
        return Attribute::make(get: fn () => $this->logo_orizzontale ? Storage::url($this->logo_orizzontale) : null);
    }

    protected function logoVerticaleUrl(): Attribute
    {
        return Attribute::make(get: fn () => $this->logo_verticale ? Storage::url($this->logo_verticale) : null);
    }

    protected function logoIfrcUrl(): Attribute
    {
        return Attribute::make(get: fn () => $this->logo_ifrc ? Storage::url($this->logo_ifrc) : null);
    }

    protected function faviconUrl(): Attribute
    {
        return Attribute::make(get: fn () => $this->favicon ? Storage::url($this->favicon) : null);
    }

    protected function logoUnItaliaUrl(): Attribute
    {
        return Attribute::make(get: fn () => $this->logo_un_italia ? Storage::url($this->logo_un_italia) : null);
    }
}
