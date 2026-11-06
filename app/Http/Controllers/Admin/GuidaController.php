<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Guida;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

/** Guida del pannello: i documenti di `docs/` (vedi App\Support\Guida), aperta a tutti gli utenti del pannello. */
class GuidaController extends Controller
{
    public function index()
    {
        return $this->mostra(null);
    }

    public function show(string $percorso)
    {
        return $this->mostra($percorso);
    }

    public function pdf(string $percorso)
    {
        $pagina = Guida::pagina($percorso, perPdf: true) ?? abort(404);

        return Pdf::loadView('admin.guida.pdf', ['pagina' => $pagina])
            ->download(Str::slug(basename($percorso)).'.pdf');
    }

    public function immagine(string $file)
    {
        $percorso = Guida::radice().'/immagini/'.$file;

        return is_file($percorso) ? response()->file($percorso) : abort(404);
    }

    private function mostra(?string $percorso)
    {
        return view('admin.guida.show', [
            'pagina' => Guida::pagina($percorso) ?? abort(404),
            'percorso' => $percorso,
            'indice' => Guida::indice(),
        ]);
    }
}
