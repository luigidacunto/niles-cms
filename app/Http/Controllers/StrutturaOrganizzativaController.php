<?php

namespace App\Http\Controllers;

use App\Models\BoardMember;
use App\Models\BoardSetting;
use App\Models\Page;

class StrutturaOrganizzativaController extends Controller
{
    public function show()
    {
        $page = Page::where('slug', 'struttura-organizzativa')->firstOrFail();
        abort_unless($page->published, 404);

        $organigramma = BoardSetting::current()->organigramma;

        return view('struttura-organizzativa.index', [
            'page' => $page,
            'members' => BoardMember::ordered()->get()->filter(fn ($m) => filled($m->name))->values(),
            'organigramma' => $organigramma?->published ? $organigramma : null,
        ]);
    }
}
