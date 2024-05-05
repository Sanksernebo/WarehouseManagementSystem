<?php
session_start();
// Check if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page
    header("Location: ../login/login.php");
    exit;
}
include_once '../db/laoseis.php';
$message = '';
$error = '';

if (isset($_POST['submit'])) {
    $RegNr = $_POST['RegNr'];
    $Kuupaev = $_POST['Kuupaev'];
    $Odomeeter = $_POST['Odomeeter'];
    $Tehtud_tood = $_POST['Tehtud_tood'];

    // Using prepared statements to prevent SQL Injection
    $sql = "INSERT INTO Tehtud_tood (RegNr, Kuupaev, Odomeeter, Tehtud_tood)
            VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssis", $RegNr, $Kuupaev, $Odomeeter, $Tehtud_tood);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        header("Location: tehtud_tood.php");
        exit;
    } else {
        $error = "Viga sisestamisel: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Töö Sisestus</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
    <meta charset="utf-8">
    <link rel="stylesheet" href="/style.css">
    <link rel="icon" type="image/x-icon" href="img/cartehniklogo_svg.svg">
</head>

<body>
    <nav>
        <div class="logo">
            <a href="../../index.php">
                <img src="/src/img/cartehniklogo_valge.svg" alt="Cartehnik logo">
            </a>
        </div>
        <div class="nav-links">
            <a href="../../index.php">Avaleht</a>
            <a href="/src/myydud_tooted/myyk.php">Müüdud Tooted</a>
            <a href="/src/tehtud_tood/tehtud_tood.php">Tehtud Tööd</a>
            <div class="dropdown">
                <button class="dropbtn">Rehvid
                    <i class="fa fa-caret-down"></i>
                </button>
                <div class="dropdown-content">
                    <a href="/src/rehv_myyk/rehv_myyk.php">Müüdud Rehvid</a>
                    <a href="/src/rehv_ladu/rehv_ladu.php">Rehvid Laos</a>
                </div>
            </div>
            <a href="../login/logout.php">
                <?php if (isset($_SESSION['username'])): ?>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?>,</span>
                <?php endif; ?>
                Logi välja
            </a>
        </div>
    </nav>
    <form name="frmUser" method="post" action="">
        <div><?php if (!empty($error)) {
            echo $error;
        } ?></div>
        <div style="padding-bottom:5px;">
            Reg.Nr: <br>
            <input type="text" name="RegNr" class="txtField">
            <br>
            Kuupäev:<br>
            <input type="date" name="Kuupaev" class="txtField">
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
    <footer>
        <p>Rõngu Auto OÜ</p>
        <p>Copyright &copy;
            <script>document.write(new Date().getFullYear());</script>
        </p>
    </footer>
</body>

</html>