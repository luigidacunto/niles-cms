/*
 * Menu laterale del pannello: memoria dei gruppi aperti + ricerca sotto il logo. JS puro, nessuna dipendenza.
 * Il markup è quello di AdminLTE: <ul class="nav-sidebar"> con titoli fissi <li class="nav-header"> e gruppi richiudibili
 * <li class="nav-item"> che contengono un <ul class="nav-treeview"> con le pagine (apertura/chiusura gestita da AdminLTE).
 *
 * - Gruppi: lo stato aperto/chiuso è ricordato nel browser (per nome del gruppo). Al primo accesso sono tutti chiusi; il gruppo
 *   che contiene la pagina corrente si apre sempre (lo apre già AdminLTE, classe menu-open).
 * - Titoli: un titolo senza voci visibili sotto (permessi, ricerca) si nasconde.
 * - Ricerca (testo libero, senza distinzione di maiuscole/accenti): mostra solo le pagine che corrispondono, con il loro gruppo
 *   aperto; se il testo corrisponde al nome di un gruppo, il gruppo compare intero. Si azzera con la X, con Esc o cliccando una voce.
 */
(function () {
    'use strict';

    var CHIAVE = 'nilesMenuAperti';

    function normalizza(testo) {
        return (testo || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/\s+/g, ' ').trim();
    }

    function leggiAperti() {
        try { return JSON.parse(localStorage.getItem(CHIAVE) || '[]'); } catch (e) { return []; }
    }

    function salvaAperti(elenco) {
        try { localStorage.setItem(CHIAVE, JSON.stringify(elenco)); } catch (e) { /* storage non disponibile: funziona comunque, senza memoria */ }
    }

    function testoLink(a) {
        var p = a.querySelector('p');
        return (p ? p.firstChild && p.firstChild.textContent : a.textContent) || '';
    }

    function avvia() {
        var nav = document.querySelector('.nav-sidebar');
        var sidebar = document.querySelector('.sidebar');
        if (!nav || !sidebar) { return; }

        // Elementi di primo livello: titoli, voci singole (Dashboard) e gruppi con sottomenu.
        var elementi = Array.prototype.map.call(nav.children, function (li) {
            var sotto = li.querySelector(':scope > .nav-treeview');
            return {
                li: li,
                titolo: li.classList.contains('nav-header'),
                sotto: sotto,
                nome: li.classList.contains('nav-header') ? li.textContent.trim() : normalizza(testoLink(li.querySelector(':scope > a') || li)),
                voci: sotto ? Array.prototype.slice.call(sotto.children) : [],
            };
        }).filter(function (e) { return e.titolo || e.li.classList.contains('nav-item'); });

        // Memoria dei gruppi: si applica lo stato salvato (il gruppo con la pagina corrente resta aperto) e si registra ogni clic.
        var aperti = leggiAperti();
        elementi.forEach(function (e) {
            if (!e.sotto) { return; }
            if (aperti.indexOf(e.nome) !== -1) { e.li.classList.add('menu-open'); }
            e.li.querySelector(':scope > a').addEventListener('click', function () {
                if (ricerca) { return; }
                var apre = !e.li.classList.contains('menu-open');   // lo stato prima del clic: AdminLTE lo inverte subito dopo
                var i = aperti.indexOf(e.nome);
                if (apre && i === -1) { aperti.push(e.nome); }
                if (!apre && i !== -1) { aperti.splice(i, 1); }
                salvaAperti(aperti);
            });
        });

        // Casella di ricerca sotto il logo (il .sidebar è subito dopo il brand-link).
        var box = document.createElement('div');
        box.className = 'niles-menu-search';
        box.innerHTML = '<i class="fas fa-search niles-lente" aria-hidden="true"></i>' +
            '<input type="search" placeholder="Cerca nel menu…" aria-label="Cerca nel menu" autocomplete="off">' +
            '<button type="button" class="niles-azzera" aria-label="Azzera la ricerca" title="Azzera" hidden>&times;</button>';
        sidebar.insertBefore(box, sidebar.firstChild);
        var campo = box.querySelector('input');
        var azzera = box.querySelector('button');
        var ricerca = '';

        var vuoto = document.createElement('li');
        vuoto.className = 'niles-nessun-risultato';
        vuoto.textContent = 'Nessuna voce trovata';
        vuoto.hidden = true;
        nav.appendChild(vuoto);

        function disegna() {
            var q = ricerca;
            var trovati = 0;

            elementi.forEach(function (e) {
                if (e.titolo) { return; }
                if (!e.sotto) {
                    e.li.hidden = !!q && normalizza(e.li.textContent).indexOf(q) === -1;
                } else {
                    var perNome = q && e.nome.indexOf(q) !== -1;
                    var n = 0;
                    e.voci.forEach(function (v) {
                        var ok = !q || perNome || normalizza(v.textContent).indexOf(q) !== -1;
                        v.hidden = !ok;
                        if (ok) { n++; }
                    });
                    e.li.hidden = !!q && n === 0;
                    // In ricerca i gruppi con risultati si mostrano aperti senza toccare lo stato salvato; fuori ricerca decide la classe menu-open.
                    e.sotto.style.display = q ? (n ? 'block' : 'none') : (e.li.classList.contains('menu-open') ? 'block' : 'none');
                }
                if (!e.li.hidden) { trovati++; }
            });

            // Titoli: visibili solo se sotto (fino al titolo successivo) c'è almeno un elemento visibile.
            elementi.forEach(function (e, i) {
                if (!e.titolo) { return; }
                var visibile = false;
                for (var j = i + 1; j < elementi.length && !elementi[j].titolo; j++) {
                    if (!elementi[j].li.hidden) { visibile = true; break; }
                }
                e.li.hidden = !visibile;
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
        // Cliccare una pagina azzera la ricerca (la pagina successiva riparte pulita).
        nav.addEventListener('click', function (e) {
            var a = e.target.closest('a.nav-link');
            if (ricerca && a && !a.parentElement.querySelector(':scope > .nav-treeview')) { pulisci(); }
        });

        disegna();
    }

    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', avvia); } else { avvia(); }
})();
