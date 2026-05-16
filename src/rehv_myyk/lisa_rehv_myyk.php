<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}
require_once '../includes/csrf.php';
?>
<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
    <meta charset="utf-8">
    <link rel="stylesheet" href="../../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="icon" type="image/x-icon" href="../img/cartehniklogo_svg.svg">
    <title>Rehvi Müük</title>
</head>

<body>
<?php require_once '../includes/nav.php'; ?>

    <h1>Lisa Müüdud Rehvid</h1>
    <form method="post" action="rehv_myyk-process.php">
        <?= csrf_field() ?>
        Auto Reg.Nr:<br>
        <input type="text" name="RegNr">
        <br>
        Mõõt:<br>
        <input type="text" name="Moot">
        <br>
        Tootja:
        <input type="text" name="Tootja">
        <br>
        Kogus:<br>
        <input type="number" name="Kogus">
        <br>
        <label for="Hooaeg" name="Hooaeg">Hooaeg</label>
        <select name="hooaeg">
            <option value="Suverehv">Suverehv</option>
            <option value="Naastrehv">Naastrehv</option>
            <option value="Lamellrehv">Lamellrehv</option>
        </select>
        <br>
        <label for="Tarnija" name="Tarnija">Tarnija</label>
        <select name="tarnija">
            <option value="INTERCARS">InterCars</option>
            <option value="ERIMELL">Erimell</option>
            <option value="LATTAKO">Latakko</option>
            <option value="MUU">Muu</option>
        </select>
        <br>
        Kuupäev<br>
        <input type="date" name="Kuupaev">
        <br><br>
        <div class="formButton">
            <input type="submit" name="submit" value="Sisesta">
        </div>
    </form>

<?php require_once '../includes/footer.php'; ?>
</body>

</html>
