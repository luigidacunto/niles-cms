/*
 * Convenzioni di interfaccia del pannello: select con ricerca (Select2). Ogni <select class="select-ricerca"> diventa una
 * select con campo di ricerca, tema Bootstrap 4, italiano. Si inizializza qui una volta sola, dopo jQuery e Select2.
 * L'evento `change` di Select2 passa da jQuery: gli attributi onchange="…" dei <select> continuano a funzionare.
 */
$(function () {
    $('.select-ricerca').select2({ theme: 'bootstrap4', language: 'it', width: '100%' });
});
