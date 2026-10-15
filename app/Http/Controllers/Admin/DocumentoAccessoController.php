<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentoAccesso;
use Illuminate\Http\Request;

/** Registro delle anteprime e dei download dei documenti riservati (ultimi 12 mesi), sola lettura. Solo admin. */
class DocumentoAccessoController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'event' => (string) $request->input('event'),
            'q' => trim((string) $request->input('q')),
        ];

        $log = DocumentoAccesso::query()->latest('id');

        if (in_array($filters['event'], [DocumentoAccesso::ANTEPRIMA, DocumentoAccesso::DOWNLOAD], true)) {
            $log->where('event', $filters['event']);
        }
        if ($filters['q'] !== '') {
            $log->where(fn ($q) => $q->where('document_title', 'like', "%{$filters['q']}%")
                ->orWhere('member_label', 'like', "%{$filters['q']}%")
                ->orWhere('ip', 'like', "%{$filters['q']}%"));
        }

        return view('admin.registro-download', ['log' => $log->paginate(50)->withQueryString(), 'filters' => $filters]);
    }
}
