<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}
include_once '../db/laoseis.php';
require_once '../includes/csrf.php';

$message = '';

if (isset($_POST['submit'])) {
    csrf_verify();

    $RegNr   = $_POST['RegNr'];
    $Kuupaev = $_POST['Kuupaev'];
    $Kogus   = $_POST['Kogus'];
    $Moot    = $_POST['Moot'];
    $Tootja  = $_POST['Tootja'];
    $hooaeg  = $_POST['hooaeg'];
    $tarnija = $_POST['tarnija'];

    $sql = "INSERT INTO Rehvi_myyk (RegNr, Moot, Tootja, Kogus, Hooaeg, Tarnija, Kuupaev) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssss", $RegNr, $Moot, $Tootja, $Kogus, $hooaeg, $tarnija, $Kuupaev);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        header("Location: rehv_myyk.php");
        exit;
    } else {
        $message = "Sisestamine ebaõnnestus.";
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
    <meta charset="utf-8">
    <link rel="stylesheet" href="../../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="icon" type="image/x-icon" href="../img/cartehniklogo_svg.svg">
    <title>Rehvi Müügi Sisestus</title>
</head>

<body>
<?php require_once '../includes/nav.php'; ?>

    <?php if (!empty($message)): ?>
        <p style="font-weight:bold;"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
</body>

</html>
