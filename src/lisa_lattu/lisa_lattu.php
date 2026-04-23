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
    <meta charset="utf-8">
    <link rel="stylesheet" href="../../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="icon" type="image/x-icon" href="../img/cartehniklogo_svg.svg">
    <title>Toote Sisestus</title>
</head>

<body>
<?php require_once '../includes/nav.php'; ?>

    <h1>Lisa Toode</h1>
    <form method="post" action="lisa_lattu-process.php">
        <?= csrf_field() ?>
        Tootekood:<br>
        <input type="text" name="Tootekood">
        <br>
        Nimetus:<br>
        <input type="text" name="Nimetus">
        <br>
        Kogus:<br>
        <input type="number" name="Kogus">
        <br>
        Sisseostu Hind:<br>
        <input type="number" step="0.01" name="Sisseost">
        <br>
        Jaehind:<br>
        <input type="number" step="0.01" name="Jaehind">
        <br>
        Tehtud Hind:<br>
        <input type="number" value="0" step="0.01" name="Lopphind" readonly>
        <br>
        <label for="Ost" name="Ost">Ostetud</label>
        <select name="ost" value="-">
            <option value="-">-</option>
            <option value="InterCars">Inter Cars</option>
            <option value="AD Baltic">AD Baltic</option>
            <option value="Balti Autoosad">Balti Autoosad</option>
            <option value="Erimell">Erimell</option>
        </select>
        <br>
        <label for="Olek" name="Olek">Olek</label>
        <select name="olek" value="Isiklik">
            <option value="Isiklik">Isiklik</option>
            <option value="Firma">Firma</option>
            <option value="Tagastus">Tagastus</option>
        </select>
        <br><br>
        <div class="formButton">
            <input type="submit" name="submit" value="Sisesta">
        </div>
    </form>

<?php require_once '../includes/footer.php'; ?>
</body>

</html>
