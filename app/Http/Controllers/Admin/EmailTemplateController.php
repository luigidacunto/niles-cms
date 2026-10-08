<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Support\PolicyPlaceholders;
use Illuminate\Http\Request;

/** Template delle email di sistema: default seedato (sola lettura) + personalizzazione opzionale. Solo admin. */
class EmailTemplateController extends Controller
{
    public function index()
    {
        $tipi = collect(config('email_templates'))->map(fn ($def, $tipo) => [
            'tipo' => $tipo,
            'etichetta' => $def['label'],
            'descrizione' => $def['descrizione'],
            'ha_override' => EmailTemplate::where('tipo', $tipo)->where('is_default', false)->exists(),
        ])->values();

        return view('admin.email-templates.index', ['tipi' => $tipi]);
    }

    public function edit(string $tipo)
    {
        $def = $this->definizione($tipo);

        return view('admin.email-templates.edit', [
            'tipo' => $tipo,
            'def' => $def,
            'default' => EmailTemplate::where('tipo', $tipo)->where('is_default', true)->first(),
            // Testo di partenza "di fabbrica" (config), per il pulsante Ripristina: indipendente dal DB.
            'testoSorgente' => EmailTemplate::testoPredefinito($tipo),
            'override' => EmailTemplate::where('tipo', $tipo)->where('is_default', false)->first(),
            'segnapostoComitato' => ['{nome_comitato}', '{email_comitato}', '{telefono_comitato}', '{indirizzo_comitato}', '{piva_comitato}', '{codice_fiscale_comitato}', '{pec_comitato}'],
        ]);
    }

    public function update(Request $request, string $tipo)
    {
        $this->definizione($tipo);

        $data = $request->validate([
            'oggetto' => ['required', 'string', 'max:255'],
            'corpo' => ['required', 'string'],
        ]);

        $data['corpo'] = $this->normalizzaLink($data['corpo']);

        EmailTemplate::updateOrCreate(['tipo' => $tipo, 'is_default' => false], $data);

        return redirect()->route('admin.email-templates.edit', $tipo)->with('status', 'Template personalizzato salvato.');
    }

    public function destroy(string $tipo)
    {
        $this->definizione($tipo);

        EmailTemplate::where('tipo', $tipo)->where('is_default', false)->delete();

        return redirect()->route('admin.email-templates.edit', $tipo)->with('status', 'Personalizzazione rimossa: in uso il testo predefinito.');
    }

    /** Anteprima con dati di esempio del template in uso (personalizzato se c'è, altrimenti predefinito). */
    public function anteprima(string $tipo)
    {
        $this->definizione($tipo);

        $resa = EmailTemplate::render($tipo, EmailTemplate::valoriEsempio($tipo), EmailTemplate::grezziEsempio($tipo));

        return response('<!doctype html><meta charset="utf-8"><title>'.e($resa['oggetto']).'</title>'
            .'<p style="font:12px sans-serif;color:#666;border-bottom:1px solid #ddd;padding:8px">Oggetto: <strong>'.e($resa['oggetto']).'</strong> (dati di esempio)</p>'
            .'<div style="font:14px sans-serif;padding:8px 16px">'.$resa['corpo'].'</div>');
    }

    /**
     * Un segnaposto usato come indirizzo di un link (`href="{link_preferenze}"`) non deve avere davanti un
     * protocollo né essere codificato (`%7B…%7D`): gli editor visuali tendono ad aggiungerlo.
     */
    private function normalizzaLink(string $html): string
    {
        $html = preg_replace('#href="(?:https?://|//)?(?:%7B|\{)([a-z_]+)(?:%7D|\})"#i', 'href="{$1}"', $html);

        return $html;
    }

    private function definizione(string $tipo): array
    {
        abort_unless(array_key_exists($tipo, config('email_templates')), 404);

        return config('email_templates')[$tipo];
    }
}
