<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Registro delle anteprime e dei download dei documenti riservati, per socio. Solo audit di sicurezza.
 * Conservazione 12 mesi: ogni scrittura cancella le righe più vecchie (nessuno scheduler da configurare sui
 * server, stesso meccanismo di LoginAudit).
 */
class DocumentoAccesso extends Model
{
    public const UPDATED_AT = null;

    public const RETENTION_MONTHS = 12;

    public const ANTEPRIMA = 'anteprima';

    public const DOWNLOAD = 'download';

    protected $table = 'documento_accessi';

    protected $fillable = ['document_id', 'document_title', 'member_id', 'member_label', 'event', 'ip'];

    public static function record(Document $document, Member $member, string $event, Request $request): void
    {
        static::where('created_at', '<', now()->subMonths(self::RETENTION_MONTHS))->delete();

        static::create([
            'document_id' => $document->id,
            'document_title' => $document->title,
            'member_id' => $member->id,
            'member_label' => $member->nomeCompleto(),
            'event' => $event,
            'ip' => $request->ip(),
        ]);
    }
}
