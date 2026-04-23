<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}
include_once '../db/laoseis.php';

$message = '';

if (count($_POST) > 0) {
    require_once '../includes/csrf.php';
    csrf_verify();

    $stmt = mysqli_prepare($conn, "UPDATE Tehtud_tood SET RegNr=?, Kuupaev=?, Odomeeter=?, Tehtud_tood=? WHERE too_id=?");
    mysqli_stmt_bind_param($stmt, 'ssisi', $_POST['RegNr'], $_POST['Kuupaev'], $_POST['Odomeeter'], $_POST['Tehtud_tood'], $_POST['too_id']);

    if (!mysqli_stmt_execute($stmt)) {
        $message = "Uuendamine ebaõnnestus.";
    } elseif (mysqli_stmt_affected_rows($stmt) > 0) {
        $message = "Edukalt uuendatud!";
    } else {
        $message = "Andmed jäid samaks.";
    }

    mysqli_stmt_close($stmt);
    header("Location: ../tehtud_tood/tehtud_tood.php");
    exit;
}

if (isset($_GET['too_id'])) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM Tehtud_tood WHERE too_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $_GET['too_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_array($result)) {
        // format datetime for datetime-local input (YYYY-MM-DDTHH:MM)
        $kuupaev_formatted = date('Y-m-d\TH:i', strtotime($row['Kuupaev']));
    } else {
        echo "Error: Autot ei leitud";
        exit;
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
    <meta charset="utf-8">
    <link rel="stylesheet" href="../../style.css">
    <script src="https://kit.fontawesome.com/4d1395116e.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="icon" type="image/x-icon" href="../img/cartehniklogo_svg.svg">
    <title>Tehtud Tööd - <?php echo htmlspecialchars($row['RegNr']); ?></title>
</head>

<body>
<?php require_once '../includes/nav.php'; ?>

    <form name="frmUser" method="post" action="">
        <?= csrf_field() ?>
        <div><?php if ($message) echo htmlspecialchars($message); ?></div>
        <div style="padding-bottom:5px;">
            ID: <br>
            <input type="text" name="too_id" value="<?php echo htmlspecialchars($row['too_id']); ?>" readonly>
            <br>
            Reg Nr: <br>
            <input type="text" name="RegNr" class="txtField" value="<?php echo htmlspecialchars($row['RegNr']); ?>">
            <br>
            Kuupäev:<br>
            <input type="datetime-local" name="Kuupaev" class="txtField" value="<?php echo $kuupaev_formatted; ?>">
            <br>
            Odomeeter:<br>
            <input type="number" name="Odomeeter" class="txtField" value="<?php echo htmlspecialchars($row['Odomeeter']); ?>">
            <br>
            Tehtud Tööd:<br>
            <textarea type="text" name="Tehtud_tood" rows="4" cols="48"><?php echo htmlspecialchars($row['Tehtud_tood']); ?></textarea>
            <br>
            <div class="formButton">
                <input type="submit" name="submit" value="Uuenda" class="button">
            </div>
        </div>
    </form>

<?php require_once '../includes/footer.php'; ?>
</body>

</html>
