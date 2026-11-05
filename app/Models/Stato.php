<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Stati esteri, riutilizzabili ovunque serva uno Stato (oggi: luogo di nascita dei corsi con anagrafica completa).
 * I valori iniziali vengono da config/stati.php, poi la tabella è la fonte (si aggiorna senza toccare il codice; gestione
 * da pannello prevista in seguito). `attivo` = offerto nei moduli; un Stato disattivato resta nei dati già salvati.
 * ⚠️ I dati salvati (es. `iscrizioni_corso.nascita_stato`) conservano il NOME come testo, non l'id: rinominare o disattivare
 * uno Stato non cambia ciò che è già stato raccolto.
 */
class Stato extends Model
{
    protected $table = 'stati';

    protected $fillable = ['nome', 'codice_iso', 'attivo'];

    protected function casts(): array
    {
        return ['attivo' => 'boolean'];
    }

    public function scopeAttivi(Builder $query): void
    {
        $query->where('attivo', true)->orderBy('nome');
    }

    /** Stati offerti nei moduli, in ordine alfabetico. */
    public static function elencoAttivi(): Collection
    {
        return static::attivi()->get();
    }

    /**
     * Inserisce gli Stati di config/stati.php che mancano (per codice ISO). Solo creazione: non riattiva né rinomina quelli
     * già presenti, quindi non sovrascrive le modifiche fatte dall'installazione.
     */
    public static function popolaPredefiniti(): void
    {
        foreach (config('stati') as $iso => $nome) {
            static::firstOrCreate(['codice_iso' => $iso], ['nome' => $nome, 'attivo' => true]);
        }
    }
}
