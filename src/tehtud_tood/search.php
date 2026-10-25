<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}
include_once '../db/laoseis.php';

$q      = isset($_GET['q']) ? (string)$_GET['q'] : '';
$arve   = isset($_GET['arve']) ? (string)$_GET['arve'] : '';
$offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
$limit  = 51;

$select_and_from =
    "SELECT too_id,
            UPPER(RegNr) AS RegNr,
            DATE_FORMAT(Kuupaev, '%d.%m.%Y %H:%i') AS FormattedDate,
            Odomeeter, Tehtud_tood, Arve_staatus
     FROM Tehtud_tood";

$where  = [];
$types  = '';
$params = [];

if ($q !== '') {
    $like = '%' . $q . '%';
    $where[] = "(RegNr       LIKE ?
              OR Tehtud_tood LIKE ?
              OR DATE_FORMAT(Kuupaev, '%d.%m.%Y %H:%i') LIKE ?)";
    $types  .= 'sss';
    array_push($params, $like, $like, $like);
}

// Invoice filter: only jobs marked as needing an invoice.
if ($arve === 'arvega') {
    $where[] = "Arve_staatus <> 'puudub'";
}

$sql = $select_and_from
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . ' ORDER BY Kuupaev DESC LIMIT ? OFFSET ?';
$types .= 'ii';
array_push($params, $limit, $offset);

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) { http_response_code(500); exit; }
mysqli_stmt_bind_param($stmt, $types, ...$params);
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
