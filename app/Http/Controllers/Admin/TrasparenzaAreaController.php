<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Aree in cui sono divisi i documenti della pagina Trasparenza (Albo, Sovvenzioni, …).
 * Sono le `document_categories` con `scope='trasparenza'`. Le categorie della libreria
 * generica si gestiscono altrove (Admin\DocumentCategoryController).
 */
class TrasparenzaAreaController extends Controller
{
    public function index()
    {
        return view('admin.trasparenza.aree.index', [
            'categories' => DocumentCategory::trasparenza()->withCount('documents')
                ->orderBy('order')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.trasparenza.aree.form', ['category' => new DocumentCategory]);
    }

    public function store(Request $request)
    {
        DocumentCategory::create($this->validated($request) + ['scope' => 'trasparenza']);

        return redirect()->route('admin.trasparenza-aree.index')->with('status', 'Area creata.');
    }

    public function edit(DocumentCategory $trasparenzaArea)
    {
        return view('admin.trasparenza.aree.form', ['category' => $trasparenzaArea]);
    }

    public function update(Request $request, DocumentCategory $trasparenzaArea)
    {
        $trasparenzaArea->update($this->validated($request, $trasparenzaArea));

        return redirect()->route('admin.trasparenza-aree.index')->with('status', 'Area aggiornata.');
    }

    public function destroy(DocumentCategory $trasparenzaArea)
    {
        $count = $trasparenzaArea->documents()->count();
        abort_if($count > 0, 422, "Non puoi eliminare questa area: ha ancora {$count} documenti. Spostali prima in un'altra area.");

        $trasparenzaArea->delete();

        return redirect()->route('admin.trasparenza-aree.index')->with('status', 'Area eliminata.');
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
