<?php
// Invoice (arve) status for a Tehtud_tood row, stored in Tehtud_tood.Arve_staatus.
//   puudub     - no invoice needed (no icon shown)
//   koostamata - invoice needed but not created yet (red)
//   saadetud   - invoice created and sent (orange)
//   makstud    - payment received (green)

const ARVE_STAATUSED = [
    'puudub'     => 'Arvet pole vaja',
    'koostamata' => 'Arve koostamata',
    'saadetud'   => 'Arve saadetud',
    'makstud'    => 'Arve makstud',
];

// Status the row icon moves to when clicked. 'makstud' is final; going back
// is done from the edit page so a stray click can't un-pay an invoice.
const ARVE_JARGMINE = [
    'koostamata' => 'saadetud',
    'saadetud'   => 'makstud',
];

function arve_staatus_kehtiv($staatus) {
    return is_string($staatus) && array_key_exists($staatus, ARVE_STAATUSED);
}

// Renders the invoice icon for one row. Requires csrf.php to be loaded.
function arve_ikoon($too_id, $staatus) {
    if ($staatus === 'puudub' || !arve_staatus_kehtiv($staatus)) {
        return '';
    }
    $label = ARVE_STAATUSED[$staatus];
    $next  = ARVE_JARGMINE[$staatus] ?? null;
    $title = $next ? $label . ' — klõpsa: ' . mb_strtolower(ARVE_STAATUSED[$next]) : $label;

    return '<form method="post" action="../../src/tehtud_tood/arve_staatus.php" class="arve-form">'
        . csrf_field()
        . '<input type="hidden" name="too_id" value="' . (int)$too_id . '">'
        . '<button type="submit" class="arve-button arve-' . $staatus . '"'
        . ' title="' . htmlspecialchars($title) . '" aria-label="' . htmlspecialchars($title) . '"'
        . ' data-staatus="' . $staatus . '"' . ($next ? '' : ' disabled') . '>'
        . '<i class="fa-solid fa-file-invoice fa-lg"></i>'
        . '</button>'
        . '</form>';
}
