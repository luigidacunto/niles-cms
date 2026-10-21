<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FooterLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Link della terza colonna del footer pubblico. Solo admin (middleware `admin.role` sulla rotta).
 * Un solo modulo: righe esistenti (modifica / «elimina») + righe vuote per aggiungerne di nuove.
 */
class FooterLinkController extends Controller
{
    public function edit()
    {
        return view('admin.footer.edit', ['links' => FooterLink::ordered()->get()]);
    }

    public function update(Request $request)
    {
        Validator::make($request->all(), [
            'links' => ['array'],
            'links.*.id' => ['nullable', 'integer', 'exists:footer_links,id'],
            'links.*.label' => ['nullable', 'string', 'max:100'],
            'links.*.url' => ['nullable', 'string', 'max:2000'],
            'links.*.order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'links.*.delete' => ['nullable', 'boolean'],
        ], [], ['links.*.label' => 'testo', 'links.*.url' => 'link'])
            ->after(function ($validator) use ($request) {
                foreach ((array) $request->input('links') as $i => $riga) {
                    $label = trim((string) ($riga['label'] ?? ''));
                    $url = trim((string) ($riga['url'] ?? ''));
                    if ($label === '' && $url === '') {
                        continue;
                    }
                    if ($label === '' || $url === '') {
                        $validator->errors()->add("links.$i.url", 'Compila sia il testo sia il link (riga '.($i + 1).').');
                    } elseif (! preg_match('~^(https?://\S+|/(?!/)\S*)$~i', $url)) {
                        $validator->errors()->add("links.$i.url", 'Il link della riga '.($i + 1).' deve cominciare con https:// (sito esterno) oppure con / (pagina di questo sito).');
                    }
                }
            })->validate();

        foreach ((array) $request->input('links') as $riga) {
            $label = trim((string) ($riga['label'] ?? ''));
            $url = trim((string) ($riga['url'] ?? ''));
            $link = ! empty($riga['id']) ? FooterLink::find($riga['id']) : null;

            if ($link && ! empty($riga['delete'])) {
                $link->delete();
            } elseif ($label !== '' && $url !== '') {
                ($link ?? new FooterLink)->fill(['label' => $label, 'url' => $url, 'order' => (int) ($riga['order'] ?? 0)])->save();
            }
        }

        return redirect()->route('admin.footer.edit')->with('status', 'Link del footer salvati.');
    }
}
