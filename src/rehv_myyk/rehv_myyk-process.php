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

if (isset($_POST['submit'])) {
    $RegNr = $_POST['RegNr'];
    $Kuupaev = $_POST['Kuupaev'];
    $Kogus = $_POST['Kogus'];
    $Moot = $_POST['Moot'];
    $Tootja = $_POST['Tootja'];
    $hooaeg = $_POST['hooaeg'];
    $tarnija = $_POST['tarnija'];

    $sql = "INSERT INTO Rehvi_myyk (RegNr, Moot, Tootja, Kogus, Hooaeg, Tarnija, Kuupaev)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssss", $RegNr, $Moot, $Tootja, $Kogus, $hooaeg, $tarnija, $Kuupaev);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        header("Location: rehv_myyk.php");
        exit();
    } else {
        $message = "Tulemusi ei leitud";
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Rehvi Müügi Sisestus</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
    <meta charset="utf-8">
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
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
    <?php if (!empty($message)): ?>
        <p style="font-weight: bold;"><?php echo $message; ?></p>
    <?php endif; ?>
    <footer>
        <p>Rõngu Auto OÜ</p>
        <p>Copyright &copy; <script>document.write(new Date().getFullYear());</script></p>
    </footer>
</body>
</html>
