<?php

namespace App\Http\Controllers;

use App\Models\PrivacyPolicy;

class PrivacyPolicyController extends Controller
{
    public function show(string $tipo)
    {
        abort_unless(array_key_exists($tipo, config('privacy_policies')), 404);

        $policy = PrivacyPolicy::perTipo($tipo);
        abort_unless($policy, 404);

        return view('privacy-policy.show', ['policy' => $policy]);
    }
}
