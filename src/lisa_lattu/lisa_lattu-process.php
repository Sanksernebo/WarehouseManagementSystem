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

    $sql = "INSERT INTO Ladu (Tootekood, Nimetus, Kogus, Sisseost, Jaehind, Lopphind, Ost, Olek) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt === false) {
        die('Sisestamine ebaõnnestus.');
    }

    mysqli_stmt_bind_param($stmt, 'ssiddiss', $_POST['Tootekood'], $_POST['Nimetus'], $_POST['Kogus'], $_POST['Sisseost'], $_POST['Jaehind'], $_POST['Lopphind'], $_POST['ost'], $_POST['olek']);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        header("Location: ../../index.php");
        exit;
    } else {
        $message = "Sisestamine ebaõnnestus.";
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Toote Sisestus</title>
    <link rel="icon" type="image/x-icon" href="../img/cartehniklogo_svg.svg">
    <link rel="stylesheet" href="../../style.css">
</head>

<body>
<?php require_once '../includes/nav.php'; ?>

    <?php if (!empty($message)): ?>
        <p style="font-weight:bold;"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
</body>

</html>
<?php mysqli_close($conn); ?>
