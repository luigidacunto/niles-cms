/*
 * Menu laterale del pannello: sezioni collassabili + ricerca sotto il logo. JS puro, nessuna dipendenza.
 * Il markup è quello di AdminLTE: <ul class="nav-sidebar"> con <li class="nav-header"> (titolo sezione) seguito dalle
 * sue <li class="nav-item"> fino al titolo successivo.
 *
 * - Clic sul titolo di una sezione: la chiude/apre (stato ricordato nel browser, per titolo). Al caricamento la sezione
 *   che contiene la pagina corrente si apre sempre (poi la si può richiudere a mano).
 * - Ricerca (testo libero, senza distinzione di maiuscole/accenti): nasconde le voci che non corrispondono e le sezioni
 *   senza risultati, e mostra aperte quelle con risultati. Si azzera con la X, con Esc o cliccando una voce.
 *   Se il testo corrisponde al nome di una sezione, la sezione compare intera.
 */
(function () {
    'use strict';

    var CHIAVE = 'nilesMenuChiuso';

    function normalizza(testo) {
        return (testo || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/\s+/g, ' ').trim();
    }

    function leggiChiusi() {
        try { return JSON.parse(localStorage.getItem(CHIAVE) || '[]'); } catch (e) { return []; }
    }

    function salvaChiusi(elenco) {
        try { localStorage.setItem(CHIAVE, JSON.stringify(elenco)); } catch (e) { /* storage non disponibile: funziona comunque, senza memoria */ }
    }

    function avvia() {
        var nav = document.querySelector('.nav-sidebar');
        var sidebar = document.querySelector('.sidebar');
        if (!nav || !sidebar) { return; }

        // Raggruppa: voci prima del primo titolo (Dashboard) + una sezione per ogni titolo.
        var iniziali = [];
        var gruppi = [];
        var corrente = null;
        Array.prototype.forEach.call(nav.children, function (li) {
            if (li.classList.contains('nav-header')) {
                corrente = { intestazione: li, nome: li.textContent.trim(), voci: [] };
                gruppi.push(corrente);
            } else if (li.classList.contains('nav-item')) {
                (corrente ? corrente.voci : iniziali).push(li);
            }
        });

        var chiusi = leggiChiusi();
        var ricerca = '';

        // Titoli cliccabili con freccia.
        gruppi.forEach(function (g) {
            var h = g.intestazione;
            h.classList.add('niles-sezione');
            h.setAttribute('role', 'button');
            h.setAttribute('tabindex', '0');
            var freccia = document.createElement('i');
            freccia.className = 'fas fa-chevron-down niles-freccia';
            h.appendChild(freccia);
            // La sezione con la pagina corrente si apre sempre al caricamento: così si vede dove si è.
            var attiva = g.voci.some(function (li) { return li.querySelector('a.nav-link.active'); });
            var i = chiusi.indexOf(g.nome);
            if (attiva && i !== -1) { chiusi.splice(i, 1); salvaChiusi(chiusi); }
            h.addEventListener('click', function () { alterna(g); });
            h.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); alterna(g); }
            });
        });

        function alterna(g) {
            var i = chiusi.indexOf(g.nome);
            if (i === -1) { chiusi.push(g.nome); } else { chiusi.splice(i, 1); }
            salvaChiusi(chiusi);
            disegna();
        }

        // Casella di ricerca sotto il logo (il .sidebar è subito dopo il brand-link).
        var box = document.createElement('div');
        box.className = 'niles-menu-search';
        box.innerHTML = '<i class="fas fa-search niles-lente" aria-hidden="true"></i>' +
            '<input type="search" placeholder="Cerca nel menu…" aria-label="Cerca nel menu" autocomplete="off">' +
            '<button type="button" class="niles-azzera" aria-label="Azzera la ricerca" title="Azzera" hidden>&times;</button>';
        sidebar.insertBefore(box, sidebar.firstChild);
        var campo = box.querySelector('input');
        var azzera = box.querySelector('button');

        var vuoto = document.createElement('li');
        vuoto.className = 'niles-nessun-risultato';
        vuoto.textContent = 'Nessuna voce trovata';
        vuoto.hidden = true;
        nav.appendChild(vuoto);

        function disegna() {
            var q = ricerca;
            var trovati = 0;

            iniziali.forEach(function (li) {
                var visibile = !q || normalizza(li.textContent).indexOf(q) !== -1;
                li.hidden = !visibile;
                if (visibile) { trovati++; }
            });

            gruppi.forEach(function (g) {
                var perNome = q && normalizza(g.nome).indexOf(q) !== -1;
                var corrispondenti = 0;
                g.voci.forEach(function (li) {
                    var ok = !q || perNome || normalizza(li.textContent).indexOf(q) !== -1;
                    if (q) {
                        li.hidden = !ok;                       // in ricerca: solo le voci che corrispondono
                        if (ok) { corrispondenti++; }
                    } else {
                        li.hidden = chiusi.indexOf(g.nome) !== -1;   // fuori ricerca: sezione chiusa
                    }
                });
                var chiusa = !q && chiusi.indexOf(g.nome) !== -1;
                g.intestazione.hidden = q ? corrispondenti === 0 : false;
                g.intestazione.classList.toggle('niles-chiusa', chiusa);
                g.intestazione.setAttribute('aria-expanded', chiusa ? 'false' : 'true');
                trovati += corrispondenti;
            });

            vuoto.hidden = !(q && trovati === 0);
            azzera.hidden = !campo.value;
        }

        function pulisci() {
            campo.value = '';
            ricerca = '';
            disegna();
        }

        campo.addEventListener('input', function () { ricerca = normalizza(campo.value); disegna(); });
        campo.addEventListener('keydown', function (e) { if (e.key === 'Escape') { pulisci(); } });
        azzera.addEventListener('click', function () { pulisci(); campo.focus(); });
        // Cliccare una voce azzera la ricerca (la pagina successiva riparte pulita).
        nav.addEventListener('click', function (e) { if (ricerca && e.target.closest('a.nav-link')) { pulisci(); } });

        disegna();
    }

    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', avvia); } else { avvia(); }
})();
