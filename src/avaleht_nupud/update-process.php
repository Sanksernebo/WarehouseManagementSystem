<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}
include_once '../db/laoseis.php';

if (count($_POST) > 0) {
    require_once '../includes/csrf.php';
    csrf_verify();

    $stmt = mysqli_prepare($conn, "UPDATE Ladu SET Tootekood=?, Nimetus=?, Kogus=?, Sisseost=?, Jaehind=?, Lopphind=?, Ost=?, Olek=? WHERE toote_id=?");
    mysqli_stmt_bind_param($stmt, 'ssddsdssi', $_POST['Tootekood'], $_POST['Nimetus'], $_POST['Kogus'], $_POST['Sisseost'], $_POST['Jaehind'], $_POST['Lopphind'], $_POST['Ost'], $_POST['Olek'], $_POST['toote_id']);

    if (!mysqli_stmt_execute($stmt)) {
        $message = "Uuendamine ebaõnnestus.";
    } else {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $message = "Edukalt uuendatud!";
        } else {
            $message = "Uuendamine ebaõnnestus või andmed jäid samaks.";
        }
    }

    mysqli_stmt_close($stmt);
    header("Location: ../../index.php");
    exit;
}

if (isset($_GET['ID'])) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM Ladu WHERE toote_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $_GET['ID']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_array($result)) {
        // record fetched
    } else {
        echo "Error: Toodet ei leitud";
        exit;
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <link rel="stylesheet" href="../../style.css">
    <script src="https://kit.fontawesome.com/4d1395116e.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="icon" type="image/x-icon" href="../img/cartehniklogo_svg.svg">
    <title>Toote andmed</title>
</head>

<body>
<?php require_once '../includes/nav.php'; ?>

    <h1>Muuda toote andmeid</h1>

    <form name="frmUser" method="post" action="">
        <?= csrf_field() ?>
        <div><?php if (isset($message)) echo htmlspecialchars($message); ?></div>
        <div style="padding-bottom:5px;">
            <input type="hidden" name="toote_id" class="txtField" value="<?php echo htmlspecialchars($row['toote_id']); ?>">
            <br>
            Tootekood: <br>
            <input type="text" name="Tootekood" class="txtField" value="<?php echo htmlspecialchars($row['Tootekood']); ?>">
            <br>
            Nimetus:<br>
            <input type="text" name="Nimetus" class="txtField" value="<?php echo htmlspecialchars($row['Nimetus']); ?>">
            <br>
            Kogus:<br>
            <input type="number" name="Kogus" class="txtField" value="<?php echo htmlspecialchars($row['Kogus']); ?>">
            <br>
            Sisseostetu Hind:<br>
            <input type="number" name="Sisseost" class="txtField" value="<?php echo htmlspecialchars($row['Sisseost']); ?>">
            <br>
            Jaehind:<br>
            <input type="number" name="Jaehind" class="txtField" value="<?php echo htmlspecialchars($row['Jaehind']); ?>">
            <br>
            Tehtud Hind:<br>
            <input type="number" name="Lopphind" step="0.01" class="txtField" value="0">
            <label for="ost" name="Ost">Ostetud</label>
            <select id="ost" name="Ost">
                <option value="-" <?php if ($row['Ost'] == '-') echo 'selected'; ?>>-</option>
                <option value="InterCars" <?php if ($row['Ost'] == 'InterCars') echo 'selected'; ?>>Inter Cars</option>
                <option value="AD Baltic" <?php if ($row['Ost'] == 'AD Baltic') echo 'selected'; ?>>AD Baltic</option>
                <option value="Balti Autoosad" <?php if ($row['Ost'] == 'Balti Autoosad') echo 'selected'; ?>>Balti Autoosad</option>
                <option value="Erimell" <?php if ($row['Ost'] == 'Erimell') echo 'selected'; ?>>Erimell</option>
            </select>
            <br>
            <label for="olek" name="Olek">Olek</label>
            <select id="olek" name="Olek">
                <option value="Isiklik" <?php if ($row['Olek'] == 'Isiklik') echo 'selected'; ?>>Isiklik</option>
                <option value="Firma" <?php if ($row['Olek'] == 'Firma') echo 'selected'; ?>>Firma</option>
                <option value="Tagastus" <?php if ($row['Olek'] == 'Tagastus') echo 'selected'; ?>>Tagastus</option>
            </select>
            <br>
            <div class="formButton">
                <input type="submit" name="submit" value="Uuenda" class="button">
            </div>
        </div>
    </form>

<?php require_once '../includes/footer.php'; ?>
</body>

</html>
