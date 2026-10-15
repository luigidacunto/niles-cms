<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Categorie dei documenti riservati (`document_categories` con scope `soci`). Una categoria decide chi vede i suoi
 * documenti: la «Senza categoria» di sistema è aperta a tutti i soci, le altre solo ai soci a cui sono assegnate
 * (dalla scheda del socio). Permesso unico 'comunicazioni_soci'.
 */
class CategoriaRiservataController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeRiservati($request);

        return view('admin.riservati.categorie.index', [
            'categories' => DocumentCategory::soci()->withCount(['documents', 'members'])
                ->orderByRaw('slug = ? desc', [DocumentCategory::SLUG_GENERALE])->orderBy('order')->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizeRiservati($request);

        return view('admin.riservati.categorie.form', ['category' => new DocumentCategory]);
    }

    public function store(Request $request)
    {
        $this->authorizeRiservati($request);
        $data = $this->validated($request);

        DocumentCategory::create($data + [
            'slug' => DocumentCategory::uniqueSlug(Str::slug($data['name']) ?: 'categoria'),
            'scope' => DocumentCategory::SCOPE_SOCI,
            'selectable' => true,
        ]);

        return redirect()->route('admin.categorie-riservate.index')->with('status', 'Categoria creata.');
    }

    public function edit(Request $request, DocumentCategory $categoria)
    {
        $this->authorizeCategoria($request, $categoria);

        return view('admin.riservati.categorie.form', ['category' => $categoria]);
    }

    public function update(Request $request, DocumentCategory $categoria)
    {
        $this->authorizeCategoria($request, $categoria);
        $categoria->update($this->validated($request)); // lo slug non cambia: resta stabile per i riferimenti

        return redirect()->route('admin.categorie-riservate.index')->with('status', 'Categoria aggiornata.');
    }

    public function destroy(Request $request, DocumentCategory $categoria)
    {
        $this->authorizeCategoria($request, $categoria);

        $count = $categoria->documents()->count();
        abort_if($count > 0, 422, "Non puoi eliminare questa categoria: ha ancora {$count} documenti.");

        $categoria->delete(); // le assegnazioni ai soci cadono con la categoria (FK cascade)

        return redirect()->route('admin.categorie-riservate.index')->with('status', 'Categoria eliminata.');
    }

    /** Categoria riservata modificabile: scope `soci`, esclusa quella di sistema. */
    private function authorizeCategoria(Request $request, DocumentCategory $categoria): void
    {
        $this->authorizeRiservati($request);
        abort_unless($categoria->scope === DocumentCategory::SCOPE_SOCI && ! $categoria->isGenerale(), 404);
    }

    private function authorizeRiservati(Request $request): void
    {
        abort_unless($request->user('admin')->hasPermission('comunicazioni_soci'), 403);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);
        $data['order'] = (int) ($data['order'] ?? 0);

        return $data;
    }
}
