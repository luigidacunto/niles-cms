@extends('adminlte::page')

@php $editing = $item->exists; @endphp

@section('title', $editing ? 'Modifica comunicazione' : 'Nuova comunicazione')

@section('content_header')
    <h1>{{ $editing ? 'Modifica comunicazione' : 'Nuova comunicazione' }}</h1>
@stop

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/summernote/summernote-bs4.min.css') }}">
@stop

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $editing ? route('admin.comunicazioni-soci.update', $item) : route('admin.comunicazioni-soci.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <div class="form-group">
                            <label>Titolo</label>
                            <input name="title" value="{{ old('title', $item->title) }}" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Estratto <small class="text-muted">(facoltativo)</small></label>
                            <textarea name="excerpt" id="excerpt" class="form-control" rows="2" maxlength="500">{{ old('excerpt', $item->excerpt) }}</textarea>
                            <small class="text-muted">Breve riassunto mostrato nell'elenco delle comunicazioni.</small>
                        </div>

                        <div class="form-group">
                            <label>Testo</label>
                            <textarea name="body" id="body" class="form-control" rows="14">{{ old('body', $item->body) }}</textarea>
                            <small class="text-muted">
                                ⚠️ Le immagini inserite nel testo non sono protette da accesso diretto: per il materiale
                                riservato usa gli <strong>allegati</strong> qui sotto, che sono realmente privati.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Allegati riservati</h3></div>
                    <div class="card-body">
                        @if ($item->attachments->isNotEmpty())
                            <table class="table table-sm mb-3">
                                <tbody>
                                    @foreach ($item->attachments as $doc)
                                        <tr>
                                            <td class="text-nowrap" style="width:1%"><i class="fas {{ $doc->fa_icon }} {{ $doc->bootstrap_color_class }} fa-lg"></i></td>
                                            <td>{{ $doc->title }}</td>
                                            <td style="width:5rem">
                                                <input type="number" min="0" class="form-control form-control-sm"
                                                       name="attachments[{{ $doc->id }}][order]" value="{{ old("attachments.{$doc->id}.order", $doc->pivot->order) }}">
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
                            <label>Aggiungi allegati</label>
                            <input type="file" name="allegati[]" multiple class="form-control-file"
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.odt,.ods,.odp,.txt,.csv">
                            <small class="text-muted">
                                PDF, Office, OpenDocument, testo, CSV (max 20 MB l'uno). Si scaricano solo da soci con accesso e
                                amministratori; eliminando la comunicazione (o l'allegato) il file viene cancellato.
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="form-group">
                            <label>Data pubblicazione</label>
                            <input type="datetime-local" name="published_at" class="form-control"
                                   value="{{ old('published_at', optional($item->published_at ?? now())->format('Y-m-d\TH:i')) }}">
                        </div>

                        <div class="custom-control custom-switch mb-3">
                            <input type="hidden" name="published" value="0">
                            <input type="checkbox" class="custom-control-input" id="published" name="published" value="1"
                                   @checked(old('published', $item->published ?? true))>
                            <label class="custom-control-label" for="published">Pubblicata</label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">{{ $editing ? 'Salva' : 'Crea comunicazione' }}</button>
                        <a href="{{ route('admin.comunicazioni-soci.index') }}" class="btn btn-link btn-block">Annulla</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@stop

@section('js')
    @include('admin.partials.wysiwyg')
@stop
