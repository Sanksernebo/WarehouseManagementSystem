<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}
include_once '../db/laoseis.php';
require_once '../includes/csrf.php';

$error = '';

if (isset($_POST['submit'])) {
    csrf_verify();

    $RegNr       = $_POST['RegNr'];
    $Kuupaev     = $_POST['Kuupaev'];
    $Odomeeter   = $_POST['Odomeeter'];
    $Tehtud_tood = $_POST['Tehtud_tood'];

    $sql = "INSERT INTO Tehtud_tood (RegNr, Kuupaev, Odomeeter, Tehtud_tood) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssis", $RegNr, $Kuupaev, $Odomeeter, $Tehtud_tood);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        header("Location: tehtud_tood.php");
        exit;
    } else {
        $error = "Sisestamine ebaõnnestus.";
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
    <link rel="icon" type="image/x-icon" href="../img/cartehniklogo_svg.svg">
    <title>Töö Sisestus</title>
</head>

<body>
<?php require_once '../includes/nav.php'; ?>

    <form name="frmUser" method="post" action="">
        <?= csrf_field() ?>
        <div><?php if (!empty($error)) echo htmlspecialchars($error); ?></div>
        <div style="padding-bottom:5px;">
            Reg.Nr: <br>
            <input type="text" name="RegNr" class="txtField">
            <br>
            Kuupäev:<br>
            <input type="datetime-local" name="Kuupaev" class="txtField">
            <br>
            Odomeeter:<br>
            <input type="number" name="Odomeeter" class="txtField">
            <br>
            Tehtud Tööd:<br>
            <textarea name="Tehtud_tood" class="txtField"></textarea>
            <br>
            <div class="formButton">
                <input type="submit" name="submit" value="Lisa" class="button">
            </div>
        </div>
    </form>

<?php require_once '../includes/footer.php'; ?>
</body>

</html>
