@extends('adminlte::page')

@section('title', 'Post')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Post</h1>
        @if (Auth::guard('admin')->user()->hasPermission('posts', 'write'))
            <a href="{{ route('admin.posts.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuovo post
            </a>
        @endif
    </div>
@stop

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="GET" class="form-row align-items-end mb-3">
        <div class="col-md-4 mb-2">
            <label class="mb-1 text-muted small">Cerca (titolo o slug)</label>
            <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Cerca…">
        </div>
        <div class="col-md-3 mb-2">
            <label class="mb-1 text-muted small">Stato</label>
            <select name="stato" class="form-control">
                <option value="">Tutti</option>
                <option value="pubblicati" @selected($filters['stato'] === 'pubblicati')>Solo pubblicati</option>
                <option value="bozze" @selected($filters['stato'] === 'bozze')>Solo bozze</option>
            </select>
        </div>
        <div class="col-md-3 mb-2">
            <label class="mb-1 text-muted small">Categoria</label>
            <select name="categoria" class="form-control">
                <option value="">Tutte</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) $filters['categoria'] === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 mb-2">
            <div class="btn-group btn-block">
                <button type="submit" class="btn btn-secondary">Filtra</button>
                @if ($filters['q'] !== '' || $filters['stato'] !== '' || $filters['categoria'] !== '')
                    <a href="{{ route('admin.posts.index') }}" class="btn btn-outline-secondary" title="Azzera filtri">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="text-nowrap" style="width:1%"></th>
                        <th>Titolo</th>
                        <th>Categoria</th>
                        <th>Pubblicato</th>
                        <th>Data</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($posts as $post)
                        @php($canWrite = Auth::guard('admin')->user()->hasPermission('posts', 'write'))
                        <tr>
                            <td class="text-nowrap">
                                @if ($canWrite)
                                    <x-admin.action-button :href="route('admin.posts.edit', $post)" icon="fas fa-pen" label="Modifica" />
                                @else
                                    <x-admin.action-button :href="route('admin.posts.show', $post)" icon="fas fa-eye" label="Dettaglio" />
                                @endif
                                @if ($post->category)
                                    <x-admin.action-button :href="route('posts.show', [$post->category, $post])" target="_blank" icon="fas fa-external-link-alt" label="Anteprima" />
                                @endif
                            </td>
                            <td>{{ $post->title }}</td>
                            <td>
                                @if ($post->category)
                                    <span class="badge badge-secondary">{{ $post->category->name }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $post->published ? 'badge-success' : 'badge-secondary' }}">
                                    {{ $post->published ? 'Sì' : 'Bozza' }}
                                </span>
                            </td>
                            <td>{{ $post->published_at?->format('d/m/Y') }}</td>
                            <td class="text-right">
                                @if ($canWrite)
                                    <x-admin.danger-actions :action="route('admin.posts.destroy', $post)"
                                        confirm="Eliminare questo post?" />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Nessun post trovato.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $posts->links('pagination::bootstrap-4') }}
@stop
