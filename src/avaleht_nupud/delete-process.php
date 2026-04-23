<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}
include_once '../db/laoseis.php';

if (isset($_GET['ID'])) {
    $id = $_GET['ID'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM Ladu WHERE toote_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_array($result);
    } else {
        echo "Error: Toodet ei leitud";
        exit();
    }
    mysqli_stmt_close($stmt);
}

if (isset($_POST['confirm_delete'])) {
    require_once '../includes/csrf.php';
    csrf_verify();

    $id = $_POST['ID'];
    $stmt = mysqli_prepare($conn, "DELETE FROM Ladu WHERE toote_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        header("Location: ../../index.php");
        exit();
    } else {
        echo "Error: Ei suutnud kustutada.";
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="../../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="icon" type="image/x-icon" href="../img/cartehniklogo_svg.svg">
    <title>Toote andmed</title>
</head>

<body>
<?php require_once '../includes/nav.php'; ?>

    <h1>Kustuta toode laost</h1>
    <form name="frmUser" method="post" action="">
        <?= csrf_field() ?>
        <div style="padding-bottom:5px;">
            <input type="hidden" name="ID" value="<?php echo htmlspecialchars($row['toote_id']); ?>">

            <label>ID:</label>
            <input type="text" value="<?php echo htmlspecialchars($row['toote_id']); ?>" readonly><br>
            <label>Tootekood:</label>
            <input type="text" value="<?php echo htmlspecialchars($row['Tootekood']); ?>" readonly><br>
            <label>Nimetus:</label>
            <input type="text" value="<?php echo htmlspecialchars($row['Nimetus']); ?>" readonly><br>
            <label>Kogus:</label>
            <input type="number" value="<?php echo htmlspecialchars($row['Kogus']); ?>" readonly><br>
            <label>Sisseostetu Hind:</label>
            <input type="number" value="<?php echo htmlspecialchars($row['Sisseost']); ?>" readonly><br>
            <label>Jaehind:</label>
            <input type="number" value="<?php echo htmlspecialchars($row['Jaehind']); ?>" readonly><br>
            <label>Ostetud:</label>
            <input type="text" value="<?php echo htmlspecialchars($row['Ost']); ?>" readonly><br>
            <label>Olek:</label>
            <input type="text" value="<?php echo htmlspecialchars($row['Olek']); ?>" readonly><br>
            <div class="formButton">
                <input type="submit" name="confirm_delete" value="Kinnita Kustutamine">
            </div>
        </div>
    </form>

<?php require_once '../includes/footer.php'; ?>
</body>

</html>
