<?php

namespace App\Exports;

use App\Models\Corso;
use App\Support\CsvSicuro;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class IscrittiCorsoExport implements FromCollection, WithHeadings
{
    public function __construct(private Corso $corso, private string $stato = 'attivi') {}

    public function collection(): Collection
    {
        $q = $this->corso->iscrizioni();
        match ($this->stato) {
            'ritirati' => $q->ritirate(),
            'tutti' => null,
            'mancanti' => $q->datiNascitaMancanti(),
            default => $q->attive(),
        };

        // Testi dal pubblico: neutralizzate le "formule" (App\Support\CsvSicuro); il telefono è già ristretto a numeri e + ( ) . -.
        $s = [CsvSicuro::class, 'cella'];

        return $q->get()->map(fn ($i) => [
            'Nome' => $s($i->nome),
            'Cognome' => $s($i->cognome),
            'Email' => $s($i->email),
            'Telefono' => $i->telefono,
            'Codice fiscale' => $s($i->codice_fiscale),
            'Indirizzo' => trim("{$i->via}, {$i->cap} {$i->comune}".($i->provincia ? " ({$i->provincia})" : ''), ' ,'),
            'Presenza confermata' => $i->presenza_confermata_at?->format('d/m/Y H:i'),
            'Ritirato il' => $i->ritirata_at?->format('d/m/Y H:i'),
            'Iscritto il' => $i->created_at->format('d/m/Y H:i'),
            // Tipologie con anagrafica completa: data e luogo di nascita (per compilare gli attestati).
            ...($this->corso->richiedeAnagraficaCompleta() ? [
                'Data di nascita' => $i->data_nascita?->format('d/m/Y'),
                'Luogo di nascita' => $s((string) $i->luogoNascita()),
            ] : []),
        ]);
    }

    public function headings(): array
    {
        return [
            'Nome', 'Cognome', 'Email', 'Telefono', 'Codice fiscale', 'Indirizzo',
            'Presenza confermata', 'Ritirato il', 'Iscritto il',
            ...($this->corso->richiedeAnagraficaCompleta() ? ['Data di nascita', 'Luogo di nascita'] : []),
        ];
    }
}
