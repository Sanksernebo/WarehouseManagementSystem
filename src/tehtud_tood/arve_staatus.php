<?php
// Moves a job's invoice to its next status (koostamata -> saadetud -> makstud).
// Called by the invoice icon on the Tehtud Tööd page. Answers with JSON when
// requested via fetch, otherwise redirects back to the list.
session_start();
$wants_json = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;

if (!isset($_SESSION['user_id'])) {
    if ($wants_json) { http_response_code(401); exit; }
    header("Location: ../login/login.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
include_once '../db/laoseis.php';
require_once '../includes/csrf.php';
require_once '_arve.php';
csrf_verify();

$too_id = isset($_POST['too_id']) ? (int)$_POST['too_id'] : 0;

$stmt = mysqli_prepare($conn, "SELECT Arve_staatus FROM Tehtud_tood WHERE too_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $too_id);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$row) {
    http_response_code(404);
    exit;
}

$staatus = $row['Arve_staatus'];
if (isset(ARVE_JARGMINE[$staatus])) {
    $uus = ARVE_JARGMINE[$staatus];
    // Only advance from the status we read, in case another request changed it meanwhile.
    $stmt = mysqli_prepare($conn, "UPDATE Tehtud_tood SET Arve_staatus = ? WHERE too_id = ? AND Arve_staatus = ?");
    mysqli_stmt_bind_param($stmt, 'sis', $uus, $too_id, $staatus);
    if (!mysqli_stmt_execute($stmt)) {
        http_response_code(500);
        exit;
    }
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        $staatus = $uus;
    }
    mysqli_stmt_close($stmt);
}

if ($wants_json) {
    header('Content-Type: application/json');
    echo json_encode(['staatus' => $staatus, 'html' => arve_ikoon($too_id, $staatus)]);
    exit;
}
header("Location: tehtud_tood.php");
exit;
