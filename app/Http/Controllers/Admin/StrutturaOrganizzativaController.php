<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BoardMember;
use App\Models\BoardSetting;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Page;
use App\Support\DocumentStore;
use App\Support\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Composizione della sezione Struttura Organizzativa (pagina di sistema
 * `struttura-organizzativa`). Solo admin (middleware `admin.role` sulla rotta).
 *
 */
class StrutturaOrganizzativaController extends Controller
{
    /** Slug della categoria libreria riservata dove finiscono gli organigrammi. */
    private const ORGANIGRAMMA_CATEGORY = 'organigramma';

    public function edit()
    {
        $bySlot = BoardMember::ordered()->get()->groupBy('slot');

        return view('admin.struttura-organizzativa.edit', [
            'page' => Page::where('slug', 'struttura-organizzativa')->first(),
            'president' => $bySlot->get(BoardMember::SLOT_PRESIDENT)?->first(),
            'vice' => $bySlot->get(BoardMember::SLOT_VICE)?->first(),
            'members' => $bySlot->get(BoardMember::SLOT_MEMBER) ?? collect(),
            'organigramma' => BoardSetting::current()->organigramma,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'slots.president.name' => ['nullable', 'string', 'max:255'],
            'slots.president.bio' => ['nullable', 'string', 'max:2000'],
            'slots.president.photo' => ['nullable', 'image', 'max:8192'],
            'slots.vice_president.name' => ['nullable', 'string', 'max:255'],
            'slots.vice_president.bio' => ['nullable', 'string', 'max:2000'],
            'slots.vice_president.photo' => ['nullable', 'image', 'max:8192'],
            'members' => ['array'],
            'members.*.id' => ['nullable', 'integer', 'exists:board_members,id'],
            'members.*.role_label' => ['nullable', 'string', 'max:255'],
            'members.*.name' => ['nullable', 'string', 'max:255'],
            'members.*.bio' => ['nullable', 'string', 'max:2000'],
            'members.*.photo' => ['nullable', 'image', 'max:8192'],
            'organigramma' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx', 'max:20480'],
        ]);

        DB::transaction(function () use ($request) {
            $errors = [];

            foreach (array_keys(BoardMember::FIXED_SLOTS) as $slot) {
                $this->saveFixedSlot($request, $slot, $errors);
            }
            $this->saveMembers($request, $errors);

            if ($errors) {
                // Rollback della transazione: eventuali cancellazioni fatte finora vengono annullate.
                throw ValidationException::withMessages($errors);
            }

            $this->saveOrganigramma($request);
        });

        return redirect()->route('admin.struttura-organizzativa.edit')
            ->with('status', 'Struttura organizzativa aggiornata.');
    }

    private function saveFixedSlot(Request $request, string $slot, array &$errors): void
    {
        $data = $request->input("slots.$slot", []);
        $photo = $request->file("slots.$slot.photo");
        $removePhoto = $request->boolean("slots.$slot.remove_photo");
        $existing = BoardMember::where('slot', $slot)->first();

        $name = trim((string) ($data['name'] ?? ''));
        $bio = trim((string) ($data['bio'] ?? ''));

        // Blocco vuoto → la carica non è coperta: riga rimossa.
        if ($name === '' && $bio === '' && ! $photo && ! ($existing && $existing->photo_path && ! $removePhoto)) {
            $this->deleteMember($existing);

            return;
        }

        $label = BoardMember::FIXED_SLOTS[$slot];
        if ($name === '') {
            $errors["slots.$slot.name"] = "Indica il nome del/della $label.";
        }
        if ($bio === '') {
            $errors["slots.$slot.bio"] = "Scrivi una presentazione per il/la $label.";
        }
        if ($errors) {
            return;
        }

        $member = $existing ?: new BoardMember(['slot' => $slot]);
        $member->name = $name;
        $member->bio = $bio;
        $member->role_label = null;
        $member->order = 0;
        $this->applyPhoto($member, $photo, $removePhoto, "$name-$label");
        $member->save();
    }

    private function saveMembers(Request $request, array &$errors): void
    {
        $rows = $request->input('members', []);
        $order = 0;

        foreach ($rows as $i => $row) {
            $id = $row['id'] ?? null;
            $existing = $id ? BoardMember::where('id', $id)->where('slot', BoardMember::SLOT_MEMBER)->first() : null;

            if (! empty($row['remove'])) {
                $this->deleteMember($existing);

                continue;
            }

            $roleLabel = trim((string) ($row['role_label'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            $bio = trim((string) ($row['bio'] ?? ''));
            $photo = $request->file("members.$i.photo");

            // Riga nuova completamente vuota: ignorata.
            if (! $existing && $roleLabel === '' && $name === '' && $bio === '' && ! $photo) {
                continue;
            }

            if ($roleLabel === '') {
                $errors["members.$i.role_label"] = 'Indica il ruolo di questo blocco.';
            }
            if ($name === '') {
                $errors["members.$i.name"] = 'Indica il nome.';
            }
            if ($bio === '') {
                $errors["members.$i.bio"] = 'Scrivi una presentazione.';
            }
            if ($errors) {
                continue;
            }

            $member = $existing ?: new BoardMember(['slot' => BoardMember::SLOT_MEMBER]);
            $member->role_label = $roleLabel;
            $member->name = $name;
            $member->bio = $bio;
            $member->order = $order++;
            $this->applyPhoto($member, $photo, ! empty($row['remove_photo']), "$name-$roleLabel");
            $member->save();
        }
    }

    private function applyPhoto(BoardMember $member, $photo, bool $remove, string $nameBase): void
    {
        if ($photo) {
            if ($member->photo_path) {
                Storage::disk('public')->delete($member->photo_path);
            }
            $member->photo_path = ImageUpload::storeResized($photo, 'board-photos', $nameBase, 800);

            return;
        }

        if ($remove && $member->photo_path) {
            Storage::disk('public')->delete($member->photo_path);
            $member->photo_path = null;
        }
    }

    private function deleteMember(?BoardMember $member): void
    {
        if (! $member) {
            return;
        }
        if ($member->photo_path) {
            Storage::disk('public')->delete($member->photo_path);
        }
        $member->delete();
    }

    private function saveOrganigramma(Request $request): void
    {
        $settings = BoardSetting::current();

        if ($request->boolean('remove_organigramma')) {
            // Sgancia dalla pagina; il Document resta in libreria come storico.
            $settings->update(['organigramma_document_id' => null]);
        }

        if (! $request->hasFile('organigramma')) {
            return;
        }

        $category = DocumentCategory::firstOrCreate(
            ['slug' => self::ORGANIGRAMMA_CATEGORY],
            ['name' => 'Organigramma', 'scope' => 'library', 'selectable' => false, 'order' => 99]
        );

        $document = new Document([
            'document_category_id' => $category->id,
            'title' => 'Organigramma '.now()->format('Y'),
            'published' => true,
            'published_at' => now(),
        ]);
        DocumentStore::store($request->file('organigramma'), $document);
        $document->save();

        $settings->update(['organigramma_document_id' => $document->id]);
    }
}
