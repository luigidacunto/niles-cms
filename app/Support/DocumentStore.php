<?php

namespace App\Support;

use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Punto unico per salvare/sostituire il file di un Document. Usato dalla sezione
 * Trasparenza, dalla libreria documenti e dai costrutti (es. organigramma della
 * Struttura Organizzativa). Il nome su disco è leggibile (vedi Filenames), non ULID.
 */
class DocumentStore
{
    /** Cartella unica per tutti i documenti (disco `public`; `local` per i riservati ai soci, vedi Document::disk()). */
    public const DIR = 'documenti';

    public static function store(UploadedFile $file, Document $document): void
    {
        if ($document->file_path) {
            Storage::disk($document->disk())->delete($document->file_path);
        }

        $ext = $file->getClientOriginalExtension() ?: $file->extension();
        $name = Filenames::readable($document->title ?: 'documento', $ext);

        $document->file_path = $file->storeAs(self::DIR, $name, $document->disk());
        $document->original_filename = $file->getClientOriginalName();
        $document->file_size = $file->getSize();
    }
}
