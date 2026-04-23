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
    <title>Rehvid Laos</title>
</head>

<body>
<?php require_once '../includes/nav.php'; ?>

    <h1>Lisa Rehvid Lattu</h1>
    <form method="post" action="rehv_ladu-process.php">
        <?= csrf_field() ?>
        Auto Reg.Nr:<br>
        <input type="text" name="RegNr">
        <br>
        Omanik:<br>
        <input type="text" name="Omanik">
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
        Kuupäev<br>
        <input type="date" name="Kuupaev">
        <br><br>
        <div class="formButton">
            <input type="submit" name="submit" value="Lisa">
        </div>
    </form>

<?php require_once '../includes/footer.php'; ?>
</body>

</html>
