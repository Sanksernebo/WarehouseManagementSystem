<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}
include_once '../db/laoseis.php';

$q      = isset($_GET['q']) ? (string)$_GET['q'] : '';
$offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
$limit  = 51;

$select_and_from =
    "SELECT rehvimyyk_id, UPPER(RegNr) AS RegNr,
            DATE_FORMAT(Kuupaev, '%d.%m.%Y') AS FormattedDate,
            Kogus, UPPER(Moot) AS Moot, Tootja, Hooaeg, Tarnija
     FROM Rehvi_myyk";

if ($q === '') {
    $stmt = mysqli_prepare(
        $conn,
        "$select_and_from ORDER BY Kuupaev DESC LIMIT ? OFFSET ?"
    );
    if (!$stmt) { http_response_code(500); exit; }
    mysqli_stmt_bind_param($stmt, 'ii', $limit, $offset);
} else {
    $like = '%' . $q . '%';
    $stmt = mysqli_prepare(
        $conn,
        "$select_and_from
         WHERE RegNr   LIKE ?
            OR Moot    LIKE ?
            OR Tootja  LIKE ?
            OR Hooaeg  LIKE ?
            OR Tarnija LIKE ?
            OR DATE_FORMAT(Kuupaev, '%d.%m.%Y') LIKE ?
         ORDER BY Kuupaev DESC
         LIMIT ? OFFSET ?"
    );
    if (!$stmt) { http_response_code(500); exit; }
    mysqli_stmt_bind_param(
        $stmt, 'ssssssii',
        $like, $like, $like, $like, $like, $like, $limit, $offset
    );
}
if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    exit;
}
$result = mysqli_stmt_get_result($stmt);

$rows = [];
while ($row = mysqli_fetch_array($result)) {
    $rows[] = $row;
}
mysqli_stmt_close($stmt);

$has_more = count($rows) > 50;
if ($has_more) array_pop($rows);

ob_start();
foreach ($rows as $row) {
    include __DIR__ . '/_row.php';
}
$rows_html = ob_get_clean();

header('Content-Type: application/json');
echo json_encode(['rows_html' => $rows_html, 'has_more' => $has_more]);
