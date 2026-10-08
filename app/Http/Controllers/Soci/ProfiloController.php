<?php

namespace App\Http\Controllers\Soci;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/** Pagina "I miei dati" dell'area soci: sola lettura (la modifica dei propri dati arriverà più avanti). */
class ProfiloController extends Controller
{
    public function show(Request $request)
    {
        return view('soci.profilo', ['member' => $request->user('member')]);
    }

    /** Il socio chiede la disattivazione dell'accesso: non è immediata, la conferma un admin (vedi MemberController). */
    public function richiediDisattivazione(Request $request)
    {
        $member = $request->user('member');

        if (! $member->richiesta_disattivazione_at) {
            $member->richiesta_disattivazione_at = now();
            $member->save();
        }

        return redirect()->route('soci.profilo');
    }
}
