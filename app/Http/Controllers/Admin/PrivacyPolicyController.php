<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrivacyPolicy;
use Illuminate\Http\Request;

/** Informative privacy per tipologia: default (seedato) + override opzionale. Solo admin. */
class PrivacyPolicyController extends Controller
{
    public function index()
    {
        $tipi = collect(config('privacy_policies'))->map(function ($etichetta, $tipo) {
            return [
                'tipo' => $tipo,
                'etichetta' => $etichetta,
                'ha_override' => PrivacyPolicy::where('tipo', $tipo)->where('is_default', false)->exists(),
            ];
        })->values();

        return view('admin.privacy-policies.index', ['tipi' => $tipi]);
    }

    public function edit(string $tipo)
    {
        abort_unless(array_key_exists($tipo, config('privacy_policies')), 404);

        return view('admin.privacy-policies.edit', [
            'tipo' => $tipo,
            'etichetta' => config('privacy_policies')[$tipo],
            'default' => PrivacyPolicy::where('tipo', $tipo)->where('is_default', true)->first(),
            'override' => PrivacyPolicy::where('tipo', $tipo)->where('is_default', false)->first(),
        ]);
    }

    public function update(Request $request, string $tipo)
    {
        abort_unless(array_key_exists($tipo, config('privacy_policies')), 404);

        $data = $request->validate([
            'titolo' => ['required', 'string', 'max:255'],
            'testo' => ['required', 'string'],
        ]);

        PrivacyPolicy::updateOrCreate(['tipo' => $tipo, 'is_default' => false], $data);

        return redirect()->route('admin.privacy-policies.edit', $tipo)->with('status', 'Informativa personalizzata salvata.');
    }

    public function destroy(string $tipo)
    {
        abort_unless(array_key_exists($tipo, config('privacy_policies')), 404);

        PrivacyPolicy::where('tipo', $tipo)->where('is_default', false)->delete();

        return redirect()->route('admin.privacy-policies.edit', $tipo)->with('status', 'Personalizzazione rimossa: in uso il testo predefinito.');
    }
}
