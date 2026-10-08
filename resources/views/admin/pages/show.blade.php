@extends('adminlte::page')

@section('title', 'Dettaglio pagina')

@section('content_header')
    <h1>{{ $page->title }}</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    @if ($page->excerpt)
                        <div class="form-group">
                            <label class="text-muted">Estratto</label>
                            <p>{{ $page->excerpt }}</p>
                        </div>
                    @endif

                    <div class="form-group">
                        <label class="text-muted">Testo</label>
                        <div>{!! $page->body !!}</div>
                    </div>

                    @if ($page->attachments->isNotEmpty())
                        <div class="form-group mb-0">
                            <label class="text-muted">Allegati ({{ $page->attachments->count() }})</label>
                            <ul class="list-unstyled mb-0">
                                @foreach ($page->attachments as $doc)
                                    <li><i class="fas {{ $doc->fa_icon }} {{ $doc->bootstrap_color_class }}"></i> {{ $doc->title }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="form-group">
                        <label class="text-muted">Slug</label>
                        <p><code>{{ $page->slug }}</code></p>
                    </div>

                    @if ($page->parent)
                        <div class="form-group">
                            <label class="text-muted">Pagina genitore</label>
                            <p>{{ $page->parent->title }}</p>
                        </div>
                    @endif

                    <div class="form-group">
                        <label class="text-muted">Nel menu</label>
                        <p>
                            @if ($page->in_menu)
                                <span class="badge badge-info">Sì</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </p>
                    </div>

                    <div class="form-group">
                        <label class="text-muted">Stato</label>
                        <p>
                            <span class="badge {{ $page->published ? 'badge-success' : 'badge-secondary' }}">
                                {{ $page->published ? 'Pubblicata' : 'Bozza' }}
                            </span>
                        </p>
                    </div>

                    <a href="{{ route('admin.pages.index') }}" class="btn btn-link btn-block">Torna all'elenco</a>
                </div>
            </div>
        </div>
    </div>
@stop
