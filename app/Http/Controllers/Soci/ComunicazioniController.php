<?php

namespace App\Http\Controllers\Soci;

use App\Http\Controllers\Controller;
use App\Models\ComunicazioneSoci;

/** Comunicazioni interne per i soci loggati (rotte dietro auth:member + area.soci, vedi routes/soci.php). */
class ComunicazioniController extends Controller
{
    public function index()
    {
        return view('soci.comunicazioni.index', [
            'comunicazioni' => ComunicazioneSoci::visibili()->orderByDesc('published_at')->paginate(10),
        ]);
    }

    public function show(ComunicazioneSoci $comunicazione)
    {
        abort_unless(ComunicazioneSoci::visibili()->whereKey($comunicazione->id)->exists(), 404);

        return view('soci.comunicazioni.show', ['comunicazione' => $comunicazione->load('attachments')]);
    }
}
