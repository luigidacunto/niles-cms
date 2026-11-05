<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;

class DatiFatturazioneCorso extends Model
{
    protected $table = 'dati_fatturazione_corso';

    protected $fillable = [
        'tipo', 'nome', 'cognome', 'ragione_sociale', 'partita_iva', 'codice_fiscale',
        'via', 'comune', 'provincia', 'cap', 'codice_destinatario', 'pec', 'email', 'telefono', 'metodo_pagamento',
        'fattura_numero', 'fattura_data', 'ricevuta_numero', 'ricevuta_data',
        'incassato_importo', 'incassato_at', 'incasso_nota', 'pagamento_registrato_da', 'pagamento_registrato_at',
    ];

    protected function casts(): array
    {
        return [
            'fattura_data' => 'date',
            'ricevuta_data' => 'date',
            'incassato_importo' => 'decimal:2',
            'incassato_at' => 'datetime',
            'pagamento_registrato_at' => 'datetime',
        ];
    }

    public function registratoDa(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'pagamento_registrato_da');
    }

    /** Fattura emessa = numero registrato (la data è sempre insieme al numero). */
    public function fatturaEmessa(): bool
    {
        return filled($this->fattura_numero);
    }

    public function ricevutaEmessa(): bool
    {
        return filled($this->ricevuta_numero);
    }

    /** Incasso registrato: nessuno / parziale (importo sotto il totale dovuto) / completo. $totaleCent = totale dovuto in centesimi. */
    public function statoIncasso(int $totaleCent): string
    {
        if ($this->incassato_at === null) {
            return 'da_incassare';
        }

        return (int) round($this->incassato_importo * 100) >= $totaleCent ? 'incassata' : 'parziale';
    }

    public function iscrizioni(): HasMany
    {
        return $this->hasMany(IscrizioneCorso::class);
    }

    /** Intestatario leggibile (ragione sociale per le aziende, nome e cognome per i privati). */
    public function intestatario(): string
    {
        return $this->tipo === 'azienda' ? (string) $this->ragione_sociale : trim("{$this->nome} {$this->cognome}");
    }

    /**
     * Blocco 'fatturazione' derivato dal nominativo, per la spunta "fatturazione a me, stessi dati"
     * (form pubblico "solo io" + form admin): sempre privato, anagrafica e residenza copiate dal
     * nominativo. Va poi passato a datiDaInput() come un normale blocco compilato a mano.
     */
    public static function inputDaNominativo(array $nominativo, ?string $metodoPagamento): array
    {
        return ['tipo' => 'privato']
            + Arr::only($nominativo, ['nome', 'cognome', 'codice_fiscale', 'via', 'comune', 'provincia', 'cap'])
            + ['metodo_pagamento' => $metodoPagamento];
    }

    /**
     * Mappa il blocco 'fatturazione' di un form (pubblico o admin) + i contatti (dal richiedente o dal
     * nominativo stesso) nell'array pronto per create(). Il blocco 'fatturazione' non porta mai email/
     * telefono propri — arrivano sempre da $contatto. Condivisa tra CorsoIscrizioneController (pubblico)
     * e Admin\CorsoIscrizioneController (inserimento manuale) per non duplicare la mappatura.
     */
    public static function datiDaInput(array $f, array $contatto): array
    {
        return [
            'tipo' => $f['tipo'],
            'nome' => $f['nome'] ?? null,
            'cognome' => $f['cognome'] ?? null,
            'ragione_sociale' => $f['ragione_sociale'] ?? null,
            'partita_iva' => $f['partita_iva'] ?? null,
            'codice_fiscale' => isset($f['codice_fiscale']) ? strtoupper($f['codice_fiscale']) : null,
            'via' => $f['via'] ?? null,
            'comune' => $f['comune'] ?? null,
            'provincia' => $f['provincia'] ?? null,
            'cap' => $f['cap'] ?? null,
            'codice_destinatario' => isset($f['codice_destinatario']) ? strtoupper($f['codice_destinatario']) : null,
            'pec' => $f['pec'] ?? null,
            'email' => $contatto['email'] ?? null,
            'telefono' => $contatto['telefono'] ?? null,
            'metodo_pagamento' => $f['metodo_pagamento'] ?? null,
        ];
    }
}
