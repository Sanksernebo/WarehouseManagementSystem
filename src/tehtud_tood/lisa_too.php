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
    <script src="https://kit.fontawesome.com/4d1395116e.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="icon" type="image/x-icon" href="../img/cartehniklogo_svg.svg">
    <title>Lisa Töö</title>
</head>

<body>
<?php require_once '../includes/nav.php'; ?>

    <h1>Lisa Tehtud Töö</h1>
    <form method="post" action="lisa_too-process.php">
        <?= csrf_field() ?>
        Auto Reg.Nr:<br>
        <input type="text" name="RegNr">
        <br>
        Kuupäev:<br>
        <input type="datetime-local" name="Kuupaev">
        <br><br>
        Odomeeter:<br>
        <div class="odomeeter-container">
            <input type="number" id="odomeeter" name="Odomeeter">
            <span class="odomeeter-km">km</span>
        </div>
        <br>
        Tehtud Tööd:<br>
        <textarea type="text" name="Tehtud_tood" rows="4" cols="48"></textarea>

        <br><br>
        <div class="formButton">
            <input type="submit" name="submit" value="Sisesta">
        </div>
    </form>

<?php require_once '../includes/footer.php'; ?>
</body>

</html>
