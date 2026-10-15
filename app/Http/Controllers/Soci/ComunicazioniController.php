<?php

namespace App\Http\Controllers\Soci;

use App\Http\Controllers\Controller;
use App\Models\ComunicazioneImage;
use App\Models\ComunicazioneSoci;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Comunicazioni interne per i soci loggati (rotte dietro auth:member + area.soci, vedi routes/soci.php). */
class ComunicazioniController extends Controller
{
    public function index()
    {
        return view('soci.comunicazioni.index', [
            'comunicazioni' => ComunicazioneSoci::visibili()->orderByDesc('published_at')->paginate(10),
        ]);
    }

    public function show(Request $request, ComunicazioneSoci $comunicazione)
    {
        $this->assicuraVisibile($comunicazione);
        $member = $request->user('member');

        return view('soci.comunicazioni.show', [
            'comunicazione' => $comunicazione->load('images'),
            // Solo gli allegati che il socio può aprire (categoria assegnata o «Senza categoria»).
            'allegati' => $comunicazione->attachments()->with('category')->get()->filter(fn ($d) => $member->puoVedere($d)),
        ]);
    }

    public function copertina(ComunicazioneSoci $comunicazione)
    {
        $this->assicuraVisibile($comunicazione);

        return $this->serve($comunicazione->cover_image);
    }

    public function immagine(ComunicazioneImage $immagine)
    {
        $this->assicuraVisibile($immagine->comunicazione);

        return $this->serve($immagine->path);
    }

    private function serve(?string $path)
    {
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    private function assicuraVisibile(ComunicazioneSoci $comunicazione): void
    {
        abort_unless(ComunicazioneSoci::visibili()->whereKey($comunicazione->id)->exists(), 404);
    }
}
