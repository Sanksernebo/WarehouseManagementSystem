<?php
session_start();
include_once '../db/laoseis.php';

$error_flag = false; // Flag to track login errors

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Retrieve user from the database
    $query = "SELECT ID, parool FROM Login WHERE kasutajanimi = '$username'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        // Verify password
        if (password_verify($password, $user['parool'])) {
            $_SESSION['user_id'] = $user['ID'];
            $_SESSION['username'] = $username; // Store the username in the session
            header("Location: ../../index.php"); // Redirect avalehele
            exit;
        } else {
            $error_flag = true; // Incorrect password
        }
    } else {
        $error_flag = true; // Username not found
    }
}

?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
    <script src="https://kit.fontawesome.com/4d1395116e.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="icon" type="image/x-icon" href="src/img/cartehniklogo_svg.svg">
    <title>Login</title>
    <link rel="stylesheet" href="../../style.css">
    <link rel="icon" type="image/x-icon" href="../img/cartehniklogo_svg.svg">
</head>

<body>
    <nav>
        <div class="logo">
            <a href="../../index.php">
                <img src="../img/cartehniklogo_valge.svg" alt="Cartehnik logo">
            </a>
        </div>
        <div class="nav-links">
            <a href="../../index.php">Avaleht</a>
            <a href="../myydud_tooted/myyk.php">Müüdud Tooted</a>
            <a href="../tehtud_tood/tehtud_tood.php">Tehtud Tööd</a>
            <div class="dropdown">
                <button class="dropbtn">Rehvid
                    <i class="fa fa-caret-down"></i>
                </button>
                <div class="dropdown-content">
                    <a href="../rehv_myyk/rehv_myyk.php">Müüdud Rehvid</a>
                    <a href="../rehv_ladu/rehv_ladu.php">Rehvid Laos</a>
                </div>
            </div>
    </nav>
    <div>
        <form action="login.php" method="post">
            <h1>Logi Sisse</h1>
            <div>
                <label for="username">Kasutajanimi:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div>
                <label for="password">Parool:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="formButton">
                <input type="submit" value="Logi sisse"></input>
            </div>
            <?php if ($error_flag): ?>
                <p class="error">Vale kasutajanimi või parool!</p>
            <?php endif; ?>
        </form>
    </div>
    <footer>
        <p>Rõngu Auto OÜ</p>
        <p>Copyright &copy;
            <script>document.write(new Date().getFullYear())</script>
        </p>
    </footer>
</body>

</html>