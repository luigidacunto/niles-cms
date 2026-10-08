<?php

namespace App\Models;

use App\Models\Concerns\HasDocumentAttachments;
use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\PurifiesBody;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Page extends Model
{
    use HasDocumentAttachments;
    use HasUniqueSlug;
    use PurifiesBody;

    protected $fillable = [
        'parent_id', 'category_id', 'slug', 'title', 'excerpt', 'body', 'embed_html', 'template',
        'published', 'in_menu', 'order', 'system',
    ];

    protected $casts = [
        'published' => 'boolean',
        'in_menu' => 'boolean',
        'system' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'parent_id');
    }

    /**
     * Valorizzata solo sulle «pagine sezione» (pagine di sistema legate a una categoria di notizie, es. cosa-facciamo/*):
     * se presente, PageController::show() aggiunge le ultime notizie della categoria e un link all'archivio.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Page::class, 'parent_id');
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('published', true);
    }

    public function scopeInMenu(Builder $query): void
    {
        $query->where('in_menu', true);
    }

    public function scopeSystem(Builder $query): void
    {
        $query->where('system', true);
    }

    /**
     * Struttura del sito che una pagina di sistema NON permette di cambiare dal pannello
     * (parentela, slug, ordine, presenza nel menu, template): è fissata da seeder/codice.
     */
    public function structureLocked(): bool
    {
        return (bool) $this->system;
    }

    /**
     * Percorso dalla radice alla pagina, per il breadcrumb dinamico: nessun breadcrumb scritto a mano per
     * pagina, il layout pubblico chiama questo metodo per qualunque pagina stia mostrando. La profondità è di
     * pochi livelli, quindi l'N+1 di risalire ->parent un passo alla volta non è un problema.
     */
    public function breadcrumbTrail(): Collection
    {
        $trail = collect([$this]);
        $page = $this;

        while ($page->parent) {
            $page = $page->parent;
            $trail->prepend($page);
        }

        return $trail;
    }

    /**
     * Pagine di primo livello pubblicate e nel menu, con i figli pubblicati e nel menu già caricati: la
     * navigazione pubblica legge questo albero invece di ricostruirlo.
     */
    public static function menuTree(): Collection
    {
        return static::published()->inMenu()->whereNull('parent_id')
            ->orderBy('order')->orderBy('title')
            ->with(['children' => fn ($query) => $query->published()->inMenu()->orderBy('order')->orderBy('title')])
            ->get();
    }

    /**
     * Tutte le pagine (pubblicate o no, nel menu o no) come elenco piatto in ordine di profondità, con
     * l'attributo `depth` su ciascuna: l'indice admin lo usa per mostrare la vera struttura padre/figlio
     * (rientrata) invece di un elenco alfabetico che la nasconde. Non paginato: le pagine di un sito come
     * questo restano poche decine, e l'albero intero è più utile di una fetta alla volta, che separerebbe
     * a caso un padre dai suoi figli.
     */
    public static function orderedTree(): Collection
    {
        $byParent = static::orderBy('order')->orderBy('title')->get()->groupBy('parent_id');

        $flatten = function (?int $parentId, int $depth) use (&$flatten, $byParent): Collection {
            return ($byParent->get($parentId) ?? collect())->flatMap(function (self $page) use ($depth, $flatten) {
                $page->depth = $depth;

                return collect([$page])->merge($flatten($page->id, $depth + 1));
            });
        };

        return $flatten(null, 0);
    }
}
