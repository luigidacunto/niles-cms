<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Corso extends Model
{
    protected $table = 'corsi';

    protected $fillable = [
        'tipologia_corso_id', 'sede_corso_id', 'usa_indirizzo_comitato', 'admin_id', 'slug',
        'protocollo', 'data_inizio', 'data_fine', 'costo', 'iva_prevista', 'aliquota_iva', 'posti_max', 'iscrizioni_chiusura_at',
        'descrizione', 'pubblicato', 'annullato_at', 'motivo_annullamento', 'annullato_da', 'chiuso',
    ];

    protected function casts(): array
    {
        return [
            'data_inizio' => 'datetime',
            'data_fine' => 'datetime',
            'costo' => 'decimal:2',
            'iva_prevista' => 'boolean',
            'aliquota_iva' => 'decimal:2',
            'usa_indirizzo_comitato' => 'boolean',
            'iscrizioni_chiusura_at' => 'datetime',
            'pubblicato' => 'boolean',
            'annullato_at' => 'datetime',
            'chiuso' => 'boolean',
        ];
    }

    public function tipologia(): BelongsTo
    {
        return $this->belongsTo(TipologiaCorso::class, 'tipologia_corso_id');
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(SedeCorso::class, 'sede_corso_id');
    }

    public function creatoDa(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function annullatoDa(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'annullato_da');
    }

    public function iscrizioni(): HasMany
    {
        return $this->hasMany(IscrizioneCorso::class);
    }

    /**
     * Motivo per cui il corso non si può eliminare, null se si può: serve nessun iscritto attivo e nessuna fattura,
     * ricevuta o incasso registrati sui blocchi di fatturazione. Le iscrizioni ritirate non bloccano (vedi eliminaConRitirate()).
     */
    public function motivoNonEliminabile(): ?string
    {
        $attive = $this->iscrizioni()->attive()->count();
        if ($attive > 0) {
            return "Non puoi eliminare questo corso: ha {$attive} iscritti attivi.";
        }

        $conPagamenti = DatiFatturazioneCorso::whereIn('id', $this->iscrizioni()->select('dati_fatturazione_corso_id'))
            ->where(fn ($q) => $q->whereNotNull('fattura_numero')->orWhereNotNull('ricevuta_numero')->orWhereNotNull('incassato_at'))
            ->exists();

        return $conPagamenti ? 'Non puoi eliminare questo corso: ci sono fatture, ricevute o incassi registrati.' : null;
    }

    /**
     * Elimina il corso insieme alle sue iscrizioni ritirate e ai blocchi di fatturazione che restano orfani.
     * Chiamare solo se motivoNonEliminabile() è null. Ritorna il numero di iscrizioni ritirate cancellate.
     */
    public function eliminaConRitirate(): int
    {
        return DB::transaction(function () {
            $blocchi = $this->iscrizioni()->pluck('dati_fatturazione_corso_id')->filter()->unique();
            $n = $this->iscrizioni()->delete();
            DatiFatturazioneCorso::whereIn('id', $blocchi)->whereDoesntHave('iscrizioni')->delete();
            $this->delete();

            return $n;
        });
    }

    /**
     * Blocchi di fatturazione del corso per la gestione pagamenti: ognuno con gli iscritti ancora attivi e il totale dovuto
     * (quota × iscritti attivi, in centesimi). Un blocco con tutti gli iscritti ritirati sparisce, salvo abbia già
     * una fattura o un incasso registrati (da non perdere di vista).
     *
     * @return Collection<int, array{blocco: DatiFatturazioneCorso, attivi: Collection, totale_cent: int}>
     */
    public function blocchiPagamento(): Collection
    {
        $blocchi = DatiFatturazioneCorso::whereHas('iscrizioni', fn ($q) => $q->where('corso_id', $this->id))
            ->with(['registratoDa', 'iscrizioni' => fn ($q) => $q->where('corso_id', $this->id)->orderBy('cognome')->orderBy('nome')])
            ->get();

        return $blocchi
            ->map(function (DatiFatturazioneCorso $b) {
                $attivi = $b->iscrizioni->whereNull('ritirata_at')->values();

                return ['blocco' => $b, 'attivi' => $attivi, 'totale_cent' => (int) round($this->costo * 100) * $attivi->count()];
            })
            ->filter(fn ($r) => $r['attivi']->isNotEmpty() || $r['blocco']->fatturaEmessa() || $r['blocco']->incassato_at)
            ->sortBy(fn ($r) => mb_strtolower($r['blocco']->intestatario()))
            ->values();
    }

    /**
     * Conteggi dei blocchi di fatturazione del corso: totali, senza fattura, da incassare (anche in parte), incassati più del dovuto.
     * Usati dall'elenco dei pagamenti e dalla dashboard.
     *
     * @return array{blocchi: int, da_fatturare: int, da_incassare: int, eccesso: int}
     */
    public function riepilogoPagamenti(): array
    {
        $blocchi = $this->blocchiPagamento();

        return [
            'blocchi' => $blocchi->count(),
            'da_fatturare' => $blocchi->filter(fn ($r) => ! $r['blocco']->fatturaEmessa())->count(),
            'da_incassare' => $blocchi->filter(fn ($r) => $r['blocco']->daIncassare($r['totale_cent']))->count(),
            'eccesso' => $blocchi->filter(fn ($r) => $r['blocco']->statoIncasso($r['totale_cent']) === 'eccesso')->count(),
        ];
    }

    /** La tipologia richiede data e luogo di nascita (anagrafica completa, per l'attestato). */
    public function richiedeAnagraficaCompleta(): bool
    {
        return (bool) $this->tipologia->anagrafica_completa;
    }

    /** Fatture già registrate per questo corso (blocchi con numero di fattura): servono per avvisare se si cambia l'IVA. */
    public function fattureRegistrate(): int
    {
        return DatiFatturazioneCorso::whereNotNull('fattura_numero')
            ->whereHas('iscrizioni', fn ($q) => $q->where('corso_id', $this->id))
            ->count();
    }

    /** Etichetta della sede da mostrare: indirizzo del comitato, una sede del catalogo, o niente. */
    public function sedeLabel(): ?string
    {
        if ($this->usa_indirizzo_comitato) {
            return CommitteeInfo::current()->indirizzo ?: 'Sede del comitato';
        }

        return $this->sede?->nome;
    }

    /** Inizio come istante reale: l'orario salvato è ora locale del comitato (l'app gira in UTC). */
    public function inizioLocale(): Carbon
    {
        return Carbon::parse($this->data_inizio->format('Y-m-d H:i:s'), config('app.committee_timezone'));
    }

    public function fineLocale(): Carbon
    {
        return Carbon::parse($this->data_fine->format('Y-m-d H:i:s'), config('app.committee_timezone'));
    }

    /** "26/09/2026, 09:00–13:00" se stesso giorno, altrimenti "26/09/2026 09:00 — 27/09/2026 13:00". */
    public function periodoLabel(): string
    {
        if ($this->data_inizio->isSameDay($this->data_fine)) {
            return $this->data_inizio->format('d/m/Y, H:i').'–'.$this->data_fine->format('H:i');
        }

        return $this->data_inizio->format('d/m/Y H:i').' — '.$this->data_fine->format('d/m/Y H:i');
    }

    public function annullato(): bool
    {
        return $this->annullato_at !== null;
    }

    /** L'annullamento si può disfare solo finché il corso non si è ancora svolto. */
    public function puoRiattivare(): bool
    {
        return $this->annullato() && ! $this->data_fine->isPast();
    }

    /**
     * Stato mostrato nella pagina pubblica: 'annullato' (con motivo) > 'concluso' (la data del corso è
     * passata) > 'iscrizioni_chiuse' (superata iscrizioni_chiusura_at ma il corso non si è ancora
     * svolto) > 'aperto'. Non considera `chiuso`: quel flag è solo per il filtro interno dell'elenco
     * admin, non ha alcun effetto sul form pubblico.
     */
    public function statoPubblico(): string
    {
        if ($this->annullato()) {
            return 'annullato';
        }

        if ($this->data_fine->isPast()) {
            return 'concluso';
        }

        if ($this->iscrizioni_chiusura_at && $this->iscrizioni_chiusura_at->isPast()) {
            return 'iscrizioni_chiuse';
        }

        return 'aperto';
    }

    public function iscrizioniAperte(): bool
    {
        return $this->pubblicato && $this->statoPubblico() === 'aperto';
    }

    /**
     * Corsi con iscrizioni aperte, per l'elenco pubblico: stessa condizione di iscrizioniAperte()
     * (⚠️ da tenere allineate), espressa in SQL. Ordinati per data di inizio.
     */
    public function scopeConIscrizioniAperte(Builder $query): Builder
    {
        return $query->where('pubblicato', true)
            ->whereNull('annullato_at')
            ->where('data_fine', '>', now())
            ->where(fn (Builder $q) => $q->whereNull('iscrizioni_chiusura_at')->orWhere('iscrizioni_chiusura_at', '>', now()))
            ->orderBy('data_inizio');
    }

    /** Immagine del corso = quella della sua tipologia (nessun override per singolo corso); null se assente. */
    public function immagineUrl(): ?string
    {
        return $this->tipologia->immagine_url;
    }

    /**
     * SIGLA-ANNO-NNN, progressivo per tipologia+anno (es. BLSD-2026-012). Assegnato una sola volta,
     * alla creazione del corso — mai ricalcolato in update anche se la tipologia cambiasse.
     */
    public static function generaProtocollo(TipologiaCorso $tipologia): string
    {
        $anno = now()->year;

        $ultimo = static::where('tipologia_corso_id', $tipologia->id)
            ->whereYear('created_at', $anno)
            ->count();

        $progressivo = str_pad((string) ($ultimo + 1), 3, '0', STR_PAD_LEFT);

        return "{$tipologia->sigla}-{$anno}-{$progressivo}";
    }
}
