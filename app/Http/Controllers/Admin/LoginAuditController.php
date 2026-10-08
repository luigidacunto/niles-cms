<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginAudit;
use Illuminate\Http\Request;

/** Registro accessi in sola lettura (admin e soci, ultimi 12 mesi). Solo admin (admin.role sulla route). */
class LoginAuditController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'guard' => (string) $request->input('guard'),
            'event' => (string) $request->input('event'),
            'q' => trim((string) $request->input('q')),
        ];

        $log = LoginAudit::query()->latest('id');

        if (in_array($filters['guard'], ['admin', 'member'], true)) {
            $log->where('guard', $filters['guard']);
        }
        if (in_array($filters['event'], ['success', 'failed'], true)) {
            $log->where('event', $filters['event']);
        }
        if ($filters['q'] !== '') {
            $log->where(fn ($q) => $q->where('label', 'like', "%{$filters['q']}%")->orWhere('ip', 'like', "%{$filters['q']}%"));
        }

        return view('admin.registro-accessi', ['log' => $log->paginate(50)->withQueryString(), 'filters' => $filters]);
    }
}
