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
    // Prepare the SQL statement
    $sql = "INSERT INTO Ladu (Tootekood, Nimetus, Kogus, Sisseost, Jaehind, Lopphind, Ost, Olek) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);

    // Check if the statement was prepared successfully
    if ($stmt === false) {
        die('MySQL prepare error: ' . mysqli_error($conn));
    }

    // Bind parameters to the prepared statement as strings or numbers as appropriate
    mysqli_stmt_bind_param($stmt, 'ssiddisd', $_POST['Tootekood'], $_POST['Nimetus'], $_POST['Kogus'], $_POST['Sisseost'], $_POST['Jaehind'], $_POST['Lopphind'], $_POST['Ost'], $_POST['Olek']);

    // Execute the prepared statement
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt); // Close the statement to free up resources
        header("Location: ../../index.php");
        exit;
    } else {
        $message = "Error inserting data: " . mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt); // Close the statement to free up resources
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Toote Sisestus</title>
    <link rel="icon" type="image/x-icon" href="img/cartehniklogo_svg.svg">
    <link rel="stylesheet" href="/style.css">
    <meta charset="utf-8">
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
    <p style="font-weight:bold;"><?= $message ?></p>
<?php endif; ?>

<footer>
    <p>Rõngu Auto OÜ</p>
    <p>Copyright &copy; <script>document.write(new Date().getFullYear());</script></p>
</footer>
</body>
</html>
<?php
mysqli_close($conn);
?>