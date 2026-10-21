<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\FooterLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FooterLinkTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role = 'admin'): Admin
    {
        return Admin::create([
            'name' => 'Tester', 'email' => $role.'@example.test', 'password' => 'x',
            'role' => $role, 'active' => true, 'permissions' => [],
        ]);
    }

    public function test_senza_link_la_terza_colonna_non_compare(): void
    {
        $this->get('/')->assertOk()->assertDontSee('Link utili');
    }

    public function test_i_link_compaiono_in_ordine_e_gli_esterni_si_aprono_in_nuova_scheda(): void
    {
        FooterLink::create(['label' => 'Secondo', 'url' => 'https://example.org/due', 'order' => 2]);
        FooterLink::create(['label' => 'Primo', 'url' => '/trasparenza', 'order' => 1]);

        $html = $this->get('/')->assertOk()->assertSee('Link utili')->getContent();

        $this->assertLessThan(strpos($html, 'Secondo'), strpos($html, '>Primo<'));
        $this->assertMatchesRegularExpression('~<a href="https://example.org/due"\s+target="_blank" rel="noopener"~', $html);
        $this->assertDoesNotMatchRegularExpression('~<a href="/trasparenza"\s+target=~', $html);
    }

    public function test_struttura_organizzativa_resta_nella_seconda_colonna_fissa(): void
    {
        $this->get('/')->assertOk()->assertSee('Struttura organizzativa');
    }

    public function test_l_admin_aggiunge_modifica_ed_elimina_i_link(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->put(route('admin.footer.update'), ['links' => [
            ['id' => '', 'label' => 'Bandi', 'url' => 'https://www.cri.it/bandi', 'order' => 1],
            ['id' => '', 'label' => '', 'url' => '', 'order' => 2],            // riga vuota: ignorata
            ['id' => '', 'label' => 'Trasparenza', 'url' => '/trasparenza', 'order' => 3],
        ]])->assertRedirect(route('admin.footer.edit'));

        $this->assertSame(2, FooterLink::count());
        $bandi = FooterLink::where('label', 'Bandi')->first();

        // Il modulo non reinvia le righe rimosse: Trasparenza non arriva più → si elimina.
        $this->put(route('admin.footer.update'), ['links_inviati' => 1, 'links' => [
            ['id' => $bandi->id, 'label' => 'Bandi di gara', 'url' => 'https://www.cri.it/gare', 'order' => 5],
        ]]);

        $this->assertSame(1, FooterLink::count());
        $this->assertSame('Bandi di gara', $bandi->fresh()->label);
        $this->assertSame(5, $bandi->fresh()->order);
    }

    public function test_senza_il_marcatore_una_richiesta_parziale_non_cancella_nulla(): void
    {
        $this->actingAs($this->admin(), 'admin');
        FooterLink::create(['label' => 'Resta', 'url' => '/x', 'order' => 1]);

        $this->put(route('admin.footer.update'), ['links' => []])->assertRedirect();
        $this->assertSame(1, FooterLink::count());

        // Con il marcatore e nessuna riga: l'utente le ha tolte tutte.
        $this->put(route('admin.footer.update'), ['links_inviati' => 1])->assertRedirect();
        $this->assertSame(0, FooterLink::count());
    }

    public function test_indirizzi_non_validi_sono_rifiutati(): void
    {
        $this->actingAs($this->admin(), 'admin');

        foreach (['javascript:alert(1)', 'ftp://example.org', '//example.org', 'pagina-relativa', 'www.example.org'] as $url) {
            $this->put(route('admin.footer.update'), ['links' => [['id' => '', 'label' => 'X', 'url' => $url, 'order' => 1]]])
                ->assertSessionHasErrors('links.0.url');
        }
        $this->put(route('admin.footer.update'), ['links' => [['id' => '', 'label' => 'Solo testo', 'url' => '', 'order' => 1]]])
            ->assertSessionHasErrors('links.0.url');

        $this->assertSame(0, FooterLink::count());
    }

    public function test_solo_gli_admin_vi_accedono(): void
    {
        $this->actingAs($this->admin('editor'), 'admin');

        $this->get(route('admin.footer.edit'))->assertForbidden();
        $this->put(route('admin.footer.update'), ['links' => [['id' => '', 'label' => 'X', 'url' => '/x', 'order' => 1]]])->assertForbidden();
        $this->assertSame(0, FooterLink::count());
    }

    public function test_la_pagina_ha_il_pulsante_per_aggiungere_link_e_il_modello_della_riga(): void
    {
        $html = $this->actingAs($this->admin(), 'admin')->get(route('admin.footer.edit'))->assertOk()->getContent();

        $this->assertStringContainsString('id="aggiungi-link"', $html);
        $this->assertStringContainsString('<template id="modello-riga">', $html);
        $this->assertStringContainsString('name="links[__I__][label]"', $html);
        $this->assertStringContainsString('Nessun link nel footer', $html);
        $this->assertStringContainsString('name="links_inviati"', $html);
    }

    public function test_la_pagina_di_modifica_si_apre(): void
    {
        FooterLink::create(['label' => 'Esistente', 'url' => '/x', 'order' => 1]);

        $this->actingAs($this->admin(), 'admin')->get(route('admin.footer.edit'))->assertOk()->assertSee('Esistente');
    }
}
