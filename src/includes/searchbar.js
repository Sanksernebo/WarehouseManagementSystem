// Shared searchbar client.
// Called from a list page's <script> tag with:
//   initSearchbar({
//     endpoint:    'search.php',   // relative URL
//     inputId:     'searchBar',
//     tbodyId:     'tableBody',    // <tbody id="tableBody"> on the list page
//     loadMoreId:  'loadMore',
//     errorId:     'searchError',
//   });
window.initSearchbar = function (config) {
    var input     = document.getElementById(config.inputId);
    var tbody     = document.getElementById(config.tbodyId);
    var errorBox  = document.getElementById(config.errorId);

    // The "Laadi veel" button belongs under the loaded rows, not the
    // searchbar, so create it right after the table that owns the tbody.
    var loadMore  = document.getElementById(config.loadMoreId);
    if (!loadMore && tbody) {
        loadMore = document.createElement('button');
        loadMore.id = config.loadMoreId;
        loadMore.type = 'button';
        loadMore.className = 'lisa-link';
        loadMore.textContent = 'Laadi veel';
        var table = tbody.closest('table') || tbody.parentElement;
        table.insertAdjacentElement('afterend', loadMore);
    }

    if (!input || !tbody || !loadMore || !errorBox) return;

    var currentQuery   = '';
    function countDataRows() {
        return tbody.querySelectorAll('tr:not(.empty-state)').length;
    }
    var initialRowCount = countDataRows();
    var currentOffset  = initialRowCount;
    var debounceTimer  = null;

    // Specific Estonian messages per failure mode. Screen readers announce
    // the populated errorBox via aria-live (see searchbar_init.php), so the
    // message itself carries the diagnostic — users do not need to open
    // devtools to know what went wrong.
    var ERROR_MESSAGES = {
        network: 'Ühendus puudub. Kontrollige internetiühendust.',
        unauthorized: 'Sessioon on aegunud. Palun logige uuesti sisse.',
        server: 'Serveri viga. Palun proovige hiljem uuesti.',
        generic: 'Otsing ebaõnnestus. Palun proovige uuesti.'
    };
    function showError(kind) {
        errorBox.textContent = ERROR_MESSAGES[kind] || ERROR_MESSAGES.generic;
    }
    function clearError() {
        errorBox.textContent = '';
    }

    function fetchRows(query, offset, append) {
        var url = config.endpoint
            + '?q=' + encodeURIComponent(query)
            + '&offset=' + offset;
        return fetch(url, { credentials: 'same-origin' })
            .then(function (resp) {
                if (!resp.ok) {
                    var err = new Error('HTTP ' + resp.status);
                    err.status = resp.status;
                    throw err;
                }
                return resp.json();
            })
            .then(function (data) {
                clearError();
                if (append) {
                    tbody.insertAdjacentHTML('beforeend', data.rows_html);
                } else if (data.rows_html === '') {
                    var headerRow = tbody.parentElement.querySelector('thead tr');
                    var colspan = headerRow ? headerRow.children.length : 1;
                    tbody.innerHTML =
                        '<tr class="empty-state"><td colspan="' + colspan +
                        '"><p style="font-weight:bold">Tulemusi ei leitud</p></td></tr>';
                } else {
                    tbody.innerHTML = data.rows_html;
                }
                loadMore.style.display = data.has_more ? '' : 'none';
            })
            .catch(function (err) {
                console.error('searchbar fetch failed:', err);
                var kind = 'generic';
                if (err && err.status === 401) {
                    kind = 'unauthorized';
                } else if (err && err.status >= 500) {
                    kind = 'server';
                } else if (err && err.name === 'TypeError') {
                    // fetch() throws TypeError for network failures (offline,
                    // DNS failure, CORS rejection). Status codes never reach
                    // here.
                    kind = 'network';
                }
                showError(kind);
            });
    }

    input.addEventListener('input', function () {
        if (debounceTimer) clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            currentQuery  = input.value;
            currentOffset = 0;
            fetchRows(currentQuery, 0, false).then(function () {
                // After a fresh query, currentOffset tracks rows now in DOM.
                currentOffset = countDataRows();
            });
        }, 250);
    });

    loadMore.addEventListener('click', function () {
        fetchRows(currentQuery, currentOffset, true).then(function () {
            currentOffset = countDataRows();
        });
    });

    // The server tells us has_more=true only via AJAX. On initial paint we
    // prefer an explicit initialHasMore flag from the caller; otherwise fall
    // back to the heuristic of "show if the page rendered a full page of 50".
    if (config.initialHasMore === true) {
        loadMore.style.display = '';
    } else if (config.initialHasMore === false) {
        loadMore.style.display = 'none';
    } else {
        // Caller didn't supply initialHasMore — fall back to old heuristic.
        loadMore.style.display = (initialRowCount >= 50) ? '' : 'none';
    }
};
