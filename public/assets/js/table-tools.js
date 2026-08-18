/**
 * Adds a search box and click-to-sort headers to every listing table
 * (any <table> with a <thead>, wrapped in .table-scroll) without touching
 * the server-rendered markup or requiring a page reload. Key/value detail
 * tables (no <thead>) are left alone on purpose.
 */
(function () {
    function normalize(text) {
        return text.trim().toLowerCase();
    }

    function enhanceTable(thead) {
        var table = thead.closest('table');
        var wrap = table.closest('.table-scroll');
        if (!wrap) return;
        var card = wrap.parentElement;
        var tbody = table.querySelector('tbody');
        if (!tbody) return;

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

        var allRows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));

        function updateCount() {
            var visible = allRows.filter(function (row) { return row.style.display !== 'none'; }).length;
            count.textContent = input.value ? visible + ' de ' + allRows.length : '';
        }

        input.addEventListener('input', function () {
            var q = normalize(input.value);
            allRows.forEach(function (row) {
                row.style.display = normalize(row.textContent).indexOf(q) !== -1 ? '' : 'none';
            });
            updateCount();
        });

        var ths = Array.prototype.slice.call(thead.querySelectorAll('th'));
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
            }

            th.addEventListener('click', sort);
            th.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    sort();
                }
            });
        });
    }

    document.querySelectorAll('.table-scroll table thead').forEach(enhanceTable);
})();
