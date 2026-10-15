<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\CommitteeInfo;
use App\Models\ComunicazioneSoci;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentoAccesso;
use App\Models\Member;
use App\Models\PrivacyPolicy;
use App\Support\AreaSoci;
use Database\Seeders\AggiornaInformativaSociDocumentiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentiRiservatiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        CommitteeInfo::current()->update(['area_soci_attiva' => true]);
    }

    private function member(string $cf = 'AAAAAA00A00A000A'): Member
    {
        return Member::create([
            'codice_fiscale' => $cf, 'nome' => 'Anna', 'cognome' => 'Rossi',
            'ruolo' => Member::RUOLO_VOLONTARIO, 'email' => strtolower($cf).'@example.test',
        ]);
    }

    private function admin(): Admin
    {
        return Admin::firstOrCreate(['email' => 'ada@example.test'], ['name' => 'Ada', 'password' => 'x', 'role' => 'admin', 'active' => true]);
    }

    private function categoria(string $nome): DocumentCategory
    {
        return DocumentCategory::create(['name' => $nome, 'slug' => DocumentCategory::uniqueSlug(str($nome)->slug()->toString()),
            'scope' => DocumentCategory::SCOPE_SOCI, 'selectable' => true]);
    }

    private function documento(DocumentCategory $categoria, string $titolo = 'Protocollo', ?string $sotto = null, string $ext = 'pdf'): Document
    {
        $doc = new Document(['document_category_id' => $categoria->id, 'title' => $titolo, 'sottocategoria' => $sotto,
            'published' => true, 'published_at' => now()]);
        $path = 'documenti/'.str($titolo)->slug().'.'.$ext;
        Storage::disk('local')->put($path, 'contenuto');
        $doc->file_path = $path;
        $doc->file_size = 9;
        $doc->save();

        return $doc;
    }

    public function test_il_socio_vede_senza_categoria_e_le_categorie_assegnate_ma_non_le_altre(): void
    {
        $salute = $this->categoria('Salute');
        $emergenza = $this->categoria('Emergenza');
        $this->documento(AreaSoci::categoriaDocumenti(), 'Regolamento interno');
        $this->documento($salute, 'Protocollo BLSD', 'BLSD');
        $this->documento($emergenza, 'Piano maxi emergenze', 'Maxi emergenze');

        $anna = $this->member();
        $anna->categorieRiservate()->attach($salute);

        $this->actingAs($anna, 'member')->get(route('soci.documenti'))
            ->assertOk()
            ->assertSee('Senza categoria')->assertSee('Regolamento interno')
            ->assertSee('Salute')->assertSee('BLSD')->assertSee('Protocollo BLSD')
            ->assertDontSee('Emergenza')->assertDontSee('Piano maxi emergenze');
    }

    public function test_anteprima_registra_e_il_file_di_una_categoria_non_assegnata_e_404(): void
    {
        $salute = $this->categoria('Salute');
        $emergenza = $this->categoria('Emergenza');
        $ok = $this->documento($salute, 'Protocollo BLSD');
        $no = $this->documento($emergenza, 'Piano emergenze');

        $anna = $this->member();
        $anna->categorieRiservate()->attach($salute);
        $this->actingAs($anna, 'member');

        $this->get(route('soci.documenti.anteprima', $ok))->assertOk()
            ->assertSee(route('soci.documenti.file', $ok), false)->assertSee(route('soci.documenti.scarica', $ok), false);
        $this->get(route('soci.documenti.anteprima', $no))->assertNotFound();
        $this->get(route('soci.documenti.file', $no))->assertNotFound();
        $this->get(route('soci.documenti.scarica', $no))->assertNotFound();
        $this->get(route('documenti.show', $no))->assertNotFound();

        $this->assertSame(1, DocumentoAccesso::count());
        $riga = DocumentoAccesso::first();
        $this->assertSame([DocumentoAccesso::ANTEPRIMA, 'Protocollo BLSD', 'Rossi Anna'], [$riga->event, $riga->document_title, $riga->member_label]);
    }

    public function test_il_file_e_inline_senza_registro_e_il_download_e_attachment_con_registro(): void
    {
        $doc = $this->documento(AreaSoci::categoriaDocumenti(), 'Regolamento');
        $this->actingAs($this->member(), 'member');

        $inline = $this->get(route('soci.documenti.file', $doc));
        $inline->assertOk();
        $this->assertStringContainsString('inline', $inline->headers->get('Content-Disposition'));
        $this->assertSame(0, DocumentoAccesso::count());

        $download = $this->get(route('soci.documenti.scarica', $doc));
        $download->assertOk();
        $this->assertStringContainsString('attachment', $download->headers->get('Content-Disposition'));
        $this->assertSame([DocumentoAccesso::DOWNLOAD], DocumentoAccesso::pluck('event')->all());
    }

    public function test_link_diretto_del_socio_porta_all_anteprima_mai_al_download(): void
    {
        $doc = $this->documento(AreaSoci::categoriaDocumenti(), 'Regolamento');

        $this->get(route('documenti.show', $doc))->assertRedirect(route('soci.login'));
        $this->actingAs($this->member(), 'member')->get(route('documenti.show', $doc))
            ->assertRedirect(route('soci.documenti.anteprima', $doc));
        $this->assertSame(0, DocumentoAccesso::count()); // il redirect non è ancora un'apertura
    }

    public function test_registro_conservato_12_mesi(): void
    {
        $doc = $this->documento(AreaSoci::categoriaDocumenti());
        $anna = $this->member();
        $vecchio = DocumentoAccesso::create(['document_id' => $doc->id, 'document_title' => 'x', 'member_id' => $anna->id,
            'member_label' => 'x', 'event' => 'download']);
        $vecchio->forceFill(['created_at' => now()->subMonths(13)])->save();

        $this->actingAs($anna, 'member')->get(route('soci.documenti.anteprima', $doc))->assertOk();

        $this->assertNull(DocumentoAccesso::find($vecchio->id));
        $this->assertSame(1, DocumentoAccesso::count());
    }

    public function test_admin_carica_solo_pdf_e_immagini_sul_disco_privato(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $categoria = AreaSoci::categoriaDocumenti();
        $base = ['title' => 'Doc', 'document_category_id' => $categoria->id, 'published' => '1', 'sottocategoria' => '  BLSD '];

        $this->post(route('admin.documenti-riservati.store'), $base + ['file' => UploadedFile::fake()->create('a.docx', 10)])
            ->assertSessionHasErrors('file');

        $this->post(route('admin.documenti-riservati.store'), $base + ['file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')])
            ->assertRedirect(route('admin.documenti-riservati.index'));
        $doc = Document::firstOrFail();
        $this->assertSame('BLSD', $doc->sottocategoria);
        Storage::disk('local')->assertExists($doc->file_path);

        // Una categoria pubblica (library) non è ammessa tra quelle riservate.
        $pubblica = DocumentCategory::create(['name' => 'Pub', 'slug' => 'pub', 'scope' => 'library']);
        $this->post(route('admin.documenti-riservati.store'), ['document_category_id' => $pubblica->id] + $base
            + ['file' => UploadedFile::fake()->create('b.pdf', 10, 'application/pdf')])->assertSessionHasErrors('document_category_id');
    }

    public function test_categorie_riservate_la_di_sistema_non_si_tocca_e_con_documenti_non_si_elimina(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $generale = AreaSoci::categoriaDocumenti();

        $this->get(route('admin.categorie-riservate.edit', $generale))->assertNotFound();
        $this->delete(route('admin.categorie-riservate.destroy', $generale))->assertNotFound();

        $this->post(route('admin.categorie-riservate.store'), ['name' => 'Inclusione sociale', 'order' => ''])
            ->assertRedirect(route('admin.categorie-riservate.index'));
        $categoria = DocumentCategory::where('name', 'Inclusione sociale')->firstOrFail();
        $this->assertSame('inclusione-sociale', $categoria->slug);
        $this->assertSame(0, $categoria->order);

        $this->documento($categoria);
        $this->delete(route('admin.categorie-riservate.destroy', $categoria))->assertStatus(422);
    }

    public function test_assegnazione_categorie_dalla_scheda_del_socio(): void
    {
        $salute = $this->categoria('Salute');
        $generale = AreaSoci::categoriaDocumenti();
        $anna = $this->member();
        $this->actingAs($this->admin(), 'admin');
        $dati = ['codice_fiscale' => $anna->codice_fiscale, 'nome' => 'Anna', 'cognome' => 'Rossi', 'ruolo' => 'volontario', 'email' => $anna->email];

        // La «Senza categoria» non è assegnabile (è già di tutti).
        $this->put(route('admin.membri.update', $anna), $dati + ['categorie_riservate' => [$generale->id]])->assertSessionHasErrors('categorie_riservate.0');

        $this->put(route('admin.membri.update', $anna), $dati + ['categorie_riservate' => [$salute->id]])->assertRedirect();
        $this->assertSame([$salute->id], $anna->categorieRiservate()->pluck('document_categories.id')->all());

        $this->put(route('admin.membri.update', $anna), $dati)->assertRedirect();
        $this->assertSame(0, $anna->categorieRiservate()->count());
    }

    public function test_copertina_e_galleria_riservate_e_allegati_filtrati_per_socio(): void
    {
        $salute = $this->categoria('Salute');
        $aperto = $this->documento(AreaSoci::categoriaDocumenti(), 'Aperto');
        $chiuso = $this->documento($salute, 'Chiuso');

        $this->actingAs($this->admin(), 'admin')->post(route('admin.comunicazioni-soci.store'), [
            'title' => 'Avviso', 'body' => '<p>x</p>', 'published' => '1',
            'cover_image' => UploadedFile::fake()->image('c.jpg', 800, 600),
            'gallery_images' => [UploadedFile::fake()->image('g.jpg', 800, 600)],
            'attach_documents' => [$aperto->id, $chiuso->id],
        ])->assertRedirect();
        auth('admin')->logout();

        $item = ComunicazioneSoci::firstOrFail();
        Storage::disk('local')->assertExists($item->cover_image);
        Storage::disk('public')->assertMissing($item->cover_image);
        $foto = $item->images()->firstOrFail();
        Storage::disk('local')->assertExists($foto->path);

        // Ospite: nessun accesso alle immagini; socio: sì, e vede solo gli allegati che può aprire.
        $this->get(route('soci.comunicazioni.copertina', $item))->assertRedirect(route('soci.login'));
        $this->get(route('soci.comunicazioni.immagine', $foto))->assertRedirect(route('soci.login'));

        $this->actingAs($this->member(), 'member');
        $this->get(route('soci.comunicazioni.copertina', $item))->assertOk();
        $this->get(route('soci.comunicazioni.immagine', $foto))->assertOk();
        $this->get(route('soci.comunicazioni.show', $item))->assertOk()->assertSee('Aperto')->assertDontSee('Chiuso');

        // Eliminare la comunicazione cancella le immagini ma non i documenti allegati.
        $this->actingAs($this->admin(), 'admin')->delete(route('admin.comunicazioni-soci.destroy', $item))->assertRedirect();
        Storage::disk('local')->assertMissing($foto->path);
        $this->assertSame(2, Document::count());
    }

    public function test_non_si_allega_un_documento_non_riservato(): void
    {
        $pubblica = DocumentCategory::create(['name' => 'Pub', 'slug' => 'pub', 'scope' => 'library']);
        $doc = Document::create(['document_category_id' => $pubblica->id, 'title' => 'Pubblico', 'file_path' => 'documenti/pubblico.pdf', 'published' => true]);

        $this->actingAs($this->admin(), 'admin')->post(route('admin.comunicazioni-soci.store'), [
            'title' => 'Avviso', 'published' => '1', 'attach_documents' => [$doc->id],
        ])->assertSessionHasErrors('attach_documents.0');
    }

    public function test_registro_download_solo_admin(): void
    {
        $editor = Admin::create(['name' => 'Ed', 'email' => 'ed@example.test', 'password' => 'x', 'role' => 'editor', 'active' => true,
            'permissions' => ['comunicazioni_soci' => ['read', 'write']]]);
        $this->actingAs($editor, 'admin')->get(route('admin.registro-download'))->assertForbidden();

        $doc = $this->documento(AreaSoci::categoriaDocumenti(), 'Regolamento');
        DocumentoAccesso::record($doc, $this->member(), DocumentoAccesso::DOWNLOAD, request());

        $this->actingAs($this->admin(), 'admin')->get(route('admin.registro-download'))
            ->assertOk()->assertSee('Rossi Anna')->assertSee('Regolamento')->assertSee('Download');
    }

    public function test_seeder_aggiorna_il_default_dell_informativa_e_non_tocca_l_override(): void
    {
        $default = PrivacyPolicy::create(['tipo' => 'soci', 'is_default' => true, 'titolo' => 'Soci', 'testo' => '<p>vecchio</p>']);
        $override = PrivacyPolicy::create(['tipo' => 'soci', 'is_default' => false, 'titolo' => 'Mio', 'testo' => '<p>personalizzato</p>']);

        $this->seed(AggiornaInformativaSociDocumentiSeeder::class);

        $this->assertStringContainsString('Registro dei documenti riservati', $default->fresh()->testo);
        $this->assertSame('<p>personalizzato</p>', $override->fresh()->testo);
    }
}
