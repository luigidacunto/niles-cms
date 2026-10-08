<?php

namespace App\Http\Controllers;

use App\Models\DocumentCategory;
use App\Models\Page;

class TrasparenzaController extends Controller
{
    public function index()
    {
        // Testo introduttivo: riga `pages` con slug "trasparenza" (seminata da FirstInstallSeeder),
        // modificabile dalla sezione Pagine come qualsiasi altra. Se manca, la pagina rende comunque
        // solo l'elenco.
        $page = Page::where('slug', 'trasparenza')->first();

        $categories = DocumentCategory::query()
            ->trasparenza()
            ->whereHas('documents', fn ($q) => $q->where('published', true))
            ->with(['documents' => fn ($q) => $q->where('published', true)
                ->orderByDesc('published_at')->orderByDesc('id')])
            ->orderBy('order')->orderBy('name')
            ->get();

        return view('trasparenza.index', compact('page', 'categories'));
    }
}
