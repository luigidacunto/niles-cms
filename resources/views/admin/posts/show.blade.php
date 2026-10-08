@extends('adminlte::page')

@section('title', 'Dettaglio post')

@section('content_header')
    <h1>{{ $post->title }}</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    @if ($post->subtitle)
                        <h5 class="text-muted">{{ $post->subtitle }}</h5>
                    @endif

                    @if ($post->excerpt)
                        <div class="form-group">
                            <label class="text-muted">Estratto</label>
                            <p>{{ $post->excerpt }}</p>
                        </div>
                    @endif

                    <div class="form-group">
                        <label class="text-muted">Testo</label>
                        <div>{!! $post->body !!}</div>
                    </div>

                    @if ($post->images->isNotEmpty())
                        <div class="form-group mb-0">
                            <label class="text-muted">Galleria ({{ $post->images->count() }})</label>
                            <div class="row">
                                @foreach ($post->images as $image)
                                    <div class="col-4 col-md-3 mb-2">
                                        <img src="{{ $image->url }}" class="img-fluid rounded" alt="">
                                        @if ($image->caption)
                                            <small class="text-muted d-block">{{ $image->caption }}</small>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($post->attachments->isNotEmpty())
                        <div class="form-group mb-0">
                            <label class="text-muted">Allegati ({{ $post->attachments->count() }})</label>
                            <ul class="list-unstyled mb-0">
                                @foreach ($post->attachments as $doc)
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
                        <label class="text-muted">Categoria</label>
                        <p>
                            @if ($post->category)
                                <span class="badge badge-secondary">{{ $post->category->name }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </p>
                    </div>

                    @if ($post->tags->isNotEmpty())
                        <div class="form-group">
                            <label class="text-muted">Tag</label>
                            <p>{{ $post->tags->pluck('name')->implode(', ') }}</p>
                        </div>
                    @endif

                    <div class="form-group">
                        <label class="text-muted">Copertina</label>
                        <img src="{{ $post->cover_url }}" class="img-fluid rounded" alt="">
                    </div>

                    <div class="form-group">
                        <label class="text-muted">Stato</label>
                        <p>
                            <span class="badge {{ $post->published ? 'badge-success' : 'badge-secondary' }}">
                                {{ $post->published ? 'Pubblicato' : 'Bozza' }}
                            </span>
                        </p>
                    </div>

                    <div class="form-group">
                        <label class="text-muted">Data pubblicazione</label>
                        <p>{{ $post->published_at?->format('d/m/Y H:i') ?? '—' }}</p>
                    </div>

                    <a href="{{ route('admin.posts.index') }}" class="btn btn-link btn-block">Torna all'elenco</a>
                </div>
            </div>
        </div>
    </div>
@stop
