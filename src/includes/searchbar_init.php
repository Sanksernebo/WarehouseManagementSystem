<?php
// Emits the searchbar UI + JS for a list page. The list page sets these
// PHP variables before require_once:
//   $searchbar_endpoint    — relative URL of the page's search.php
//   $searchbar_placeholder — Estonian placeholder text
//   $searchbar_js_path     — relative URL of searchbar.js for this page
$endpoint    = isset($searchbar_endpoint)    ? $searchbar_endpoint    : 'search.php';
$placeholder = isset($searchbar_placeholder) ? $searchbar_placeholder : 'Otsi';
$js_path     = isset($searchbar_js_path)     ? $searchbar_js_path     : 'src/includes/searchbar.js';

// If the caller knows whether more rows are available beyond the initial
// paint, they can set $searchbar_initial_has_more to true/false. If unset,
// we emit JS `undefined` so the client falls back to its row-count heuristic.
$initial_has_more_js = isset($searchbar_initial_has_more)
    ? ($searchbar_initial_has_more ? 'true' : 'false')
    : 'undefined';
?>
<input type="text" id="searchBar" autocomplete="off" placeholder="<?php echo htmlspecialchars($placeholder); ?>">
<div id="searchError" class="search-error" role="status" aria-live="polite" aria-atomic="true"></div>
<button id="loadMore" type="button" class="lisa-link">Laadi veel</button>
<script src="<?php echo htmlspecialchars($js_path); ?>"></script>
<script>
    // The table (#tableBody) is rendered after this include, so wait for it.
    document.addEventListener('DOMContentLoaded', function () {
        initSearchbar({
            endpoint:   <?php echo json_encode($endpoint); ?>,
            inputId:    'searchBar',
            tbodyId:    'tableBody',
            loadMoreId: 'loadMore',
            errorId:    'searchError',
            initialHasMore: <?php echo $initial_has_more_js; ?>
        });
    });
</script>
