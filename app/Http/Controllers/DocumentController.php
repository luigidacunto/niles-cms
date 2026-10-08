<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Support\AreaSoci;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Serve un documento (Trasparenza o libreria) con un nome leggibile dal titolo,
     * non il nome file su disco. `inline` per i formati che il browser mostra
     * (pdf, immagini), `attachment` per gli altri (Office). I documenti non
     * pubblicati sono raggiungibili solo da un admin autenticato (anteprima bozze).
     */
    public function show(Request $request, Document $document)
    {
        abort_unless($document->published || $request->user('admin'), 404);

        // Allegati riservati ai soci: solo socio loggato (area attiva) o admin; chi non è loggato va al login.
        if ($document->isRiservato() && ! $request->user('admin')) {
            abort_unless(AreaSoci::attiva(), 404);

            if (! $request->user('member')) {
                return redirect()->guest(route('soci.login'));
            }
        }

        $disk = $document->disk();
        abort_unless($document->file_path && Storage::disk($disk)->exists($document->file_path), 404);

        $disposition = $document->servedInline() ? 'inline' : 'attachment';

        return Storage::disk($disk)->response($document->file_path, $document->download_name, [], $disposition);
    }
}
