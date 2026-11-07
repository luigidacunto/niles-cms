{{--
    Riquadro numerico della dashboard. Grande (small-box AdminLTE, con link facoltativo in basso) oppure piccolo (info-box,
    tutto cliccabile se c'è un link). Dentro una riga .dash-row si allunga alla colonna: un grande occupa lo stesso spazio
    di due piccoli impilati, con o senza link (CSS in public/css/admin-ui.css).
--}}
@props(['numero', 'testo', 'icona', 'colore' => 'secondary', 'url' => null, 'link' => 'Gestisci', 'piccolo' => false])

@if ($piccolo)
    @if ($url)<a href="{{ $url }}" class="dash-link">@endif
    <div class="info-box">
        <span class="info-box-icon bg-{{ $colore }}"><i class="fas {{ $icona }}"></i></span>
        <div class="info-box-content">
            <span class="info-box-text" title="{{ $testo }}">{{ $testo }}</span>
            <span class="info-box-number">{{ $numero }}</span>
        </div>
    </div>
    @if ($url)</a>@endif
@else
    <div class="small-box bg-{{ $colore }}">
        <div class="inner">
            <h3>{{ $numero }}</h3>
            <p>{{ $testo }}</p>
        </div>
        <div class="icon"><i class="fas {{ $icona }}"></i></div>
        @if ($url)
            <a href="{{ $url }}" class="small-box-footer">{{ $link }} <i class="fas fa-arrow-circle-right"></i></a>
        @endif
    </div>
@endif
