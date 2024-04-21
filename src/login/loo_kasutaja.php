<?php
session_start();
include_once '../db/laoseis.php'; // Adjust the path as per your project structure

// Check if the user is logged in, otherwise redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username'], $_POST['password'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Prepare the insert statement
    $stmt = mysqli_prepare($conn, "INSERT INTO Login (kasutajanimi, parool) VALUES (?, ?)");

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $username, $hashed_password);
        $result = mysqli_stmt_execute($stmt);
        if ($result) {
            echo "Uus kasutaja edukalt loodud!";
        } else {
            echo "Error: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
    <meta charset="utf-8">
    <link rel="stylesheet" href="/style.css">
    <script src="https://kit.fontawesome.com/4d1395116e.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="icon" type="image/x-icon" href="../img/cartehniklogo_svg.svg">
    <title>Loo kasutaja</title>
    <link rel="stylesheet" href="../style.css">
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
        <a href="../login/logout.php">Logi välja</a>
    </div>
    </nav>
<div>
    <form action="loo_kasutaja.php" method="post">
        <h1>Loo Uus Kasutaja</h1>
        <div>
            <label for="username">Kasutajanimi:</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div>
            <label for="password">Parool:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div class="formButton">
            <input type="submit" value="Loo kasutaja"></input>
        </div>
    </form>
</div>
<footer>
    <p>Rõngu Auto OÜ</p>
    <p>Copyright &copy; <script>document.write(new Date().getFullYear())</script></p>
</footer>
</body>
</html>
