{{-- Editor WYSIWYG condiviso da post e pagine (Summernote + picker documenti dalla libreria).
     Va incluso dentro @section('js'). Il CSS di Summernote resta nel @section('css') di ogni form. --}}
<script src="{{ asset('vendor/summernote/summernote-bs4.min.js') }}"></script>
<script src="{{ asset('vendor/summernote/summernote-it-IT.min.js') }}"></script>

<div class="modal fade" id="documentPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Inserisci un documento dalla libreria</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Chiudi"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="text" id="documentPickerSearch" class="form-control mb-3" placeholder="Cerca per titolo…" autocomplete="off">
                <div id="documentPickerList" class="list-group"></div>
                <p id="documentPickerEmpty" class="text-muted text-center py-3 d-none">Nessun documento pubblicato in libreria.</p>
            </div>
        </div>
    </div>
</div>

<script>
    $(function () {
        // Contatore caratteri estratto (post e pagine hanno lo stesso campo).
        var $excerpt = $('#excerpt');
        if ($excerpt.length) {
            var $count = $('#excerpt-count');
            var updateExcerptCount = function () { $count.text($excerpt.val().length); };
            $excerpt.on('input', updateExcerptCount);
            updateExcerptCount();
        }

        var pickerUrl = @json(route('admin.documents.picker'));
        var snContext = null;

        var renderDocs = function (docs) {
            var $list = $('#documentPickerList').empty();
            $('#documentPickerEmpty').toggleClass('d-none', docs.length > 0);
            docs.forEach(function (d) {
                var $a = $('<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"></button>');
                $a.append($('<span></span>').text(d.title));
                var meta = (d.category ? d.category + ' · ' : '') + (d.ext || '').toUpperCase();
                $a.append($('<small class="text-muted"></small>').text(meta));
                $a.on('click', function () {
                    var html = '<a href="' + d.url + '" target="_blank" rel="noopener">' + $('<span>').text(d.title).html() + '</a>';
                    if (snContext) {
                        snContext.invoke('editor.restoreRange');
                        snContext.invoke('editor.focus');
                        snContext.invoke('editor.pasteHTML', html);
                    }
                    $('#documentPickerModal').modal('hide');
                });
                $list.append($a);
            });
        };

        var loadDocs = function (q) {
            $.getJSON(pickerUrl, { q: q || '' }).done(renderDocs);
        };

        var searchTimer = null;
        $('#documentPickerSearch').on('input', function () {
            var q = this.value;
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () { loadDocs(q); }, 250);
        });
        $('#documentPickerModal').on('shown.bs.modal', function () {
            $('#documentPickerSearch').val('').trigger('focus');
            loadDocs('');
        });

        var insertDocumentButton = function (context) {
            var ui = $.summernote.ui;
            return ui.button({
                contents: '<i class="fas fa-paperclip"></i>',
                tooltip: 'Inserisci documento dalla libreria',
                click: function () {
                    snContext = context;
                    context.invoke('editor.saveRange');
                    $('#documentPickerModal').modal('show');
                }
            }).render();
        };

        $('#body').summernote({
            lang: 'it-IT',
            height: 350,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'picture', 'table', 'hr']],
                ['documento', ['insertDocument']],
                ['view', ['codeview']],
            ],
            buttons: { insertDocument: insertDocumentButton },
        });
    });
</script>
