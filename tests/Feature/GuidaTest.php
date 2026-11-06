<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Support\Guida;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Guida del pannello: legge i documenti veri di docs/, aperta a tutti gli utenti del pannello. */
class GuidaTest extends TestCase
{
    use RefreshDatabase;

    private function editorSenzaPermessi(): Admin
    {
        return Admin::create(['name' => 'E', 'email' => 'e@example.test', 'password' => 'x', 'role' => 'editor', 'active' => true, 'permissions' => []]);
    }

    public function test_tutti_gli_utenti_vedono_indice_e_pagine_con_i_link_riscritti(): void
    {
        $this->withoutVite();
        $editor = $this->actingAs($this->editorSenzaPermessi(), 'admin');

        $editor->get(route('admin.guida.index'))->assertOk()
            ->assertSee('Documentazione di NILES')
            ->assertSee('Utenti e controllo')                                                       // indice dalle cartelle
            ->assertSee(route('admin.guida.show', '02-contenuti/01-post'), false);                 // link a un documento → pannello

        $r = $editor->get(route('admin.guida.show', '00-installazione/04-aggiornamento'))->assertOk()->assertSee('Scarica PDF');
        $r->assertSee('https://github.com/'.config('app.update_check.repository').'/blob/v'.config('app.version').'/scripts/backup.sh', false);
        $this->assertStringNotContainsString('.md"', $r->getContent());                            // nessun link .md rimasto
    }

    public function test_ancore_dei_titoli_come_su_github(): void
    {
        $this->actingAs($this->editorSenzaPermessi(), 'admin')
            ->get(route('admin.guida.show', '00-installazione/03-configurazione-env'))->assertOk()
            ->assertSee('id="posta-in-uscita"', false);
    }

    public function test_immagini_pdf_e_percorsi_non_validi(): void
    {
        $editor = $this->actingAs($this->editorSenzaPermessi(), 'admin');
        $immagine = collect(glob(Guida::radice().'/immagini/*/*.png'))->first();
        $relativa = basename(dirname($immagine)).'/'.basename($immagine);

        $editor->get(route('admin.guida.immagine', $relativa))->assertOk();
        $editor->get('/admin/guida/immagini/../README.md')->assertNotFound();
        $editor->get('/admin/guida/00-installazione/99-non-esiste')->assertNotFound();
        $editor->get('/admin/guida/../composer')->assertNotFound();

        $pdf = $editor->get(route('admin.guida.pdf', '02-contenuti/01-post'))->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('content-type'));
    }

    public function test_serve_il_login(): void
    {
        $this->get(route('admin.guida.index'))->assertRedirect(route('admin.login'));
    }
}
