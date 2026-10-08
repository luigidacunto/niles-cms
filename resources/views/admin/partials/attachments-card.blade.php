{{-- Card "Allegati": documenti della Libreria collegati a `$model` (Post o Page). Condivisa tra
     admin/posts/form.blade.php e admin/pages/form.blade.php — vedi App\Models\Concerns\HasDocumentAttachments
     e App\Http\Controllers\Admin\Concerns\ManagesAttachments. --}}
@php($existingIds = $model->attachments->pluck('id'))
<div class="card">
    <div class="card-header"><h3 class="card-title">Allegati</h3></div>
    <div class="card-body">
        @if ($model->attachments->isNotEmpty())
            <table class="table table-sm mb-3">
                <tbody>
                    @foreach ($model->attachments as $doc)
                        <tr>
                            <td class="text-nowrap" style="width:1%">
                                <i class="fas {{ $doc->fa_icon }} {{ $doc->bootstrap_color_class }} fa-lg"></i>
                            </td>
                            <td>{{ $doc->title }}</td>
                            <td style="width:5rem">
                                <input type="number" min="0" class="form-control form-control-sm"
                                       name="attachments[{{ $doc->id }}][order]"
                                       value="{{ old("attachments.{$doc->id}.order", $doc->pivot->order) }}">
                            </td>
                            <td class="text-nowrap text-right" style="width:1%">
                                <label class="mb-0 text-danger small">
                                    <input type="checkbox" name="attachments[{{ $doc->id }}][delete]" value="1"> elimina
                                </label>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="form-group mb-0">
            <label>Aggiungi allegato</label>
            <select name="attach_documents[]" multiple class="form-control" size="5">
                @foreach ($availableDocuments as $doc)
                    @unless ($existingIds->contains($doc->id))
                        <option value="{{ $doc->id }}">{{ $doc->title }} ({{ strtoupper($doc->extension) }})</option>
                    @endunless
                @endforeach
            </select>
            <small class="text-muted">
                Solo documenti già presenti in Libreria. Per allegarne uno nuovo, caricalo prima in
                <a href="{{ route('admin.documents.index') }}" target="_blank">Libreria documenti</a>, poi torna qui.
                Compare in fondo alla pagina pubblica con un'icona automatica per tipo di file.
            </small>
        </div>
    </div>
</div>
