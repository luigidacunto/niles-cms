@extends('adminlte::page')

@section('title', 'Struttura organizzativa')

@section('content_header')
    <h1>Struttura organizzativa</h1>
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
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <p class="text-muted">
        Componi qui la struttura. Presidente e vice presidente hanno un blocco fisso; sotto puoi
        aggiungere altri blocchi (consiglieri, consigliere giovani, referenti di attività…) indicando
        il ruolo a testo libero. Un blocco senza nome viene rimosso. La foto è facoltativa; nome e
        presentazione sono obbligatori per ogni blocco compilato.
        @if ($page)
            Il testo introduttivo della pagina si modifica da
            <a href="{{ route('admin.pages.edit', $page) }}">Pagine → Struttura Organizzativa</a>.
        @endif
    </p>

    <form method="POST" action="{{ route('admin.struttura-organizzativa.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- Slot fissi --}}
        @foreach (['president' => ['Presidente', $president], 'vice_president' => ['Vice Presidente', $vice]] as $slot => [$label, $member])
            <div class="card">
                <div class="card-header"><strong>{{ $label }}</strong></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label>Nome</label>
                            <input name="slots[{{ $slot }}][name]" value="{{ old("slots.$slot.name", $member?->name) }}" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Presentazione</label>
                        <textarea name="slots[{{ $slot }}][bio]" class="form-control bio-field" rows="5" data-max="1000">{{ old("slots.$slot.bio", $member?->bio) }}</textarea>
                        <small class="text-muted"><span class="bio-count">0</span>/1000 circa</small>
                    </div>
                    <div class="form-group">
                        <label>Foto <small class="text-muted">(facoltativa)</small></label>
                        @if ($member?->photo_path)
                            <div class="mb-1">
                                <img src="{{ $member->photoUrl }}" alt="" style="height:70px" class="rounded">
                                <label class="ml-2 mb-0"><input type="checkbox" name="slots[{{ $slot }}][remove_photo]" value="1"> togli foto</label>
                            </div>
                        @endif
                        <input type="file" name="slots[{{ $slot }}][photo]" accept="image/*" class="form-control-file">
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Blocchi liberi --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Altri blocchi</strong>
                <button type="button" class="btn btn-sm btn-outline-primary" id="add-member">
                    <i class="fas fa-plus"></i> Aggiungi blocco
                </button>
            </div>
            <div class="card-body" id="members-list">
                @foreach ($members as $i => $member)
                    @include('admin.struttura-organizzativa._member-row', ['i' => $i, 'member' => $member])
                @endforeach
            </div>
        </div>

        {{-- Organigramma --}}
        <div class="card">
            <div class="card-header"><strong>Organigramma <small class="text-muted">(facoltativo)</small></strong></div>
            <div class="card-body">
                @if ($organigramma)
                    <p class="mb-1">
                        Allegato attuale:
                        <a href="{{ $organigramma->downloadUrl }}" target="_blank" rel="noopener">
                            {{ $organigramma->original_filename ?? $organigramma->download_name }}
                        </a>
                        <label class="ml-2 mb-0"><input type="checkbox" name="remove_organigramma" value="1"> togli dalla pagina</label>
                    </p>
                    <small class="text-muted d-block mb-2">Togliendolo resta comunque nella Libreria documenti (categoria "Organigramma") come storico.</small>
                @endif
                <input type="file" name="organigramma" class="form-control-file">
                <small class="text-muted">PDF, Word, Excel, PowerPoint. Caricare un nuovo file sostituisce quello mostrato sulla pagina.</small>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Salva</button>
    </form>

    {{-- Template per una nuova riga blocco libero --}}
    <template id="member-row-template">
        @include('admin.struttura-organizzativa._member-row', ['i' => '__INDEX__', 'member' => null])
    </template>
@stop

@section('js')
    <script>
        $(function () {
            function bindBioCounter($el) {
                var max = $el.data('max') || 1000;
                var $count = $el.closest('.form-group').find('.bio-count');
                var upd = function () { $count.text($el.val().length); $count.toggleClass('text-danger', $el.val().length > max); };
                $el.on('input', upd); upd();
            }
            $('.bio-field').each(function () { bindBioCounter($(this)); });

            var next = {{ $members->count() }};
            $('#add-member').on('click', function () {
                var html = $('#member-row-template').html().replace(/__INDEX__/g, next++);
                var $row = $(html);
                $('#members-list').append($row);
                $row.find('.bio-field').each(function () { bindBioCounter($(this)); });
            });

            // "Rimuovi blocco": nasconde la riga e segna il checkbox remove (per le esistenti).
            $('#members-list').on('click', '.remove-member-row', function () {
                var $row = $(this).closest('.member-row');
                if ($row.find('input[name$="[id]"]').val()) {
                    $row.find('input[name$="[remove]"]').prop('checked', true);
                    $row.addClass('d-none');
                } else {
                    $row.remove();
                }
            });
        });
    </script>
@stop
