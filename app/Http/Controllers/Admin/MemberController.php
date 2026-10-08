<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Rules\CodiceFiscale;
use App\Services\MemberImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Anagrafica membri del comitato. Permesso unico 'membri' (lettura+scrittura insieme): chi lo ha può
 * tutto, quindi un solo controllo. Import Excel in due passi (anteprima → conferma), logica in
 * MemberImportService.
 */
class MemberController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeMembri($request);

        $filters = [
            'q' => trim((string) $request->input('q')),
            'ruolo' => (string) $request->input('ruolo'),
            'presenza' => (string) $request->input('presenza'), // '' (attivi) | disabilitati | rimossi | tutti
        ];

        $members = Member::query()->orderBy('cognome')->orderBy('nome');

        if ($filters['q'] !== '') {
            $members->where(fn ($q) => $q
                ->where('cognome', 'like', "%{$filters['q']}%")
                ->orWhere('nome', 'like', "%{$filters['q']}%")
                ->orWhere('codice_fiscale', 'like', "%{$filters['q']}%")
                ->orWhere('email', 'like', "%{$filters['q']}%"));
        }
        if (array_key_exists($filters['ruolo'], Member::RUOLI)) {
            $members->where('ruolo', $filters['ruolo']);
        }
        match ($filters['presenza']) {
            'disabilitati' => $members->where('disabilitato', true),
            'richieste' => $members->whereNotNull('richiesta_disattivazione_at')->where('disabilitato', false),
            'rimossi' => $members->onlyTrashed(),
            'tutti' => $members->withTrashed(),
            default => $members->where('disabilitato', false),
        };

        return view('admin.membri.index', [
            'members' => $members->paginate(30)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizeMembri($request);

        return view('admin.membri.form', ['member' => new Member(['ruolo' => Member::RUOLO_VOLONTARIO])]);
    }

    public function store(Request $request)
    {
        $this->authorizeMembri($request);

        Member::create($this->validated($request));

        return redirect()->route('admin.membri.index')->with('status', 'Membro aggiunto.');
    }

    public function edit(Request $request, Member $member)
    {
        $this->authorizeMembri($request);

        return view('admin.membri.form', ['member' => $member]);
    }

    public function update(Request $request, Member $member)
    {
        $this->authorizeMembri($request);

        $data = $this->validated($request, $member);
        $member->update($data);
        if ($data['disabilitato']) {
            $member->forceFill(['richiesta_disattivazione_at' => null])->save(); // richiesta evasa
        }

        return redirect()->route('admin.membri.index')->with('status', 'Membro aggiornato.');
    }

    public function respingiRichiesta(Request $request, Member $member)
    {
        $this->authorizeMembri($request);

        $member->forceFill(['richiesta_disattivazione_at' => null])->save();

        return redirect()->route('admin.membri.edit', $member)->with('status', 'Richiesta di disattivazione respinta.');
    }

    public function destroy(Request $request, Member $member)
    {
        $this->authorizeMembri($request);

        $member->rimuovi();

        return redirect()->route('admin.membri.index')->with('status', 'Membro rimosso (recuperabile dal filtro "Rimossi").');
    }

    public function restore(Request $request, int $id)
    {
        $this->authorizeMembri($request);

        Member::onlyTrashed()->findOrFail($id)->ripristina();

        return redirect()->route('admin.membri.index')->with('status', 'Membro ripristinato.');
    }

    public function importForm(Request $request)
    {
        $this->authorizeMembri($request);

        return view('admin.membri.import');
    }

    public function importPreview(Request $request, MemberImportService $service)
    {
        $this->authorizeMembri($request);

        $data = $request->validate([
            'lista' => ['required', Rule::in(array_keys(MemberImportService::LISTE))],
            'file' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
        ], [
            'file.mimes' => 'Il file deve essere un Excel (.xlsx).',
            'file.required' => 'Seleziona il file Excel da importare.',
        ]);

        $disk = Storage::disk('local');
        $this->purgeStaleImports();

        $token = (string) Str::uuid();
        $disk->putFileAs('member-imports', $request->file('file'), "$token.xlsx");

        $plan = $service->plan($disk->path("member-imports/$token.xlsx"), $data['lista']);
        if ($plan['blocking']) {
            $disk->delete("member-imports/$token.xlsx"); // niente da confermare: il file non serve più
        }

        return view('admin.membri.import-preview', [
            'plan' => $plan,
            'lista' => $data['lista'],
            'token' => $token,
        ]);
    }

    public function importApply(Request $request, MemberImportService $service)
    {
        $this->authorizeMembri($request);

        $data = $request->validate([
            'lista' => ['required', Rule::in(array_keys(MemberImportService::LISTE))],
            'token' => ['required', 'uuid'],
        ]);

        $relative = "member-imports/{$data['token']}.xlsx";
        abort_unless(Storage::disk('local')->exists($relative), 404);

        // Ricalcolato dal file salvato (non dall'anteprima nel browser): lo ruolo del DB può essere cambiato nel frattempo.
        $plan = $service->plan(Storage::disk('local')->path($relative), $data['lista']);
        if ($plan['blocking']) {
            return redirect()->route('admin.membri.import')->withErrors(['file' => $plan['blocking']]);
        }

        $service->apply($plan);
        Storage::disk('local')->delete($relative);

        $c = $plan['counts'];

        return redirect()->route('admin.membri.index')->with('status',
            "Import completato: {$c['nuovo']} nuovi, {$c['cambio_ruolo']} cambi ruolo, {$c['ripristino']} ripristinati, {$c['rimosso']} rimossi.");
    }

    /** Anteprime abbandonate (annulla/chiusura pagina): cancellate dopo 1 ora, a ogni nuovo upload. */
    private function purgeStaleImports(): void
    {
        $disk = Storage::disk('local');
        foreach ($disk->files('member-imports') as $file) {
            if ($disk->lastModified($file) < now()->subHour()->getTimestamp()) {
                $disk->delete($file);
            }
        }
    }

    private function authorizeMembri(Request $request): void
    {
        abort_unless($request->user('admin')->hasPermission('membri'), 403);
    }

    private function validated(Request $request, ?Member $member = null): array
    {
        $data = $request->validate([
            'codice_fiscale' => ['required', new CodiceFiscale, Rule::unique('members', 'codice_fiscale')->ignore($member)],
            'nome' => ['required', 'string', 'max:255'],
            'cognome' => ['required', 'string', 'max:255'],
            'data_nascita' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('members', 'email')->ignore($member)],
            'telefono' => ['nullable', 'string', 'max:50'],
            'telefoni_aggiuntivi' => ['nullable', 'string', 'max:500'],
            'ruolo' => ['required', Rule::in(array_keys(Member::RUOLI))],
            'disabilitato' => ['boolean'],
        ], [
            'codice_fiscale.unique' => 'Esiste già un membro con questo codice fiscale (controlla anche tra i rimossi).',
            'email.unique' => 'Questa email è già associata a un altro membro.',
        ]);

        $data['codice_fiscale'] = strtoupper($data['codice_fiscale']);
        $data['email'] = isset($data['email']) ? strtolower($data['email']) : null;
        $data['telefoni_aggiuntivi'] = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string) ($data['telefoni_aggiuntivi'] ?? ''))))) ?: null;
        $data['disabilitato'] = $request->boolean('disabilitato');

        return $data;
    }
}
