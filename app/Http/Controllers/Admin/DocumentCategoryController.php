<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Categorie della libreria documenti generica (`document_categories` con
 * `scope='library'`). Le aree della Trasparenza si gestiscono in
 * Admin\TrasparenzaAreaController. Il flag `selectable` non si tocca da qui: le
 * categorie riservate (es. Organigramma) le crea un seeder / un costrutto.
 */
class DocumentCategoryController extends Controller
{
    public function index()
    {
        return view('admin.documenti.categorie.index', [
            'categories' => DocumentCategory::library()->withCount('documents')
                ->orderBy('order')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.documenti.categorie.form', ['category' => new DocumentCategory]);
    }

    public function store(Request $request)
    {
        DocumentCategory::create($this->validated($request) + ['scope' => 'library']);

        return redirect()->route('admin.document-categories.index')->with('status', 'Categoria creata.');
    }

    public function edit(DocumentCategory $documentCategory)
    {
        abort_unless($documentCategory->scope === 'library', 404);

        return view('admin.documenti.categorie.form', ['category' => $documentCategory]);
    }

    public function update(Request $request, DocumentCategory $documentCategory)
    {
        abort_unless($documentCategory->scope === 'library', 404);
        $documentCategory->update($this->validated($request, $documentCategory));

        return redirect()->route('admin.document-categories.index')->with('status', 'Categoria aggiornata.');
    }

    public function destroy(DocumentCategory $documentCategory)
    {
        abort_unless($documentCategory->scope === 'library', 404);
        abort_if($documentCategory->selectable === false, 422, 'Categoria riservata di sistema: non eliminabile.');

        $count = $documentCategory->documents()->count();
        abort_if($count > 0, 422, "Non puoi eliminare questa categoria: ha ancora {$count} documenti.");

        $documentCategory->delete();

        return redirect()->route('admin.document-categories.index')->with('status', 'Categoria eliminata.');
    }

    private function validated(Request $request, ?DocumentCategory $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('document_categories', 'slug')->ignore($category)],
            'description' => ['nullable', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
