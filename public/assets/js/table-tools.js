/**
 * Adds a search box, click-to-sort headers and pagination (10 rows per page)
 * to every listing table (any <table> with a <thead>, wrapped in .table-scroll)
 * without touching the server-rendered markup or requiring a page reload.
 * Key/value detail tables (no <thead>) are left alone on purpose.
 *
 * Search and sort always work on ALL rows; pagination only slices the result.
 * Search text, sorted column and current page are remembered per page for the
 * browser session, so saving in a modal (which reloads the list) keeps them.
 */
(function () {
    var PAGE_SIZE = 10;

    function normalize(text) {
        return text.trim().toLowerCase();
    }

    function storageKey(tableIndex) {
        var route = new URLSearchParams(window.location.search).get('r') || window.location.pathname;
        return 'bf-table:' + route + ':' + tableIndex;
    }

    // The page's server-side filters in a canonical form: the filter form sends
    // "r=a%2Fb%2Findex&dev=" while the post-save redirect sends "r=a/b/index",
    // so compare non-empty values, sorted, without the route itself.
    function currentFilters() {
        var pairs = [];
        new URLSearchParams(window.location.search).forEach(function (value, name) {
            if (name !== 'r' && value !== '') pairs.push(name + '=' + value);
        });
        return pairs.sort().join('&');
    }

    function loadState(key) {
        try {
            return JSON.parse(window.sessionStorage.getItem(key) || '{}') || {};
        } catch (e) {
            return {};
        }
    }

    function saveState(key, state) {
        try {
            window.sessionStorage.setItem(key, JSON.stringify(state));
        } catch (e) {
            // Storage unavailable (private mode, blocked): the tools still work, just unremembered.
        }
    }

    function enhanceTable(thead, tableIndex) {
        var table = thead.closest('table');
        var wrap = table.closest('.table-scroll');
        if (!wrap) return;
        var card = wrap.parentElement;
        var tbody = table.querySelector('tbody');
        if (!tbody) return;

        var key = storageKey(tableIndex);
        var state = loadState(key);
        var page = 0;

        var searchWrap = document.createElement('div');
        searchWrap.className = 'table-search';
        var input = document.createElement('input');
        input.type = 'search';
        input.placeholder = 'Buscar en esta tabla...';
        input.setAttribute('aria-label', 'Buscar en esta tabla');
        var count = document.createElement('span');
        count.className = 'table-search-count';
        searchWrap.appendChild(input);
        searchWrap.appendChild(count);
        card.insertBefore(searchWrap, wrap);

        var pager = document.createElement('div');
        pager.className = 'table-pager';
        var prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.className = 'table-pager-btn';
        prevBtn.textContent = '‹ Anterior';
        var info = document.createElement('span');
        info.className = 'table-pager-info';
        var nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.className = 'table-pager-btn';
        nextBtn.textContent = 'Siguiente ›';
        pager.appendChild(prevBtn);
        pager.appendChild(info);
        pager.appendChild(nextBtn);
        card.insertBefore(pager, wrap.nextSibling);

        // Row text is fixed once rendered, so normalise it once instead of on every keystroke.
        var searchText = new Map();
        function textOf(row) {
            if (!searchText.has(row)) searchText.set(row, normalize(row.textContent));
            return searchText.get(row);
        }

        function render() {
            var q = normalize(input.value);
            var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
            var matches = q ? rows.filter(function (row) { return textOf(row).indexOf(q) !== -1; }) : rows;
            var pages = Math.max(1, Math.ceil(matches.length / PAGE_SIZE));
            if (page > pages - 1) page = pages - 1;
            if (page < 0) page = 0;

            rows.forEach(function (row) { row.style.display = 'none'; });
            matches.slice(page * PAGE_SIZE, (page + 1) * PAGE_SIZE).forEach(function (row) { row.style.display = ''; });

            count.textContent = q ? matches.length + ' de ' + rows.length : '';
            pager.hidden = matches.length <= PAGE_SIZE;
            info.textContent = 'Página ' + (page + 1) + ' de ' + pages + ' · ' + matches.length + ' registros';
            prevBtn.disabled = page === 0;
            nextBtn.disabled = page >= pages - 1;

            state.page = page;
            saveState(key, state);
        }

        input.addEventListener('input', function () {
            state.q = input.value;
            page = 0;
            render();
        });
        prevBtn.addEventListener('click', function () { page -= 1; render(); });
        nextBtn.addEventListener('click', function () { page += 1; render(); });

        var ths = Array.prototype.slice.call(thead.querySelectorAll('th'));
        var sorters = [];
        ths.forEach(function (th, index) {
            if (!th.textContent.trim()) return;
            th.classList.add('sortable-th');
            th.setAttribute('role', 'button');
            th.setAttribute('tabindex', '0');

            function sort() {
                var dir = th.getAttribute('data-sort-dir') === 'asc' ? 'desc' : 'asc';
                ths.forEach(function (t) { t.removeAttribute('data-sort-dir'); });
                th.setAttribute('data-sort-dir', dir);

                var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
                rows.sort(function (a, b) {
                    var av = a.children[index] ? a.children[index].textContent.trim() : '';
                    var bv = b.children[index] ? b.children[index].textContent.trim() : '';
                    var an = parseFloat(av.replace(/[^0-9.\-]/g, ''));
                    var bn = parseFloat(bv.replace(/[^0-9.\-]/g, ''));
                    var bothNumeric = /^[\d.,\-\s%]+$/.test(av) && /^[\d.,\-\s%]+$/.test(bv) && !isNaN(an) && !isNaN(bn);
                    var cmp = bothNumeric ? (an - bn) : av.localeCompare(bv, 'es');
                    return dir === 'asc' ? cmp : -cmp;
                });
                rows.forEach(function (row) { tbody.appendChild(row); });

                state.col = index;
                state.dir = dir;
                page = 0;
                render();
            }

            sorters[index] = sort;
            th.addEventListener('click', sort);
            th.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    sort();
                }
            });
        });

        // Restore what the user had before the page reloaded: search, then sort, then page.
        // A different server-side filter means a different result set, so start at page 1.
        var filters = currentFilters();
        var savedPage = state.query === filters && typeof state.page === 'number' ? state.page : 0;
        state.query = filters;
        if (state.q) input.value = state.q;
        if (typeof state.col === 'number' && sorters[state.col]) {
            // sort() flips the current direction, so start from the opposite one.
            ths[state.col].setAttribute('data-sort-dir', state.dir === 'asc' ? 'desc' : 'asc');
            sorters[state.col]();
        }
        page = savedPage;
        render();
    }

    // "Limpiar filtros" (always inside form.filters) also forgets this page's
    // table search, sort and page, so it really returns to the unfiltered list.
    document.addEventListener('click', function (e) {
        if (!e.target.closest('form.filters a')) return;
        var prefix = storageKey('');
        try {
            Object.keys(window.sessionStorage).forEach(function (k) {
                if (k.indexOf(prefix) === 0) window.sessionStorage.removeItem(k);
            });
        } catch (err) {
            // Storage unavailable: nothing was remembered, nothing to clear.
        }
    });

    document.querySelectorAll('.table-scroll table thead').forEach(function (thead, index) {
        enhanceTable(thead, index);
    });
})();
